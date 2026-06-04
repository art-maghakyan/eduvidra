<?php

use App\Enums\RbacPermission;
use App\Enums\RbacRole;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/health', HealthController::class)->name('health');

Route::get('/robots.txt', RobotsController::class)->name('robots');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', 'role:'.RbacRole::STUDENT->value])->name('dashboard');

Route::middleware([
    'auth',
    'verified',
    'permission:'.RbacPermission::ADMIN_ACCESS->value,
])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:'.RbacPermission::USERS_MANAGE->value)
        ->name('users.index');

    Route::post('/users/{user}/two-factor/required', [UserController::class, 'toggleTwoFactorRequired'])
        ->middleware('permission:'.RbacPermission::USERS_MANAGE->value)
        ->name('users.two-factor.required');

    Route::post('/users/{user}/two-factor/reset', [UserController::class, 'resetTwoFactor'])
        ->middleware('permission:'.RbacPermission::USERS_MANAGE->value)
        ->name('users.two-factor.reset');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:'.RbacRole::STUDENT->value])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
