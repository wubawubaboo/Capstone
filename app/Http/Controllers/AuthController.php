<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\User;
use App\Services\PhilSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AuthController extends Controller
{
    protected $smsService;
    /**
     * Helper method to redirect authenticated users based on their role.
     */
    
    public function __construct(PhilSmsService $smsService)
    {
        $this->smsService = $smsService;
    }
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
            'id_photo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:5120'], 
            'selfie_id_photo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ]);

        $idPath = $request->file('id_photo')->store('id_photos');
        $selfiePath = $request->file('selfie_id_photo')->store('id_photos/selfies'); // Added this line

        User::create([
            'full_name' => $request->name,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'role' => 'resident',
            'barangay_id' => $request->barangay_id,
            'id_photo_path' => $idPath,
            'selfie_id_photo_path' => $selfiePath,
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
        $role = Auth::user()->role ?? 'resident';

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (in_array($role, ['secretary', 'vawc', 'admin'])) {
            return to_route('/portal/secure-login');
        }

        return to_route('/');
    }

    public function accountRequests()
    {
        $requests = User::where('role', 'resident')
            ->where('is_verified', false)
            ->with('barangay')
            ->latest()
            ->paginate(15);

        return Inertia::render('Secretary/AccountRequests', [
            'requests' => $requests
        ]);
    }

    public function approveAccount(User $user, PhilSmsService $smsService)
    {

        $user->update(['is_verified' => true]);

        $message = "Your account has been approved. You may now log in to the portal and access our services.";
        $smsService->sendSms($user->phone_number, $message);

        return back()->with('success', 'Account approved successfully and SMS sent.');
    }

    public function rejectAccount(Request $request, User $user, PhilSmsService $smsService)
    {
        $validated = $request->validate([
            'reason' => 'required|string|in:Blurry ID,Mismatched Information,Invalid ID,Expired ID',
            'custom_message' => 'nullable|string|max:150'
        ]);

        $message = "Your account verification was declined. Reason: " . $validated['reason'] . ".";
        
        if (!empty($validated['custom_message'])) {
            $message .= " " . $validated['custom_message'];
        }
        
        $message .= " Please register again with valid information.";

        $smsService->sendSms($user->phone_number, $message);

        if ($user->id_photo_path) {
            Storage::disk('public')->delete($user->id_photo_path);
        }
        $user->delete();

        return back()->with('success', 'Account rejected, removed, and SMS sent to the resident.');
    }

    public function showIdPhoto(User $user)
    {
        if (Auth::user()->role !== 'secretary') {
            abort(403, 'Unauthorized access.');
        }

        if (!$user->id_photo_path || !Storage::exists($user->id_photo_path)) {
            abort(404, 'ID photo not found.');
        }

        return Storage::response($user->id_photo_path);
    }
}