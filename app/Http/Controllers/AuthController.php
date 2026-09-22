<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\RejectAccountRequest;
use App\Http\Requests\Auth\StorePoliceRequest;
use App\Http\Requests\Auth\UpdateStaffAccountRequest;
use App\Models\Barangay;
use App\Models\SystemLog;
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
  
    public function __construct(PhilSmsService $smsService)
    {
        $this->smsService = $smsService;
    }
    private function redirectBasedOnRole()
    {
        return match (Auth::user()->role) {
            'resident'        => to_route('resident.home'),
            'admin'           => to_route('admin.analytics'),
            'secretary'       => to_route('secretary.analytics'),
            'vawc'            => to_route('vawc.analytics'),
            'barangay_police' => to_route('resident.home'),
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

    public function register(RegisterRequest $request)
    {
        if (Auth::check()) return $this->redirectBasedOnRole();

        $idPath = $request->file('id_photo')->store('id_photos');
        $selfiePath = $request->file('selfie_id_photo')->store('id_photos/selfies');

        User::create([
            'full_name' => $request->name,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'role' => 'resident',
            'barangay_id' => $request->barangay_id,
            'address' => $request->address,
            'date_of_birth' => $request->date_of_birth,
            'start_of_residency' => $request->start_of_residency,
            'id_photo_path' => $idPath,
            'selfie_id_path' => $selfiePath,
            'is_verified' => false,
        ]);

        return to_route('login')->withErrors([
            'success' => 'Registration submitted successfully! Please wait for the Secretary to verify your ID before logging in.'
        ]);
    }

    public function logout(Request $request)
    {
        $role = Auth::user()->role ?? 'resident';

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (in_array($role, ['secretary', 'vawc', 'admin'])) {
            return to_route('staff.login');
        }

        return redirect('/');
    }

    public function accountRequests()
    {
        $barangayId = Auth::user()->barangay_id;

        $pendingResidents = User::where('role', 'resident')
            ->where('barangay_id', $barangayId)
            ->where('is_verified', false)
            ->latest()
            ->get(); 

        $verifiedResidents = User::where('role', 'resident')
            ->where('barangay_id', $barangayId)
            ->where('is_verified', true)
            ->latest()
            ->get();

        $policeAccounts = User::where('role', 'barangay_police')
            ->where('barangay_id', $barangayId)
            ->latest()
            ->get();

        return Inertia::render('Secretary/AccountRequests', [
            'pendingResidents' => $pendingResidents,
            'verifiedResidents' => $verifiedResidents,
            'policeAccounts' => $policeAccounts
        ]);
    }

    public function approveAccount(User $user, PhilSmsService $smsService)
    {
        abort_unless($user->barangay_id === Auth::user()->barangay_id, 404);
        abort_unless($user->role === 'resident' && !$user->is_verified, 404);

        $user->update(['is_verified' => true]);

        $message = "Your account has been approved. You may now log in to the portal and access our services.";
        $smsService->sendSms($user->phone_number, $message);

        SystemLog::record('UPDATE', 'Account', "Approved resident account for {$user->full_name}.", $user->barangay_id);

        return back()->with('success', 'Account approved successfully and SMS sent.');
    }

    public function rejectAccount(RejectAccountRequest $request, User $user, PhilSmsService $smsService)
    {
        abort_unless($user->barangay_id === Auth::user()->barangay_id, 404);
        abort_unless($user->role === 'resident' && !$user->is_verified, 404);

        $validated = $request->validated();

        $message = "Your account verification was declined. Reason: " . $validated['reason'] . ".";
        
        if (!empty($validated['custom_message'])) {
            $message .= " " . $validated['custom_message'];
        }
        
        $message .= " Please register again with valid information.";

        $smsService->sendSms($user->phone_number, $message);

        if ($user->id_photo_path) {
            Storage::delete($user->id_photo_path);
        }
        if ($user->selfie_id_path) {
            Storage::delete($user->selfie_id_path);
        }

        SystemLog::record('DELETE', 'Account', "Rejected and removed resident account for {$user->full_name}. Reason: {$validated['reason']}.", $user->barangay_id);

        $user->delete();

        return back()->with('success', 'Account rejected, removed, and SMS sent to the resident.');
    }

    public function showIdPhoto(User $user)
    {
        if (Auth::user()->role !== 'secretary' || $user->barangay_id !== Auth::user()->barangay_id || $user->role !== 'resident') {
            abort(403, 'Unauthorized access.');
        }

        if (!$user->id_photo_path || !Storage::exists($user->id_photo_path)) {
            abort(404, 'ID photo not found.');
        }

        SystemLog::record('VIEW', 'Account', "Viewed ID photo for {$user->full_name}.", $user->barangay_id);

        return Storage::response($user->id_photo_path);
    }
    public function showSelfiePhoto(User $user)
    {
        if (Auth::user()->role !== 'secretary' || $user->barangay_id !== Auth::user()->barangay_id || $user->role !== 'resident') {
            abort(403, 'Unauthorized access.');
        }

        if (!$user->selfie_id_path || !Storage::exists($user->selfie_id_path)) {
            abort(404, 'Selfie photo not found.');
        }

        SystemLog::record('VIEW', 'Account', "Viewed selfie ID photo for {$user->full_name}.", $user->barangay_id);

        return Storage::response($user->selfie_id_path);
    }

    public function storePolice(StorePoliceRequest $request)
    {
        $officer = User::create([
            'full_name' => $request->name,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'role' => 'barangay_police',
            'barangay_id' => Auth::user()->barangay_id,
            'address' => $request->address,
            'date_of_birth' => $request->date_of_birth,
            'start_of_residency' => $request->start_of_residency,
            'is_verified' => true,
        ]);

        SystemLog::record('CREATE', 'Account', "Created Barangay Police account for {$officer->full_name}.");

        return redirect()->back()->with('success', 'Barangay Police account created successfully.');
    }

    public function updatePolice(UpdateStaffAccountRequest $request, User $user)
    {
        abort_unless($user->barangay_id === Auth::user()->barangay_id, 404);
        abort_unless($user->role === 'barangay_police', 404);

        $user->update($request->validated());

        SystemLog::record('UPDATE', 'Account', "Updated Barangay Police account for {$user->full_name}.", $user->barangay_id);

        return redirect()->back()->with('success', 'Police account updated.');
    }

    public function destroyPolice(User $user)
    {
        abort_unless($user->barangay_id === Auth::user()->barangay_id, 404);
        abort_unless($user->role === 'barangay_police', 404);

        SystemLog::record('DELETE', 'Account', "Deleted Barangay Police account for {$user->full_name}.", $user->barangay_id);

        $user->delete();
        return redirect()->back()->with('success', 'Police account deleted.');
    }

    public function updateResident(UpdateStaffAccountRequest $request, User $user)
    {
        abort_unless($user->barangay_id === Auth::user()->barangay_id, 404);
        abort_unless($user->role === 'resident', 404);

        $user->update($request->validated());

        SystemLog::record('UPDATE', 'Account', "Updated resident account for {$user->full_name}.", $user->barangay_id);

        return redirect()->back()->with('success', 'Resident account updated.');
    }

    public function destroyResident(User $user)
    {
        abort_unless($user->barangay_id === Auth::user()->barangay_id, 404);
        abort_unless($user->role === 'resident', 404);

        SystemLog::record('DELETE', 'Account', "Deleted resident account for {$user->full_name}.", $user->barangay_id);

        if ($user->id_photo_path) Storage::delete($user->id_photo_path);
        if ($user->selfie_id_path) Storage::delete($user->selfie_id_path);
        
        $user->delete();
        return redirect()->back()->with('success', 'Resident account deleted.');
    }
}