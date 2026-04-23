<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FALaravel\Facade as Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_login_requires_two_factor_setup(): void
    {
        $user = User::factory()->create([
            'two_factor_required' => true,
            'two_factor_confirmed_at' => null,
            'two_factor_secret' => null,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('two-factor.setup', absolute: false));
    }

    public function test_two_factor_setup_can_be_confirmed_and_logs_user_in(): void
    {
        $user = User::factory()->create([
            'two_factor_required' => true,
            'two_factor_confirmed_at' => null,
            'two_factor_secret' => null,
        ]);

        $this->withSession([
            '2fa:enroll_user_id' => $user->id,
            '2fa:enroll_secret' => ($secret = Google2FA::generateSecretKey()),
        ]);

        $response = $this->post('/two-factor/setup', [
            'code' => Google2FA::getCurrentOtp($secret),
        ]);

        $this->assertAuthenticated();

        $user->refresh();
        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertNotNull($user->two_factor_secret);

        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_login_requires_two_factor_challenge_after_enrollment(): void
    {
        $user = User::factory()->create([
            'two_factor_required' => true,
            'two_factor_confirmed_at' => CarbonImmutable::now(),
            'two_factor_secret' => Google2FA::generateSecretKey(),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('two-factor.challenge', absolute: false));
    }

    public function test_two_factor_challenge_accepts_valid_code(): void
    {
        $secret = Google2FA::generateSecretKey();

        $user = User::factory()->create([
            'two_factor_required' => true,
            'two_factor_confirmed_at' => CarbonImmutable::now(),
            'two_factor_secret' => $secret,
        ]);

        $this->withSession([
            '2fa:user_id' => $user->id,
        ]);

        $response = $this->post('/two-factor/challenge', [
            'code' => Google2FA::getCurrentOtp($secret),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_user_with_two_factor_not_required_can_login_without_setup_or_challenge(): void
    {
        $user = User::factory()->create([
            'two_factor_required' => false,
            'two_factor_confirmed_at' => null,
            'two_factor_secret' => null,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
