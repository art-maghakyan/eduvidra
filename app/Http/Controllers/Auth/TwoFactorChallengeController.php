<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RbacRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FALaravel\Facade as Google2FA;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): Response
    {
        $userId = $request->session()->get('2fa:user_id');
        if (! $userId) {
            abort(403);
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('2fa:user_id');
        if (! $userId) {
            abort(403);
        }

        $this->ensureIsNotRateLimited($request);

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        /** @var User $user */
        $user = User::query()->findOrFail($userId);

        if (! $user->two_factor_confirmed_at || ! $user->two_factor_secret) {
            abort(403);
        }

        if (! Google2FA::verifyKey($user->two_factor_secret, $validated['code'], 1)) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'code' => __('Invalid authentication code.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        $remember = (bool) $request->session()->pull('2fa:remember', false);

        $request->session()->forget(['2fa:user_id']);
        $request->session()->regenerate();

        Auth::login($user, $remember);

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
        return '2fa-challenge|'.$request->ip();
    }

    private function landingRouteNameFor(User $user): string
    {
        if ($user->hasRole(RbacRole::ADMIN->value)) {
            return 'admin.dashboard';
        }

        return 'dashboard';
    }
}
