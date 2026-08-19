<?php

use App\Enums\RbacRole;
use App\Models\EmailSuppression;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;
use RuntimeException as BaseRuntimeException;
use Spatie\Permission\Models\Role;
use Throwable as BaseThrowable;

Schedule::command('horizon:snapshot')->everyMinute();

// AI tutor usage for the previous day, as CloudWatch metrics. Runs after
// midnight so the day it reports on is closed.
Schedule::command('ai:metrics')->dailyAt('00:20');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('health:check', function () {
    $checks = [
        'database' => function (): void {
            DB::select('select 1');
        },
        'cache' => function (): void {
            $key = 'health-check:'.Str::uuid();

            Cache::put($key, 'ok', 5);

            if (Cache::get($key) !== 'ok') {
                throw new BaseRuntimeException('Cache read check failed.');
            }

            Cache::forget($key);
        },
    ];

    $failed = false;

    foreach ($checks as $name => $check) {
        try {
            $check();
            $this->info("Checking {$name}... OK");
        } catch (BaseThrowable $exception) {
            $failed = true;
            $this->error("Checking {$name}... FAILED");
            $this->line($exception->getMessage());
        }
    }

    if ($failed) {
        $this->error('Application health check failed.');

        return 1;
    }

    $this->info('Application health check passed.');

    return 0;
})->purpose('Check application readiness dependencies');

Artisan::command('user:promote-admin {email}', function (string $email) {
    $user = User::query()->where('email', $email)->first();

    if (! $user) {
        $this->error("User not found for email: {$email}");

        return 1;
    }

    Role::findOrCreate(RbacRole::ADMIN->value, 'web');

    // Admin must be admin-only (no student role).
    $user->syncRoles([RbacRole::ADMIN->value]);

    $this->info("Promoted {$user->email} to admin.");

    return 0;
})->purpose('Promote an existing user to admin role');

Artisan::command('mail:unsuppress {email}', function (string $email) {
    // Matched case-insensitively: SES echoes recipients back in whatever casing
    // the original message used, which rarely matches what an operator types.
    $suppression = EmailSuppression::query()
        ->whereRaw('lower(email) = ?', [Str::lower($email)])
        ->first();

    if (! $suppression) {
        $this->error("{$email} is not suppressed.");

        return 1;
    }

    $suppression->delete();

    // The webhook logs every suppression it adds; log removals too so the
    // audit trail stays symmetric.
    Log::info('Suppression removed via console', ['email' => $suppression->email]);

    $this->info("Removed {$suppression->email} from the suppression list.");

    return 0;
})->purpose('Remove an email address from the bounce/complaint suppression list');
