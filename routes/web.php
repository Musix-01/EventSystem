<?php

use App\Http\Controllers\Admin\EventReviewController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\Officer\EventController as OfficerEventController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\StudentEventController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

// Role picker. Signed-in users go straight to their dashboard.
Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : view('welcome'))->name('welcome');

Route::middleware('guest')->group(function () {
    // Login: /login?as=student|officer|faculty|admin
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Everyone: browse events, view one, join, leave, see your own tickets
    Route::get('/events', [HomeController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [StudentEventController::class, 'show'])->name('events.show');
    Route::post('/events/{event}/join', [RegistrationController::class, 'store'])->name('events.join');
    Route::delete('/events/{event}/leave', [RegistrationController::class, 'destroy'])->name('events.leave');
    Route::get('/my-events', [RegistrationController::class, 'index'])->name('registrations.index');

    // Request letters: officers (their own org) and admins
    Route::middleware(EnsureRole::class . ':officer,admin')->group(function () {
        Route::get('/letters/{letter}', [LetterController::class, 'show'])->name('letters.show');
        Route::get('/letters/{letter}/attachment', [LetterController::class, 'attachment'])->name('letters.attachment');
    });

    // Attendance: officers manage their org's events. Admins and faculty can view and print.
    Route::middleware(EnsureRole::class . ':officer,admin,faculty')->group(function () {
        Route::get('/events/{event}/attendance', [AttendanceController::class, 'show'])->name('attendance.show');
        Route::get('/events/{event}/attendance/print', [AttendanceController::class, 'print'])->name('attendance.print');
        Route::post('/events/{event}/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.checkin');
        Route::post('/events/{event}/attendance/scan', [AttendanceController::class, 'scan'])->name('attendance.scan');
        Route::patch('/events/{event}/attendance/{registration}', [AttendanceController::class, 'toggle'])->name('attendance.toggle');
    });

    // Org officers: request and manage their org's events
    Route::middleware(EnsureRole::class . ':officer')
        ->prefix('officer')->name('officer.')->group(function () {
            Route::get('/events', [OfficerEventController::class, 'index'])->name('events.index');
            Route::get('/events/create', [OfficerEventController::class, 'create'])->name('events.create');
            Route::post('/events', [OfficerEventController::class, 'store'])->name('events.store');
            Route::patch('/events/{event}/cancel', [OfficerEventController::class, 'cancel'])->name('events.cancel');
        });

    // Admin: approve or decline requests, read letters, manage users
    Route::middleware(EnsureRole::class . ':admin')
        ->prefix('admin')->name('admin.')->group(function () {
            Route::get('/events', [EventReviewController::class, 'index'])->name('events.index');
            Route::patch('/events/{event}/approve', [EventReviewController::class, 'approve'])->name('events.approve');
            Route::patch('/events/{event}/decline', [EventReviewController::class, 'decline'])->name('events.decline');

            Route::get('/letters', [LetterController::class, 'index'])->name('letters.index');

            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/faculty/create', [UserController::class, 'createFaculty'])->name('users.faculty.create');
            Route::post('/users/faculty', [UserController::class, 'storeFaculty'])->name('users.faculty.store');
            Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
            Route::get('/users/{user}/cor', [UserController::class, 'cor'])->name('users.cor');
        });
});
