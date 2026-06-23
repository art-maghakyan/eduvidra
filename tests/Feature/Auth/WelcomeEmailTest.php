<?php

namespace Tests\Feature\Auth;

use App\Enums\RbacRole;
use App\Mail\WelcomeEmail;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FALaravel\Facade as Google2FA;
use Tests\TestCase;

class WelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_welcome_email_is_queued_after_student_completes_2fa_setup(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'two_factor_required' => true,
            'two_factor_confirmed_at' => null,
            'two_factor_secret' => null,
        ]);
        $user->assignRole(RbacRole::STUDENT->value);

        $secret = Google2FA::generateSecretKey();

        $this->withSession([
            '2fa:enroll_user_id' => $user->id,
            '2fa:enroll_secret' => $secret,
            'just_registered' => true,
        ]);

        $response = $this->post('/two-factor/setup', [
            'code' => Google2FA::getCurrentOtp($secret),
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        Mail::assertQueued(WelcomeEmail::class, fn ($mail) => $mail->user->id === $user->id);
    }

    public function test_welcome_email_is_not_queued_without_just_registered_flag(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'two_factor_required' => true,
            'two_factor_confirmed_at' => null,
            'two_factor_secret' => null,
        ]);
        $user->assignRole(RbacRole::STUDENT->value);

        $secret = Google2FA::generateSecretKey();

        $this->withSession([
            '2fa:enroll_user_id' => $user->id,
            '2fa:enroll_secret' => $secret,
        ]);

        $this->post('/two-factor/setup', [
            'code' => Google2FA::getCurrentOtp($secret),
        ]);

        Mail::assertNothingQueued();
    }

    public function test_welcome_email_is_not_queued_for_admin_completing_2fa_setup(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'two_factor_required' => true,
            'two_factor_confirmed_at' => null,
            'two_factor_secret' => null,
        ]);
        $user->assignRole(RbacRole::ADMIN->value);

        $secret = Google2FA::generateSecretKey();

        $this->withSession([
            '2fa:enroll_user_id' => $user->id,
            '2fa:enroll_secret' => $secret,
            'just_registered' => true,
        ]);

        $this->post('/two-factor/setup', [
            'code' => Google2FA::getCurrentOtp($secret),
        ]);

        Mail::assertNothingQueued();
    }
}
