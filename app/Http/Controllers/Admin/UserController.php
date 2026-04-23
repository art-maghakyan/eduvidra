<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RbacRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $students = User::query()
            ->role(RbacRole::STUDENT->value)
            ->orderBy('name')
            ->select(['id', 'name', 'email', 'created_at', 'two_factor_required', 'two_factor_confirmed_at'])
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'createdAt' => optional($u->created_at)?->toDateString(),
                'twoFactorRequired' => (bool) $u->two_factor_required,
                'twoFactorEnrolled' => (bool) $u->two_factor_confirmed_at,
            ]);

        return Inertia::render('Admin/Users/Index', [
            'students' => $students,
        ]);
    }

    public function toggleTwoFactorRequired(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'required' => ['required', 'boolean'],
        ]);

        $user->forceFill([
            'two_factor_required' => (bool) $validated['required'],
        ])->save();

        return back();
    }

    public function resetTwoFactor(User $user): RedirectResponse
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return back();
    }
}
