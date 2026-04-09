<?php

use App\Models\User;
use App\Enums\RbacRole;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:promote-admin {email}', function (string $email) {
    $user = User::query()->where('email', $email)->first();

    if (! $user) {
        $this->error("User not found for email: {$email}");
        return 1;
    }

    Role::findOrCreate(RbacRole::ADMIN->value, 'web');

    $user->assignRole(RbacRole::ADMIN->value);

    $this->info("Promoted {$user->email} to admin.");
    return 0;
})->purpose('Promote an existing user to admin role');
