<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller {
    
    public function store(Request $request) {
    $validated = $request->validate([
        'incident_type' => 'required|string|max:255',
        'description' => 'required|string',
        'latitude' => 'nullable|numeric',
        'longitude' => 'nullable|numeric',
        'attachment' => 'nullable|image|max:2048',
    ]);

    $attachmentPath = null;
    if ($request->hasFile('attachment')) {
        $attachmentPath = $request->file('attachment')->store('reports');
    }

    Report::create([
        'user_id' => auth()->id(),
        'incident_type' => $validated['incident_type'],
        'description' => $validated['description'],
        'latitude' => $validated['latitude'],
        'longitude' => $validated['longitude'],
        'attachment_path' => $attachmentPath,
        'status' => 'Pending',
    ]);

    return to_route('resident.home')->with('success', 'Emergency report submitted successfully.');  
}
    public function showAttachment(Report $report) {
    if (auth()->id() !== $report->user_id && auth()->user()->role() !== 'secretary') {
        abort(403, 'Unauthorized to view this evidence.');
    }

    if (!Storage::disk('local')->exists($report->attachment_path)) {
        abort(404, 'File not found.');
    }

    return Storage::disk('local')->response($report->attachment_path);
}
}