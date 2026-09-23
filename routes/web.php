<?php

use App\Http\Controllers\BarangayController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlotterController;
use App\Http\Controllers\DocumentRequestController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\VawcController;
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

Route::get('/hotlines', function () {return Inertia::render('Public/Hotlines');})->name('hotlines');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/register', [AuthController::class, 'showRegistration'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::get('/portal/secure-login', [AuthController::class, 'showStaffLogin'])->name('staff.login');
Route::post('/portal/secure-login', [AuthController::class, 'staffLogin']);

Route::middleware('auth')->prefix('resident')->name('resident.')->group(function () {
    
    Route::group(['middleware' => function ($request, $next) {
        if ($request->user()->role !== 'resident') {
            abort(403, 'Unauthorized action.');
        }
        return $next($request);
    }], function () {
        Route::get('/home', function () {return Inertia::render('Resident/Home');})->name('home');
        Route::get('/profile', [ReportController::class, 'profile'])->name('profile');
        Route::get('/resident/tracking', [ReportController::class, 'tracking'])->name('tracking');
        Route::get('/emergency-report', function () {return Inertia::render('Resident/EmergencyReport');})->name('reports.create');
        Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
        Route::post('/sos-trigger', [ReportController::class, 'storeEmergency'])->name('sos.trigger');
        Route::get('/document-request', [DocumentRequestController::class, 'create'])->name('documents.create');
        Route::post('/document-request', [DocumentRequestController::class, 'store'])->name('documents.store');
        Route::get('/service-request', function () {return Inertia::render('Resident/ServiceRequest');})->name('services.create');
        Route::post('/service-request', [ServiceRequestController::class, 'store'])->name('services.store');
        Route::get('/reports/{report}/attachment', [ReportController::class, 'showAttachment'])->name('reports.attachment');
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
        Route::get('/', function () { return Inertia::render('Public/LandingPage'); })->name('landing');
        Route::get('/hotlines', function () { return Inertia::render('Public/Hotlines'); })->name('hotlines');
        Route::get('/document-requests', [DocumentRequestController::class, 'index'])->name('document-requests');
        Route::get('/document-requests/export', [DocumentRequestController::class, 'export'])->name('document-requests.export');
        Route::post('/document-requests/{documentRequest}/status', [DocumentRequestController::class, 'updateStatus'])->name('document-requests.update-status');
        Route::get('/document-requests/{documentRequest}/generate', [DocumentRequestController::class, 'generate'])->name('document-requests.generate');
        Route::post('/document-types', [DocumentTypeController::class, 'store'])->name('document-types.store');
        Route::put('/document-types/{documentType}', [DocumentTypeController::class, 'update'])->name('document-types.update');
        Route::delete('/document-types/{documentType}', [DocumentTypeController::class, 'destroy'])->name('document-types.destroy');
        Route::post('/document-types/{documentType}/toggle-active', [DocumentTypeController::class, 'toggleActive'])->name('document-types.toggle-active');
        Route::put('/document-types/{documentType}/field-positions', [DocumentTypeController::class, 'updateFieldPositions'])->name('document-types.field-positions');
        Route::get('/document-types/{documentType}/template-file', [DocumentTypeController::class, 'showTemplateFile'])->name('document-types.template-file');
        Route::delete('/document-types/{documentType}/template', [DocumentTypeController::class, 'destroyTemplate'])->name('document-types.destroy-template');
        Route::get('/service-requests', [ServiceRequestController::class, 'index'])->name('service-requests');
        Route::get('/service-requests/export', [ServiceRequestController::class, 'export'])->name('service-requests.export');
        Route::post('/service-requests/{serviceRequest}/assign', [ServiceRequestController::class, 'assignAsset'])->name('service-requests.assign');
        Route::post('/service-requests/{serviceRequest}/complete', [ServiceRequestController::class, 'complete'])->name('service-requests.complete');
        Route::get('/blotter-management', [BlotterController::class, 'index'])->name('blotters');
        Route::get('/blotter-management/export', [BlotterController::class, 'export'])->name('blotters.export');
        Route::post('/blotters/{blotter}/schedule-mediation', [BlotterController::class, 'scheduleMediation'])->name('blotters.schedule-mediation');
        Route::post('/blotters/{blotter}/vawc-detail', [BlotterController::class, 'storeVawcDetail'])->name('blotters.store-vawc');
        Route::get('/blotters/create', [BlotterController::class, 'create'])->name('blotters.create');
        Route::post('/blotters', [BlotterController::class, 'store'])->name('blotters.store');
        Route::get('/residents/search', [BlotterController::class, 'searchResidents'])->name('residents.search');
        Route::post('/cases/{blotter}/resolve', [BlotterController::class, 'resolveCase'])->name('cases.resolve');
        Route::post('/cases/{blotter}/escalate', [BlotterController::class, 'escalateCase'])->name('cases.escalate');
        Route::post('/cases/{blotter}/reopen', [BlotterController::class, 'reopenCase'])->name('cases.reopen');
        Route::get('/reports', [ReportController::class, 'secretaryIndex'])->name('reports');
        Route::get('/reports/export', [ReportController::class, 'exportSecretary'])->name('reports.export');
        Route::put('/reports/{report}/update-status', [ReportController::class, 'updateStatus'])->name('reports.update-status');
        Route::post('/reports/{report}/acknowledge', [ReportController::class, 'acknowledge'])->name('reports.acknowledge');
        Route::get('/reports/{report}/attachment', [ReportController::class, 'showAttachment'])->name('reports.attachment');
        Route::get('/case-history/{blotter}', [BlotterController::class, 'caseHistory'])->name('case-history');
        Route::get('/case-history/{blotter}/report', [BlotterController::class, 'downloadCaseReport'])->name('case-history.report');
        Route::get('/mediation-calendar', [BlotterController::class, 'mediationCalendar'])->name('mediation-calendar');
        Route::get('/mediation-meeting/{blotter}', [BlotterController::class, 'mediationMeetingDetails'])->name('mediation-meeting-details');
        Route::put('/mediation/{mediation}/notes', [BlotterController::class, 'updateMediationNotes'])->name('mediation-notes.update');
        Route::post('/cases/{blotter}/schedule-mediation', [BlotterController::class, 'scheduleMediation'])->name('cases.schedule-mediation');
        Route::get('/account-requests', [AuthController::class, 'accountRequests'])->name('account-requests');
        Route::post('/account-requests/{user}/approve', [AuthController::class, 'approveAccount'])->name('account-requests.approve');
        Route::post('/account-requests/{user}/reject', [AuthController::class, 'rejectAccount'])->name('account-requests.reject');
        Route::get('/account-requests/{user}/id-photo', [AuthController::class, 'showIdPhoto'])->name('account-requests.id-photo');
        Route::get('/account-requests/{user}/selfie-photo', [AuthController::class, 'showSelfiePhoto'])->name('account-requests.selfie-photo');
        Route::put('/resident-accounts/{user}', [AuthController::class, 'updateResident'])->name('resident.update');
        Route::delete('/resident-accounts/{user}', [AuthController::class, 'destroyResident'])->name('resident.destroy');
        Route::get('/assets', [AssetController::class, 'index'])->name('assets');
        Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
        Route::patch('/assets/{asset}/toggle', [AssetController::class, 'toggleAvailability'])->name('assets.toggle');
        Route::delete('/assets/{asset}/archive', [AssetController::class, 'archive'])->name('assets.archive');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('/police-accounts', [AuthController::class, 'storePolice'])->name('police.store');
        Route::put('/police-accounts/{user}', [AuthController::class, 'updatePolice'])->name('police.update');
        Route::delete('/police-accounts/{user}', [AuthController::class, 'destroyPolice'])->name('police.destroy');
        Route::get('/barangay-profile', [BarangayController::class, 'editProfile'])->name('barangay.edit');
        Route::put('/barangay-profile', [BarangayController::class, 'updateProfile'])->name('barangay.update');
    });
});

