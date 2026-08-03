<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Livewire\AccountManager;
use App\Livewire\RobotManager;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/robots', RobotManager::class)->middleware('role:admin,operator')->name('robots.index');
    Route::get('/accounts', AccountManager::class)->middleware('role:admin,operator')->name('accounts.index');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
