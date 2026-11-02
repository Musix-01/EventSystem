<?php

use App\Http\Controllers\Admin\EventReviewController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Officer\EventController as OfficerEventController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\StudentEventController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Students and student org officers
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    // PUP office admins
    Route::get('/admin/login', [AdminLoginController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminLoginController::class, 'store'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Everyone: browse events, view one, join, leave, see your own tickets
    Route::get('/events', [HomeController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [StudentEventController::class, 'show'])->name('events.show');
    Route::post('/events/{event}/join', [RegistrationController::class, 'store'])->name('events.join');
    Route::delete('/events/{event}/leave', [RegistrationController::class, 'destroy'])->name('events.leave');
    Route::get('/my-events', [RegistrationController::class, 'index'])->name('registrations.index');

    // Org officers: request and manage their org's events
    Route::middleware(EnsureRole::class . ':officer')
        ->prefix('officer')->name('officer.')->group(function () {
            Route::get('/events', [OfficerEventController::class, 'index'])->name('events.index');
            Route::get('/events/create', [OfficerEventController::class, 'create'])->name('events.create');
            Route::post('/events', [OfficerEventController::class, 'store'])->name('events.store');
            Route::patch('/events/{event}/cancel', [OfficerEventController::class, 'cancel'])->name('events.cancel');
        });

    // Admin: approve or decline requests
    Route::middleware(EnsureRole::class . ':admin')
        ->prefix('admin')->name('admin.')->group(function () {
            Route::get('/events', [EventReviewController::class, 'index'])->name('events.index');
            Route::patch('/events/{event}/approve', [EventReviewController::class, 'approve'])->name('events.approve');
            Route::patch('/events/{event}/decline', [EventReviewController::class, 'decline'])->name('events.decline');
        });
});
