<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RbacRole;
use App\Http\Controllers\Controller;
use App\Mail\WelcomeEmail;
use App\Models\EmailSuppression;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FALaravel\Facade as Google2FA;

class TwoFactorSetupController extends Controller
{
    public function create(Request $request): Response
    {
        $userId = $request->session()->get('2fa:enroll_user_id');
        if (! $userId) {
            abort(403);
        }

        /** @var User $user */
        $user = User::query()->findOrFail($userId);

        // Generate secret once per enroll-session.
        $secret = $request->session()->get('2fa:enroll_secret');
        if (! is_string($secret) || $secret === '') {
            $secret = Google2FA::generateSecretKey();
            $request->session()->put('2fa:enroll_secret', $secret);
        }

        $issuer = (string) config('app.name', 'Eduvidra');
        $accountName = (string) $user->email;
        $qrSvg = Google2FA::setQrCodeBackend('svg')->getQRCodeInline($issuer, $accountName, $secret);

        return Inertia::render('Auth/TwoFactorSetup', [
            'qrSvg' => $qrSvg,
            'qrUrl' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('2fa:enroll_user_id');
        $secret = $request->session()->get('2fa:enroll_secret');

        if (! $userId || ! is_string($secret) || $secret === '') {
            abort(403);
        }

        $this->ensureIsNotRateLimited($request);

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        if (! Google2FA::verifyKey($secret, $validated['code'], 1)) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'code' => __('Invalid authentication code.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        /** @var User $user */
        $user = User::query()->findOrFail($userId);

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => CarbonImmutable::now(),
        ])->save();

        $remember = (bool) $request->session()->pull('2fa:remember', false);

        $request->session()->forget(['2fa:enroll_user_id', '2fa:enroll_secret']);
        $request->session()->regenerate();

        Auth::login($user, $remember);

        if ($request->session()->pull('just_registered') && $user->hasRole(RbacRole::STUDENT->value)) {
            if (EmailSuppression::isSuppressed($user->email)) {
                logger()->warning('Welcome email skipped — address is suppressed', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            } else {
                try {
                    Mail::to($user)->queue(new WelcomeEmail($user));
                } catch (\Throwable $e) {
                    logger()->error('Failed to queue welcome email', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return redirect()->intended(route($this->landingRouteNameFor($user), absolute: false));
    }

    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'code' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return '2fa-setup|'.$request->ip();
    }

    private function landingRouteNameFor(User $user): string
    {
        if ($user->hasRole(RbacRole::ADMIN->value)) {
            return 'admin.dashboard';
        }

        return 'dashboard';
    }
}
