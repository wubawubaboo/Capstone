<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\User;
use App\Models\SystemLog;
use App\Models\Report;
use App\Models\DocumentRequest;
use App\Models\Barangay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function analytics()
    {
        $incidentTrends = Report::selectRaw('incident_type as type, count(*) as total')->groupBy('incident_type')->get();
        $transactionVolumes = DocumentRequest::selectRaw('status, count(*) as total')->groupBy('status')->get();

        return Inertia::render('Admin/Analytics', [
            'incidentTrends' => $incidentTrends,
            'transactionVolumes' => $transactionVolumes
        ]);
    }

    /** Roles manageable from the admin Account Management page. */
    private const MANAGEABLE_ROLES = ['secretary', 'vawc_officer', 'admin'];

    public function accounts()
    {
        $staffAccounts = User::with('barangay')->whereIn('role', self::MANAGEABLE_ROLES)->get();
        $barangays = Barangay::orderBy('name')->get();

        return Inertia::render('Admin/AccountManagement', [
            'staffAccounts' => $staffAccounts,
            'barangays' => $barangays
        ]);
    }

    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|string|unique:users,phone_number',
            'barangay_id' => 'nullable|required_unless:role,admin|exists:barangays,id',
            'role' => 'required|in:secretary,vawc_officer,admin',
            'password' => 'required|min:8',
            'address' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'start_of_residency' => 'required|integer|min:1900|max:' . date('Y'),
        ]);

        $account = User::create([
            'full_name' => $validated['full_name'],
            'phone_number' => $validated['phone_number'],
            'barangay_id' => $validated['role'] === 'admin' ? null : $validated['barangay_id'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'is_verified' => true,
            'address' => $validated['address'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'start_of_residency' => $validated['start_of_residency'] ?? null,
        ]);

        SystemLog::logAction(
            $account->barangay_id,
            $request->user()->id,
            'CREATE',
            'Account',
            "Created {$account->role} account for {$account->full_name}."
        );

        return redirect()->back()->with('success', 'Administrative account created successfully.');
    }

    public function updateAccount(Request $request, User $user)
    {
        abort_unless(in_array($user->role, self::MANAGEABLE_ROLES), 404);

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|string|unique:users,phone_number,' . $user->id,
            'barangay_id' => 'nullable|required_unless:role,admin|exists:barangays,id',
            'role' => 'required|in:secretary,vawc_officer,admin',
            'password' => 'nullable|min:8',
            'address' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'start_of_residency' => 'required|integer|min:1900|max:' . date('Y'),
        ]);

        $updates = [
            'full_name' => $validated['full_name'],
            'phone_number' => $validated['phone_number'],
            'barangay_id' => $validated['role'] === 'admin' ? null : $validated['barangay_id'],
            'role' => $validated['role'],
            'address' => $validated['address'],
            'date_of_birth' => $validated['date_of_birth'],
            'start_of_residency' => $validated['start_of_residency'],
        ];

        if (!empty($validated['password'])) {
            $updates['password'] = Hash::make($validated['password']);
        }

        $user->update($updates);

        SystemLog::logAction(
            $user->barangay_id,
            $request->user()->id,
            'UPDATE',
            'Account',
            "Updated {$user->role} account for {$user->full_name}."
        );

        return redirect()->back()->with('success', 'Administrative account updated successfully.');
    }

    public function destroyAccount(Request $request, User $user)
    {
        abort_unless(in_array($user->role, self::MANAGEABLE_ROLES), 404);

        if ($user->id === $request->user()->id) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $name = $user->full_name;
        $role = $user->role;
        $barangayId = $user->barangay_id;
        $user->delete();

        SystemLog::logAction(
            $barangayId,
            $request->user()->id,
            'DELETE',
            'Account',
            "Deleted {$role} account for {$name}."
        );

        return redirect()->back()->with('success', 'Account deleted successfully.');
    }

    public function auditLogs()
    {
        $logs = SystemLog::with('user')->latest()->paginate(50);
        
        return Inertia::render('Admin/AuditLogs', [
            'logs' => $logs
        ]);
    }
}