Route::middleware('auth')->prefix('vawc')->name('vawc.')->group(function () {
    Route::group(['middleware' => function ($request, $next) {
        if ($request->user()->role !== 'vawc') {
            abort(403, 'Unauthorized action.');
        }
        return $next($request);
    }], function () {
        Route::get('/analytics', [VawcController::class, 'analytics'])->name('analytics');
        Route::get('/blotter-management', [VawcController::class, 'index'])->name('blotters');
        Route::get('/blotters/create', [VawcController::class, 'create'])->name('blotters.create');
        Route::post('/blotters', [VawcController::class, 'store'])->name('blotters.store');
        Route::get('/residents/search', [VawcController::class, 'searchResidents'])->name('residents.search');
        Route::get('/case-history/{blotter}', [VawcController::class, 'caseHistory'])->name('case-history');
        Route::get('/case-history/{blotter}/report', [VawcController::class, 'downloadCaseReport'])->name('case-history.report');
        Route::get('/mediation-calendar', [VawcController::class, 'mediationCalendar'])->name('mediation-calendar');
        Route::post('/cases/{blotter}/schedule-mediation', [VawcController::class, 'scheduleMediation'])->name('cases.schedule-mediation');
        Route::put('/mediation/{mediation}/notes', [VawcController::class, 'updateMediationNotes'])->name('mediation-notes.update');
        Route::post('/cases/{blotter}/resolve', [VawcController::class, 'resolveCase'])->name('cases.resolve');
        Route::post('/cases/{blotter}/escalate', [VawcController::class, 'escalateCase'])->name('cases.escalate');
        Route::post('/cases/{blotter}/reopen', [VawcController::class, 'reopenCase'])->name('cases.reopen');
        Route::get('/reports', [ReportController::class, 'vawcIndex'])->name('reports');
        Route::put('/reports/{report}/update-status', [ReportController::class, 'updateStatus'])->name('reports.update-status');
        Route::post('/reports/{report}/acknowledge', [ReportController::class, 'acknowledge'])->name('reports.acknowledge');
        Route::get('/reports/{report}/attachment', [ReportController::class, 'showAttachment'])->name('reports.attachment');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::group(['middleware' => function ($request, $next) {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }
        return $next($request);
    }], function () {
        Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');
        Route::get('/accounts', [AdminController::class, 'accounts'])->name('accounts');
        Route::post('/accounts', [AdminController::class, 'storeAccount'])->name('accounts.store');
        Route::put('/accounts/{user}', [AdminController::class, 'updateAccount'])->name('accounts.update');
        Route::delete('/accounts/{user}', [AdminController::class, 'destroyAccount'])->name('accounts.destroy');
        Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('logs');
        Route::resource('barangays', BarangayController::class)->except(['create', 'show', 'edit']);
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});