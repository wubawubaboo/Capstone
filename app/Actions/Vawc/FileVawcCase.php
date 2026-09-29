<?php

namespace App\Actions\Vawc;

use App\Enums\BlotterStatus;
use App\Enums\ReportStatus;
use App\Models\BlotterRecord;
use App\Models\Report;
use App\Models\User;
use App\Models\VawcDetail;
use Illuminate\Support\Facades\DB;

/**
 * Files a confidential VAWC case: resolves the complainant either from a
 * linked resident Report or from validated manual input (VAWC allows an
 * unregistered complainant name, unlike plain blotter filing), creates the
 * BlotterRecord + its opening status-history entry + the linked VawcDetail,
 * and — when filed from a report — advances that report to Blottered. All
 * DB writes are atomic.
 */
class FileVawcCase
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
            $complainantId = $validated['is_registered_complainant'] ? $validated['complainant_id'] : null;

            if ($complainantId) {
                $user = User::where('barangay_id', $barangayId)->find($complainantId);
                abort_unless($user, 422, 'Complainant must be a resident of your barangay.');
                $complainantName = $user->full_name;
            } else {
                $complainantName = $validated['complainant_name'];
            }

            $incidentType = $validated['incident_type'];
            $incidentDescription = $validated['description'];
        }

        if ($validated['is_registered_respondent']) {
            $respondentExists = User::where('barangay_id', $barangayId)->where('id', $validated['receiver_id'])->exists();
            abort_unless($respondentExists, 422, 'Respondent must be a resident of your barangay.');
        }

        return DB::transaction(function () use ($barangayId, $reportId, $complainantId, $complainantName, $incidentType, $incidentDescription, $validated, $report, $actor) {
            $caseNumber = BlotterRecord::generateCaseNumber('VAWC', $barangayId);

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
                'note'        => 'Confidential VAWC case filed.',
            ]);

            VawcDetail::create([
                'blotter_record_id'    => $blotter->id,
                'officer_in_charge_id' => $actor->id,
                'confidential_notes'   => !empty($validated['confidential_notes']) ? $validated['confidential_notes'] : $incidentDescription,
            ]);

            if ($report) {
                $report->transitionStatus(ReportStatus::Blottered, $actor, 'Logged to confidential VAWC case.');
            }

            return $blotter;
        });
    }
}
