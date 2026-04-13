<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RbacRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Default role for all new registrations.
        $user->assignRole(RbacRole::STUDENT->value);

        // Reload so DB defaults (e.g. two_factor_required = true) are present on the model.
        $user->refresh();

        event(new Registered($user));

        Auth::login($user);

        // 2FA is required by default: force enrollment immediately after registration.
        if ($user->two_factor_required && ! $user->two_factor_confirmed_at) {
            $request->session()->put('2fa:enroll_user_id', $user->id);
            $request->session()->put('2fa:remember', false);

            Auth::guard('web')->logout();

            return redirect()->route('two-factor.setup');
        }

        return redirect(route('dashboard', absolute: false));
    }
}
