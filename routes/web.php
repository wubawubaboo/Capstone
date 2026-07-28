<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentRequestController;
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

    Route::get('/portal/secure-login', [AuthController::class, 'showStaffLogin'])->name('login');
    Route::post('/portal/secure-login', [AuthController::class, 'staffLogin']);
});

Route::middleware('auth')->prefix('resident')->group(function () {
    
    Route::group(['middleware' => function ($request, $next) {
        if ($request->user()->role !== 'resident') {
            abort(403, 'Unauthorized action.');
        }
        return $next($request);
    }], function () {
        Route::get('/home', function () {
            return Inertia::render('Resident/Home');
        })->name('home');

        Route::get('/profile', function () {
            return Inertia::render('Resident/Profile');
        })->name('profile');

        Route::get('/emergency-report', function () {
            return Inertia::render('Resident/EmergencyReport');
        })->name('reports.create');
        
        Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');

        Route::get('/document-request', function () {
            return Inertia::render('Resident/DocumentRequest');
        })->name('documents.create');
        
        Route::post('/document-request', [DocumentRequestController::class, 'store'])->name('documents.store');

        Route::get('/reports/{report}/attachment', [ReportController::class, 'showAttachment'])
            ->name('reports.attachment');

        Route::post('/logout', [AuthController::class, 'logout']);
            
    });
});

Route::middleware('auth')->prefix('secretary')->group(function () {
    
    Route::group(['middleware' => function ($request, $next) {
        if ($request->user()->role !== 'secretary') {
            abort(403, 'Unauthorized action.');
        }
        return $next($request);
    }], function () {


    });
});

Route::middleware('auth')->prefix('vawc')->group(function () {
    
    Route::group(['middleware' => function ($request, $next) {
        if ($request->user()->role !== 'vawc') {
            abort(403, 'Unauthorized action.');
        }
        return $next($request);
    }], function () {


    });
});

Route::middleware('auth')->prefix('admin')->group(function () {
    
    Route::group(['middleware' => function ($request, $next) {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }
        return $next($request);
    }], function () {


    });
});