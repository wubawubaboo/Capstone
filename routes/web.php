<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::get('/', function () {
    if (Auth::check()) {
        if (Auth::user()->role === 'resident') {
            return redirect('/resident/home'); 
        }
    }
    return Inertia::render('Public/LandingPage');
})->name('landing');

Route::get('/hotlines', function () {
    return Inertia::render('Public/Hotlines');
})->name('hotlines');


Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::get('/register', [AuthController::class, 'showRegistration'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware(['auth', 'role:resident'])->group(function () {
    Route::get('/resident/emergency-report', function () {
        return Inertia::render('Resident/EmergencyReport');
    })->name('reports.create');
    
});