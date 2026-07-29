<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AuthController extends Controller
{
    /**
     * Helper method to redirect authenticated users based on their role.
     */
    private function redirectBasedOnRole()
    {
        return match (Auth::user()->role) {
            'resident'  => to_route('resident.home'),
            'admin'     => to_route('admin.analytics'),
            'secretary' => to_route('secretary.analytics'),
            'vawc'      => to_route('vawc.analytics'),
            default     => to_route('landing'),
        };
    }

    public function showLogin()
    {
        if (Auth::check()) return $this->redirectBasedOnRole();
        
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        if (Auth::check()) return $this->redirectBasedOnRole();

        $credentials = $request->validate([
            'phone_number' => ['required', 'string'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();
            
            if ($user->role !== 'resident') {
                Auth::logout();
                return back()->withErrors([
                    'phone_number' => 'Staff members must use the secure staff portal login.',
                ]);
            }
            return to_route('resident.home');
        }

        return back()->withErrors([
            'phone_number' => 'The provided phone number or password does not match our records.',
        ])->onlyInput('phone_number');
    }

    public function showStaffLogin()
    {
        if (Auth::check()) return $this->redirectBasedOnRole();

        return Inertia::render('Auth/StaffLogin');
    }

    public function staffLogin(Request $request)
    {
        if (Auth::check()) return $this->redirectBasedOnRole();

        $credentials = $request->validate([
            'phone_number' => ['required', 'string'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($user->role === 'resident') {
                Auth::logout();
                return back()->withErrors([
                    'phone_number' => 'Residents must use the public login page.',
                ]);
            }

            $request->session()->regenerate();

            return $this->redirectBasedOnRole();
        }

        return back()->withErrors(['phone_number' => 'Invalid staff credentials.']);
    }

    public function showRegistration() 
    {
        if (Auth::check()) return $this->redirectBasedOnRole();

        return Inertia::render('Auth/Registration', [
            'barangays' => Barangay::all()
        ]);
    }

    public function register(Request $request)
    {
        if (Auth::check()) return $this->redirectBasedOnRole();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:11', 'unique:users'], 
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'barangay_id' => ['required', 'exists:barangays,id'],
        ]);

        $user = User::create([
            'full_name' => $request->name,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'role' => 'resident',
            'barangay_id' => $request->barangay_id,
        ]);

        Auth::login($user);

        return to_route('resident.home');
    }

    public function showStaffRegistration() 
    {
        if (Auth::check()) return $this->redirectBasedOnRole();

        return Inertia::render('Auth/StaffRegistration', [
            'barangays' => Barangay::all(),
        ]);
    }

    public function staffRegister(Request $request)
    {
        if (Auth::check()) return $this->redirectBasedOnRole();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:11', 'unique:users'], 
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'barangay_id' => ['required', 'exists:barangays,id'],
            'role' => ['required'],
        ]);

        $user = User::create([
            'full_name' => $request->name,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'barangay_id' => $request->barangay_id,
        ]);

        Auth::login($user);
        
        return $this->redirectBasedOnRole();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('landing');
    }

    public function accountRequests()
    {
        $requests = User::where('role', 'resident')
            ->latest()
            ->paginate(15);

        return Inertia::render('Secretary/AccountRequests', [
            'requests' => $requests
        ]);
    }
}