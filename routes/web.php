<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlotterController;
use App\Http\Controllers\DocumentRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceRequestController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::get('/', function () {
    if (Auth::check()) {
        if (Auth::user()->role === 'resident') {
            return to_route('resident.home'); 
        }
    }
    return Inertia::render('Public/LandingPage');
})->name('landing');

Route::get('/hotlines', function () {
    return Inertia::render('Public/Hotlines');
})->name('hotlines');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/register', [AuthController::class, 'showRegistration'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::get('/portal/secure-login', [AuthController::class, 'showStaffLogin'])->name('staff.login');
Route::post('/portal/secure-login', [AuthController::class, 'staffLogin']);

Route::get('/portal/secure-register', [AuthController::class, 'showStaffRegistration'])->name('staff.register');
Route::post('/portal/secure-register', [AuthController::class, 'staffRegister']);

Route::middleware('auth')->prefix('resident')->name('resident.')->group(function () {
    
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

        Route::get('/service-request', function () {
            return Inertia::render('Resident/ServiceRequest');
        })->name('services.create');
        Route::post('/service-request', [ServiceRequestController::class, 'store'])->name('services.store');

        Route::get('/reports/{report}/attachment', [ReportController::class, 'showAttachment'])
            ->name('reports.attachment');

        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            
    });
});

Route::middleware('auth')->prefix('secretary')->name('secretary.')->group(function () {
    
    Route::group(['middleware' => function ($request, $next) {
        if ($request->user()->role !== 'secretary') {
            abort(403, 'Unauthorized action.');
        }
        return $next($request);
    }], function () {
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

        Route::get('/document-requests', [DocumentRequestController::class, 'index'])->name('document-requests');
        Route::post('/document-requests/{documentRequest}/status', [DocumentRequestController::class, 'updateStatus'])->name('document-requests.update-status');
        Route::get('/service-requests', [ServiceRequestController::class, 'index'])->name('service-requests');
        Route::post('/service-requests/{serviceRequest}/assign', [ServiceRequestController::class, 'assignAsset'])->name('service-requests.assign');
        Route::post('/service-requests/{serviceRequest}/complete', [ServiceRequestController::class, 'complete'])->name('service-requests.complete');

        Route::get('/blotter-management', [BlotterController::class, 'index'])->name('blotters');
        Route::post('/blotters/{blotter}/schedule-mediation', [BlotterController::class, 'scheduleMediation'])->name('blotters.schedule-mediation');
        Route::post('/blotters/{blotter}/vawc-detail', [BlotterController::class, 'storeVawcDetail'])->name('blotters.store-vawc');
        Route::get('/blotters/create', [BlotterController::class, 'create'])->name('blotters.create');
        Route::post('/blotters', [BlotterController::class, 'store'])->name('blotters.store');
        
        Route::get('/case-history/{id}', [BlotterController::class, 'caseHistory'])->name('case-history');

        Route::get('/mediation-calendar', [BlotterController::class, 'mediationCalendar'])->name('mediation-calendar');
        Route::get('/mediation-meeting/{id}', [BlotterController::class, 'mediationMeetingDetails'])->name('mediation-meeting-details');

        Route::get('/account-requests', [AuthController::class, 'accountRequests'])->name('account-requests');
        Route::post('/account-requests/{user}/approve', [AuthController::class, 'approveAccount'])->name('account-requests.approve');
        Route::post('/account-requests/{user}/reject', [AuthController::class, 'rejectAccount'])->name('account-requests.reject');
        Route::get('/account-requests/{user}/id-photo', [AuthController::class, 'showIdPhoto'])->name('account-requests.id-photo');

        Route::get('/assets', [AssetController::class, 'index'])->name('assets');
        Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
        Route::patch('/assets/{asset}/toggle', [AssetController::class, 'toggleAvailability'])->name('assets.toggle');
        Route::delete('/assets/{asset}/archive', [AssetController::class, 'archive'])->name('assets.archive');

        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});

Route::middleware('auth')->prefix('vawc')->name('vawc.')->group(function () {
    Route::group(['middleware' => function ($request, $next) {
        if ($request->user()->role !== 'vawc') {
            abort(403, 'Unauthorized action.');
        }
        return $next($request);
    }], function () {
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    });
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::group(['middleware' => function ($request, $next) {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }
        return $next($request);
    }], function () {
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    });
});