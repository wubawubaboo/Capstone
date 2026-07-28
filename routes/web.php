<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::get('/', function () {
    return Inertia::render('Public/LandingPage');
})->name('landing');

Route::get('/hotlines', function () {
    return Inertia::render('Public/Hotlines');
})->name('hotlines');


// AUTHENTICATION ROUTES
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::get('/register', [AuthController::class, 'showRegistration'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Protected Routes (Require Authentication)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Example protected dashboard route
    Route::get('/dashboard', function () {
        return Inertia::render('Resident/Home');
    })->name('dashboard');
});

Route::prefix('resident')->group(function () {
    
    Route::get('/home', function () {
        return Inertia::render('Resident/Home');
    })->name('resident.home');

    Route::get('/profile', function () {
        return Inertia::render('Resident/Profile');
    })->name('resident.profile');

    Route::get('/document-request', function () {
        return Inertia::render('Resident/DocumentRequest');
    })->name('resident.documents');  
});

Route::prefix('secretary')->name('secretary.')->group(function () {
    // 1. Blotter Management Page
    Route::get('/blotter', function () {
        return Inertia::render('Secretary/BlotterManagement');
    })->name('blotter.index');

    // 2. Case History & Disposition (Schedule Mediation)
    Route::get('/blotter/{id}/history', function ($id) {
        return Inertia::render('Secretary/CaseHistory', ['caseId' => $id]);
    })->name('blotter.history');

    // 3. Detailed Mediation Meeting Page
    Route::get('/blotter/{id}/meeting/{meetingId}', function ($id, $meetingId) {
        return Inertia::render('Secretary/MediationMeetingDetails', [
            'caseId' => $id,
            'meetingId' => $meetingId,
        ]);
    })->name('blotter.meeting');

    // 4. Mediation Calendar Page
    Route::get('/mediation-calendar', function () {
        return Inertia::render('Secretary/MediationCalendar');
    })->name('calendar');

    // 5. Account Requests (Pending Verification)
    Route::get('/account-requests', function () {
        return Inertia::render('Secretary/AccountRequests');
    })->name('account-requests');

    // 6. Document Requests
    Route::get('/document-requests', function () {
        return Inertia::render('Secretary/DocumentRequests');
    })->name('document-requests');

    // 7. Administrative Analytics Dashboard
    Route::get('/analytics', function () {
        return Inertia::render('Secretary/Analytics');
    })->name('analytics');
});

Route::prefix('vawc')->name('vawc.')->group(function () {
    // 1. Blotter Management Page
    Route::get('/blotter', function () {
        return Inertia::render('VAWC/BlotterManagement');
    })->name('blotter.index');

    // 2. Case History & Disposition
    Route::get('/blotter/{id}/history', function ($id) {
        return Inertia::render('VAWC/CaseHistory', ['caseId' => $id]);
    })->name('blotter.history');

    // 3. Mediation Calendar Page
    Route::get('/mediation-calendar', function () {
        return Inertia::render('VAWC/MediationCalendar');
    })->name('calendar');

    // 4. Analytics Dashboard
    Route::get('/analytics', function () {
        return Inertia::render('VAWC/Analytics');
    })->name('analytics');
});