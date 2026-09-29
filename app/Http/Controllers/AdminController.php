<?php

namespace App\Http\Controllers;

use App\Enums\CaseDesk;
use App\Enums\Role;
use Inertia\Inertia;
use App\Http\Requests\Admin\StoreStaffAccountRequest;
use App\Http\Requests\Admin\UpdateStaffAccountRequest;
use App\Models\User;
use App\Models\SystemLog;
use App\Models\Report;
use App\Models\BlotterRecord;
use App\Models\Barangay;
use App\Services\IncidentAnalytics;
use App\Support\AnalyticsPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Citywide incident monitoring, optionally narrowed to one barangay with
     * `?barangay=`. VAWC-flagged reports and VAWC cases are left out entirely:
     * they are confidential to each barangay's VAWC desk.
     */
    public function analytics(Request $request, IncidentAnalytics $analytics)
    {
        $barangay = Barangay::find($request->integer('barangay') ?: null);

        $reports = Report::where('is_vawc', false)
            ->when($barangay, fn ($query) => $query->forBarangay($barangay->id));
        $cases = CaseDesk::Secretary->scopeCases(BlotterRecord::query())
            ->when($barangay, fn ($query) => $query->forBarangay($barangay->id));

        return Inertia::render('Admin/Analytics', [
            'dashboard' => $analytics->dashboard($reports, $cases, AnalyticsPeriod::fromRequest($request), [
                'map' => true,
                'sos' => true,
                'barangays' => !$barangay,
            ]),
            'barangays' => Barangay::orderBy('name')->get(['id', 'name']),
            'selectedBarangay' => $barangay?->id,
            'boundary' => $barangay?->boundary,
        ]);
    }

    public function accounts()
    {
        $staffAccounts = User::with('barangay')->whereIn('role', Role::staff())
            ->orderBy('full_name')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (User $account) => $account->setAttribute('role_label', $account->role->label()));
        $barangays = Barangay::orderBy('name')->get();

        return Inertia::render('Admin/AccountManagement', [
            'staffAccounts' => $staffAccounts,
            'barangays' => $barangays
        ]);
    }

    public function storeAccount(StoreStaffAccountRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request) {
            $account = User::create([
                'full_name' => $validated['full_name'],
                'phone_number' => $validated['phone_number'],
                'barangay_id' => User::barangayIdForRole(Role::from($validated['role']), $validated['barangay_id'] ?? null),
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
                "Created {$account->role->label()} account for {$account->full_name}."
            );
        });

        return redirect()->back()->with('success', 'Administrative account created successfully.');
    }

    public function updateAccount(UpdateStaffAccountRequest $request, User $user)
    {
        abort_unless($request->user()->can('manageStaffAccount', $user), 404);

        $validated = $request->validated();

        $updates = [
            'full_name' => $validated['full_name'],
            'phone_number' => $validated['phone_number'],
            'barangay_id' => User::barangayIdForRole(Role::from($validated['role']), $validated['barangay_id'] ?? null),
            'role' => $validated['role'],
            'address' => $validated['address'],
            'date_of_birth' => $validated['date_of_birth'],
            'start_of_residency' => $validated['start_of_residency'],
        ];

        if (!empty($validated['password'])) {
            $updates['password'] = Hash::make($validated['password']);
        }

        DB::transaction(function () use ($user, $updates, $request) {
            $user->update($updates);

            SystemLog::logAction(
                $user->barangay_id,
                $request->user()->id,
                'UPDATE',
                'Account',
                "Updated {$user->role->label()} account for {$user->full_name}."
            );
        });

        return redirect()->back()->with('success', 'Administrative account updated successfully.');
    }

    public function destroyAccount(Request $request, User $user)
    {
        abort_unless($request->user()->can('delete', $user), 404);

        DB::transaction(function () use ($user, $request) {
            SystemLog::logAction(
                $user->barangay_id,
                $request->user()->id,
                'DELETE',
                'Account',
                "Deleted {$user->role->label()} account for {$user->full_name}."
            );

            $user->delete();
        });

        return redirect()->back()->with('success', 'Account deleted successfully.');
    }

    public function auditLogs()
    {
        $logs = SystemLog::with('actor')->latest()->paginate(self::PER_PAGE);
        
        return Inertia::render('Admin/AuditLogs', [
            'logs' => $logs
        ]);
    }
}