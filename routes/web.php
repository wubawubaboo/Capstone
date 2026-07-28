<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::get('/', function () {
    return Inertia::render('Public/LandingPage');
})->name('landing');

Route::get('/hotlines', function () {
    return Inertia::render('Public/Hotlines');
})->name('hotlines');


// AUTHENTICATION ROUTES
Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login');

Route::get('/register', function () {
    return Inertia::render('Auth/Registration');
})->name('register');

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