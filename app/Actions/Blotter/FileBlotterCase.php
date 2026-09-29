<?php

namespace App\Actions\Blotter;

use App\Enums\BlotterStatus;
use App\Enums\ReportStatus;
use App\Models\BlotterRecord;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Files a plain (non-VAWC) blotter case: resolves the complainant either
 * from a linked resident Report or from validated manual input, creates the
 * BlotterRecord + its opening status-history entry, and — when filed from a
 * report — advances that report to Blottered. All DB writes are atomic.
 */
class FileBlotterCase
{
    public function __invoke(array $validated, int $barangayId, User $actor): BlotterRecord
    {
        $report = null;
        $reportId = null;
        $complainantId = null;
        $complainantName = null;
        $incidentType = null;
        $incidentDescription = null;

        if (!empty($validated['report_id'])) {
            $report = Report::with('user')
                ->visibleTo($actor)
                ->findOrFail($validated['report_id']);

            $reportId = $report->id;
            $complainantId = $report->user_id;
            $complainantName = $report->user ? $report->user->full_name : 'Unknown';
            $incidentType = $report->incident_type;
            $incidentDescription = $report->description;
        } else {
            $complainantId = $validated['complainant_id'];
            $user = User::where('barangay_id', $barangayId)->find($complainantId);
            abort_unless($user, 422, 'Complainant must be a resident of your barangay.');

            $complainantName = $user->full_name;
            $incidentType = $validated['incident_type'];
            $incidentDescription = $validated['description'];
        }

        if ($validated['is_registered_respondent']) {
            $respondentExists = User::where('barangay_id', $barangayId)->where('id', $validated['receiver_id'])->exists();
            abort_unless($respondentExists, 422, 'Respondent must be a resident of your barangay.');
        }

        return DB::transaction(function () use ($barangayId, $reportId, $complainantId, $complainantName, $incidentType, $incidentDescription, $validated, $report, $actor) {
            $caseNumber = BlotterRecord::generateCaseNumber('BLT', $barangayId);

            $blotter = BlotterRecord::create([
                'barangay_id'          => $barangayId,
                'report_id'            => $reportId,
                'complainant_id'       => $complainantId,
                'complainant_name'     => $complainantName,
                'incident_type'        => $incidentType,
                'incident_description' => $incidentDescription,
                'receiver_id'          => $validated['is_registered_respondent'] ? $validated['receiver_id'] : null,
                'receiver_name'        => !$validated['is_registered_respondent'] ? $validated['receiver_name'] : null,
                'case_number'          => $caseNumber,
                'status'               => BlotterStatus::Pending,
                'official_entry_date'  => now(),
            ]);

            $blotter->statusHistory()->create([
                'from_status' => null,
                'to_status'   => BlotterStatus::Pending->value,
                'changed_by'  => $actor->id,
                'note'        => 'Case filed.',
            ]);

            if ($report) {
                $report->transitionStatus(ReportStatus::Blottered, $actor, 'Logged to blotter case.');
            }

            return $blotter;
        });
    }
}
