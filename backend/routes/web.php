<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\UserController;

// Public routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Logout route
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/subscriptions', [AdminController::class, 'subscriptions'])->name('subscriptions');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
});

// User routes
Route::middleware(['auth', 'user'])->prefix('user')->name('user.')->group(function () {
    Route::get('/', [UserController::class, 'dashboard'])->name('dashboard');
    Route::get('/conversations', [UserController::class, 'conversations'])->name('conversations');
    Route::get('/contacts', [UserController::class, 'contacts'])->name('contacts');
    Route::get('/campaigns', [UserController::class, 'campaigns'])->name('campaigns');
    Route::get('/whatsapp', [UserController::class, 'whatsapp'])->name('whatsapp');
    Route::get('/chatbots', [UserController::class, 'chatbots'])->name('chatbots');
    Route::get('/analytics', [UserController::class, 'analytics'])->name('analytics');
    Route::get('/settings', [UserController::class, 'settings'])->name('settings');
});

// Root redirect based on user role
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if (in_array($user->role, ['super_admin', 'admin'])) {
            return redirect()->route('admin.dashboard');
        } else {
            return redirect()->route('user.dashboard');
        }
    }
    return redirect()->route('login');
});
