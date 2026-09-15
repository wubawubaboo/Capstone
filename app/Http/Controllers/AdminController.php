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

    public function accounts()
    {
        $staffAccounts = User::with('barangay')->whereIn('role', ['secretary', 'vawc_officer'])->get();
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
            'barangay_id' => 'required|exists:barangays,id',
            'role' => 'required|in:secretary,vawc_officer',
            'password' => 'required|min:8',
            'address' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'start_of_residency' => 'required|integer|min:1900|max:' . date('Y'),
        ]);

        User::create([
            'full_name' => $validated['full_name'],
            'phone_number' => $validated['phone_number'],
            'barangay_id' => $validated['barangay_id'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'is_verified' => true,
            'address' => $validated['address'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'start_of_residency' => $validated['start_of_residency'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Administrative account created successfully.');
    }

    public function auditLogs()
    {
        $logs = SystemLog::with('user')->latest()->paginate(50);
        
        return Inertia::render('Admin/AuditLogs', [
            'logs' => $logs
        ]);
    }
}