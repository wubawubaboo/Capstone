<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\User;
use App\Models\SystemLog;
use App\Models\Report;
use App\Models\DocumentRequest;
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
        $staffAccounts = User::whereIn('role', ['secretary', 'vawc_officer'])->get();
        
        return Inertia::render('Admin/AccountManagement', [
            'staffAccounts' => $staffAccounts
        ]);
    }
    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255', // Changed from 'name'
            'email' => 'required|email|unique:users',
            'role' => 'required|in:secretary,vawc_officer',
            'password' => 'required|min:8',
        ]);

        User::create([
            'full_name' => $validated['full_name'], // Changed from 'name'
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            // You likely need to assign a default barangay_id here based on your schema
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