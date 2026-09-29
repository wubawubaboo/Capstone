<?php

namespace Tests\Feature;

use App\Enums\BlotterStatus;
use App\Enums\ReportStatus;
use App\Models\Barangay;
use App\Models\BlotterRecord;
use App\Models\MediationSchedule;
use App\Models\Report;
use App\Models\SystemLog;
use App\Models\User;
use App\Models\VawcDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

/** The case workflow shared by both desks (CaseDeskController). */
class CaseDeskTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private Barangay $barangay;
    private User $secretary;
    private User $vawcOfficer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->barangay = Barangay::create(['name' => 'San Nicolas']);
        $this->secretary = $this->makeUser('secretary');
        $this->vawcOfficer = $this->makeUser('vawc');
    }

    private function makeCase(bool $vawc, ?Report $report = null): BlotterRecord
    {
        static $n = 0;
        $n++;

        $case = BlotterRecord::create([
            'barangay_id' => $this->barangay->id,
            'report_id' => $report?->id,
            'incident_type' => 'Dispute',
            'case_number' => ($vawc ? 'VAWC' : 'BLT') . sprintf('-2026-%04d', $n),
            'status' => BlotterStatus::Pending,
            'official_entry_date' => now(),
        ]);

        if ($vawc) {
            VawcDetail::create(['blotter_record_id' => $case->id, 'officer_in_charge_id' => $this->vawcOfficer->id, 'confidential_notes' => 'Notes']);
        }

        return $case;
    }

    private function assertLogged(string $module, string $description): void
    {
        $this->assertTrue(
            SystemLog::where('module', $module)->where('description', $description)->exists(),
            "No '{$module}' log: {$description}"
        );
    }

    public function test_each_desk_only_reaches_its_own_cases(): void
    {
        $vawcCase = $this->makeCase(vawc: true);
        $case = $this->makeCase(vawc: false);

        $this->actingAs($this->secretary)->get(route('secretary.case-history', $vawcCase))->assertNotFound();
        $this->actingAs($this->secretary)->post(route('secretary.cases.resolve', $vawcCase))->assertNotFound();
        $this->actingAs($this->vawcOfficer)->get(route('vawc.case-history', $case))->assertNotFound();
        $this->actingAs($this->vawcOfficer)->post(route('vawc.cases.escalate', $case))->assertNotFound();

        $this->assertSame(BlotterStatus::Pending, $vawcCase->fresh()->status);
        $this->assertSame(BlotterStatus::Pending, $case->fresh()->status);
    }

    public function test_viewing_a_vawc_case_is_audited_but_a_regular_case_is_not(): void
    {
        $vawcCase = $this->makeCase(vawc: true);
        $case = $this->makeCase(vawc: false);

        $this->actingAs($this->vawcOfficer)->get(route('vawc.case-history', $vawcCase))
            ->assertInertia(fn (Assert $page) => $page->component('VAWC/CaseHistory')->has('blotter.vawc_detail'));
        $this->actingAs($this->secretary)->get(route('secretary.case-history', $case))
            ->assertInertia(fn (Assert $page) => $page->component('Secretary/CaseHistory')->missing('blotter.vawc_detail'));

        $this->assertLogged('VAWC Blotter', "Viewed confidential VAWC case #{$vawcCase->case_number}.");
        $this->assertSame(0, SystemLog::where('action_type', 'VIEW')->where('description', 'like', "%{$case->case_number}%")->count());
    }

    public function test_resolving_closes_the_linked_report_and_is_logged_with_desk_wording(): void
    {
        // Filing a case from a report moves the report to "blottered" (FileVawcCase).
        $report = Report::create(['user_id' => $this->makeUser('resident')->id, 'incident_type' => 'Abuse', 'description' => 'x', 'status' => 'blottered', 'is_vawc' => true]);
        $vawcCase = $this->makeCase(vawc: true, report: $report);

        $this->actingAs($this->vawcOfficer)->post(route('vawc.cases.resolve', $vawcCase))
            ->assertRedirect(route('vawc.blotters'));

        $this->assertSame(BlotterStatus::Resolved, $vawcCase->fresh()->status);
        $this->assertSame(ReportStatus::Completed, $report->fresh()->status);
        $this->assertSame('Confidential VAWC case resolved.', $vawcCase->statusHistory()->first()->note);
        $this->assertLogged('VAWC Blotter', "Resolved VAWC Case #{$vawcCase->case_number}.");
    }

    public function test_a_vawc_case_counts_as_settled_once_resolved(): void
    {
        $vawcCase = $this->makeCase(vawc: true);
        $this->assertFalse($vawcCase->vawcDetail->isSettled());

        $this->actingAs($this->vawcOfficer)->post(route('vawc.cases.resolve', $vawcCase));

        $this->assertTrue($vawcCase->fresh()->vawcDetail->isSettled());
    }

    public function test_escalating_a_regular_case_is_logged_on_the_secretary_desk(): void
    {
        $case = $this->makeCase(vawc: false);

        $this->actingAs($this->secretary)->post(route('secretary.cases.escalate', $case))
            ->assertRedirect(route('secretary.blotters'));

        $this->assertSame(BlotterStatus::EscalatedToCourt, $case->fresh()->status);
        $this->assertLogged('Blotter', "Escalated Case #{$case->case_number} to Court.");
    }

    public function test_mediation_scheduling_and_notes_work_on_both_desks(): void
    {
        foreach ([[$this->secretary, 'secretary', false, 'Mediation Schedule'], [$this->vawcOfficer, 'vawc', true, 'VAWC Mediation Schedule']] as [$user, $desk, $vawc, $module]) {
            $case = $this->makeCase($vawc);

            $this->actingAs($user)
                ->post(route("{$desk}.cases.schedule-mediation", $case), ['scheduled_date' => now()->addDay()->format('Y-m-d\TH:i')])
                ->assertSessionHasNoErrors();
            $session = MediationSchedule::where('blotter_record_id', $case->id)->firstOrFail();

            $this->actingAs($user)->put(route("{$desk}.mediation-notes.update", $session), ['notes' => 'Parties agreed'])
                ->assertSessionHasNoErrors();

            $this->assertSame(BlotterStatus::UnderMediation, $case->fresh()->status);
            $this->assertSame('Parties agreed', $session->fresh()->notes);
            $this->assertLogged($module, "Scheduled Session #1 for case #{$case->case_number}.");
            $this->assertLogged($module, "Updated notes for Session #1 of case #{$case->case_number}.");
        }
    }

    public function test_reopening_is_logged_with_the_reason(): void
    {
        $vawcCase = $this->makeCase(vawc: true);
        $this->actingAs($this->vawcOfficer)->post(route('vawc.cases.resolve', $vawcCase));

        $this->actingAs($this->vawcOfficer)->post(route('vawc.cases.reopen', $vawcCase), ['reason' => 'New evidence'])
            ->assertSessionHasNoErrors();

        $this->assertSame(BlotterStatus::Pending, $vawcCase->fresh()->status);
        $this->assertLogged('VAWC Blotter', "Reopened VAWC Case #{$vawcCase->case_number}: New evidence");
    }

    public function test_case_report_pdf_is_named_per_desk(): void
    {
        $vawcCase = $this->makeCase(vawc: true);
        $case = $this->makeCase(vawc: false);

        $this->actingAs($this->vawcOfficer)->get(route('vawc.case-history.report', $vawcCase))
            ->assertDownload("vawc-case-report-{$vawcCase->case_number}.pdf");
        $this->actingAs($this->secretary)->get(route('secretary.case-history.report', $case))
            ->assertDownload("case-report-{$case->case_number}.pdf");

        $this->assertLogged('VAWC Blotter', "Generated confidential VAWC case report PDF for case #{$vawcCase->case_number}.");
        $this->assertLogged('Blotter', "Generated case report PDF for case #{$case->case_number}.");
    }

    public function test_each_calendar_shows_only_its_desks_hearings(): void
    {
        $vawcSession = MediationSchedule::create(['blotter_record_id' => $this->makeCase(vawc: true)->id, 'meeting_number' => 1, 'scheduled_date' => now()->addHour(), 'status' => 'Scheduled']);
        $session = MediationSchedule::create(['blotter_record_id' => $this->makeCase(vawc: false)->id, 'meeting_number' => 1, 'scheduled_date' => now()->addHour(), 'status' => 'Scheduled']);

        $this->actingAs($this->vawcOfficer)->get(route('vawc.mediation-calendar'))
            ->assertInertia(fn (Assert $page) => $page->component('VAWC/MediationCalendar')->has('schedules', 1)->where('schedules.0.id', $vawcSession->id));
        $this->actingAs($this->secretary)->get(route('secretary.mediation-calendar'))
            ->assertInertia(fn (Assert $page) => $page->component('Secretary/MediationCalendar')->has('schedules', 1)->where('schedules.0.id', $session->id));
    }
}
