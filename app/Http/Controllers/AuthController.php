<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

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
            $user = Auth::user();
            
            // Check verification status
            if ($user->role === 'resident' && !$user->is_verified) {
                Auth::logout();
                return back()->withErrors([
                    'phone_number' => 'Your account is still pending ID verification by the Secretary.',
                ])->onlyInput('phone_number');
            }

            if ($user->role !== 'resident') {
                Auth::logout();
                return back()->withErrors([
                    'phone_number' => 'Staff members must use the secure staff portal login.',
                ]);
            }
            
            $request->session()->regenerate();
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
            'id_photo' => ['required', 'image', 'max:5120'], 
        ]);

        // FIX: Store privately (defaults to storage/app/id_photos)
        $path = $request->file('id_photo')->store('id_photos');

        User::create([
            'full_name' => $request->name,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'role' => 'resident',
            'barangay_id' => $request->barangay_id,
            'id_photo_path' => $path,
            'is_verified' => false,
        ]);

        return to_route('login')->withErrors([
            'success' => 'Registration submitted successfully! Please wait for the Secretary to verify your ID before logging in.'
        ]);
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
            'is_verified' => true, // Staff accounts are auto-verified
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
            ->where('is_verified', false) // Only fetch unverified accounts
            ->with('barangay')
            ->latest()
            ->paginate(15);

        return Inertia::render('Secretary/AccountRequests', [
            'requests' => $requests
        ]);
    }

    public function approveAccount(User $user)
    {
        $user->update(['is_verified' => true]);
        return back()->with('success', 'Account approved successfully.');
    }

    public function rejectAccount(User $user)
    {
        // Delete the ID photo from storage to save space
        if ($user->id_photo_path) {
            Storage::disk('public')->delete($user->id_photo_path);
        }
        $user->delete();
        return back()->with('success', 'Account rejected and removed.');
    }

    // NEW METHOD: Securely serve the ID photo
    public function showIdPhoto(User $user)
    {
        // Double-check authorization
        if (Auth::user()->role !== 'secretary') {
            abort(403, 'Unauthorized access.');
        }

        if (!$user->id_photo_path || !Storage::exists($user->id_photo_path)) {
            abort(404, 'ID photo not found.');
        }

        return Storage::response($user->id_photo_path);
    }
}