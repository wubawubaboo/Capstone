<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Actions\Account\ApproveResidentAccount;
use App\Actions\Account\RejectResidentAccount;
use App\Http\Requests\Account\RejectAccountRequest;
use App\Http\Requests\Account\StorePoliceRequest;
use App\Http\Requests\Account\UpdateBarangayAccountRequest;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\SecureFileStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function __construct(private SecureFileStore $secureFiles)
    {
    }

    public function index()
    {
        $barangayId = Auth::user()->barangay_id;

        $residents = fn () => User::byBarangay($barangayId)->where('role', Role::Resident)->latest();

        return Inertia::render('Secretary/AccountRequests', [
            'pendingResidents' => $residents()->where('is_verified', false)
                ->paginate(self::PER_PAGE, ['*'], 'pending_page')->withQueryString(),
            'verifiedResidents' => $residents()->where('is_verified', true)
                ->paginate(self::PER_PAGE, ['*'], 'verified_page')->withQueryString(),
            'policeAccounts' => User::byBarangay($barangayId)->where('role', Role::BarangayPolice)->latest()
                ->paginate(self::PER_PAGE, ['*'], 'police_page')->withQueryString(),
        ]);
    }

    public function approve(Request $request, User $user, ApproveResidentAccount $approveResidentAccount)
    {
        abort_unless($request->user()->can('manageResident', $user) && $user->isPendingVerification(), 404);

        $approveResidentAccount($user);

        return back()->with('success', 'Account approved. The resident will be notified by SMS.');
    }

    public function reject(RejectAccountRequest $request, User $user, RejectResidentAccount $rejectResidentAccount)
    {
        abort_unless($request->user()->can('manageResident', $user) && $user->isPendingVerification(), 404);

        $validated = $request->validated();

        $rejectResidentAccount($user, $validated['reason'], $validated['custom_message'] ?? null);

        return back()->with('success', 'Account rejected and removed. The resident will be notified by SMS.');
    }

    public function showIdPhoto(Request $request, User $user)
    {
        return $this->verificationDocument($request, $user, $user->id_photo_path, 'ID photo');
    }

    public function showSelfiePhoto(Request $request, User $user)
    {
        return $this->verificationDocument($request, $user, $user->selfie_id_path, 'selfie ID photo');
    }

    public function storePolice(StorePoliceRequest $request)
    {
        $validated = $request->validated();

        $officer = User::create([
            'full_name' => $validated['name'],
            'phone_number' => $validated['phone_number'],
            'password' => $validated['password'],
            'role' => Role::BarangayPolice,
            'barangay_id' => $request->user()->barangay_id,
            'address' => $validated['address'],
            'date_of_birth' => $validated['date_of_birth'],
            'start_of_residency' => $validated['start_of_residency'],
            'is_verified' => true,
        ]);

        SystemLog::record('CREATE', 'Account', "Created Barangay Police account for {$officer->full_name}.");

        return back()->with('success', 'Barangay Police account created successfully.');
    }

    public function updatePolice(UpdateBarangayAccountRequest $request, User $user)
    {
        return $this->updateAccount($request, $user, 'managePolice', 'Barangay Police');
    }

    public function destroyPolice(Request $request, User $user)
    {
        return $this->destroyAccount($request, $user, 'managePolice', 'Barangay Police');
    }

    public function updateResident(UpdateBarangayAccountRequest $request, User $user)
    {
        return $this->updateAccount($request, $user, 'manageResident', 'Resident');
    }

    public function destroyResident(Request $request, User $user)
    {
        return $this->destroyAccount($request, $user, 'manageResident', 'Resident');
    }

    private function verificationDocument(Request $request, User $user, ?string $path, string $label)
    {
        abort_unless($request->user()->can('viewVerificationDocuments', $user), 403, 'Unauthorized access.');
        abort_unless($this->secureFiles->exists($path), 404, ucfirst($label) . ' not found.');

        SystemLog::record('VIEW', 'Account', "Viewed {$label} for {$user->full_name}.", $user->barangay_id);

        return $this->secureFiles->response($path);
    }

    private function updateAccount(UpdateBarangayAccountRequest $request, User $user, string $ability, string $label)
    {
        abort_unless($request->user()->can($ability, $user), 404);

        $user->update($request->validated());

        SystemLog::record('UPDATE', 'Account', "Updated {$label} account for {$user->full_name}.", $user->barangay_id);

        return back()->with('success', "{$label} account updated.");
    }

    private function destroyAccount(Request $request, User $user, string $ability, string $label)
    {
        abort_unless($request->user()->can($ability, $user), 404);

        DB::transaction(function () use ($user, $label) {
            SystemLog::record('DELETE', 'Account', "Deleted {$label} account for {$user->full_name}.", $user->barangay_id);
            $user->delete();
        });

        $user->deleteVerificationDocuments();

        return back()->with('success', "{$label} account deleted.");
    }
}
