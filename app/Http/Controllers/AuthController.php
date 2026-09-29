<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Barangay;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\SecureFileStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(LoginRequest $request)
    {
        $user = $request->authenticate();

        if (!$user->isResident()) {
            $this->rejectLogin('Staff members must use the secure staff portal login.');
        }

        if (!$user->is_verified) {
            $this->rejectLogin('Your account is still pending ID verification by the Secretary.');
        }

        return $this->startSession($request, $user);
    }

    public function showStaffLogin()
    {
        return Inertia::render('Auth/StaffLogin');
    }

    public function staffLogin(LoginRequest $request)
    {
        $user = $request->authenticate();

        if ($user->isResident()) {
            $this->rejectLogin('Residents must use the public login page.');
        }

        if (!$user->usesStaffPortal()) {
            $this->rejectLogin('This account receives SOS alerts by SMS and does not have portal access.');
        }

        return $this->startSession($request, $user);
    }

    public function showRegistration()
    {
        return Inertia::render('Auth/Registration', [
            'barangays' => Barangay::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function register(RegisterRequest $request, SecureFileStore $secureFiles)
    {
        $validated = $request->validated();
        $idPhotoPath = $secureFiles->store($request->file('id_photo'), 'id_photos');
        $selfiePath = $secureFiles->store($request->file('selfie_id_photo'), 'id_photos/selfies');

        DB::transaction(function () use ($validated, $idPhotoPath, $selfiePath) {
            $user = User::create([
                'full_name' => $validated['name'],
                'phone_number' => $validated['phone_number'],
                'password' => $validated['password'],
                'role' => Role::Resident,
                'barangay_id' => $validated['barangay_id'],
                'address' => $validated['address'],
                'date_of_birth' => $validated['date_of_birth'],
                'start_of_residency' => $validated['start_of_residency'],
                'id_photo_path' => $idPhotoPath,
                'selfie_id_path' => $selfiePath,
                'is_verified' => false,
            ]);

            // Nobody is signed in yet, so the new resident is the actor.
            SystemLog::logAction($user->barangay_id, $user->id, 'CREATE', 'Account', "Self-registered resident account for {$user->full_name}; awaiting ID verification.");
        });

        return to_route('login')->with('success', 'Registration submitted successfully! Please wait for the Secretary to verify your ID before logging in.');
    }

    public function logout(Request $request)
    {
        $backToStaffPortal = $request->user()?->usesStaffPortal() ?? false;

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $backToStaffPortal ? to_route('staff.login') : to_route('landing');
    }

    private function startSession(Request $request, User $user)
    {
        Auth::login($user);
        $request->session()->regenerate();

        return to_route($user->homeRoute());
    }

    /** Credentials were valid, but this account may not sign in here. */
    private function rejectLogin(string $message): never
    {
        throw ValidationException::withMessages(['phone_number' => $message]);
    }
}
