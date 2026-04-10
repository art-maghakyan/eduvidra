<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RbacRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $students = User::query()
            ->role(RbacRole::STUDENT->value)
            ->orderBy('name')
            ->select(['id', 'name', 'email', 'created_at'])
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'createdAt' => optional($u->created_at)?->toDateString(),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'students' => $students,
        ]);
    }
}

