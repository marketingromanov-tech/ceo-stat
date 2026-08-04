<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Livewire\RobotDetail;
use App\Livewire\RobotManager;
use App\Livewire\UserManager;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard-data', [DashboardController::class, 'data'])->name('dashboard.data');
    Route::get('/robots', RobotManager::class)->middleware('role:admin,operator,viewer')->name('robots.index');
    Route::get('/robots/{robot}', RobotDetail::class)->name('robots.show');
    Route::get('/users', UserManager::class)->middleware('role:admin')->name('users.index');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
