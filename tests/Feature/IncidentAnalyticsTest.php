<?php

namespace Tests\Feature;

use App\Enums\BlotterStatus;
use App\Models\Barangay;
use App\Models\BlotterRecord;
use App\Models\CaseStatusHistory;
use App\Models\Report;
use App\Models\User;
use App\Models\VawcDetail;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class IncidentAnalyticsTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private Barangay $barangay;

    private User $resident;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-09-29 12:00', 'Asia/Manila'));
        $this->barangay = Barangay::create(['name' => 'San Nicolas']);
        $this->resident = $this->makeUser('resident');
    }

    private function report(string $at, array $overrides = []): Report
    {
        $report = Report::create(array_merge([
            'user_id' => $this->resident->id,
            'incident_type' => 'Crime',
            'description' => 'Details',
            'status' => 'pending',
        ], $overrides));
        $report->forceFill(['created_at' => CarbonImmutable::parse($at)])->save();

        return $report;
    }

    private function blotter(string $at, array $overrides = [], bool $vawc = false): BlotterRecord
    {
        static $n = 0;
        $n++;

        $case = BlotterRecord::create(array_merge([
            'barangay_id' => $this->barangay->id,
            'incident_type' => 'Dispute',
            'case_number' => sprintf('%s-2026-%04d', $vawc ? 'VAWC' : 'BLT', $n),
            'status' => BlotterStatus::Pending,
            'official_entry_date' => CarbonImmutable::parse($at),
        ], $overrides));

        if ($vawc) {
            VawcDetail::create(['blotter_record_id' => $case->id, 'officer_in_charge_id' => $this->makeUser('vawc')->id, 'confidential_notes' => 'Notes']);
        }

        return $case;
    }

    public function test_secretary_dashboard_counts_reports_and_walk_in_cases_once(): void
    {
        $fromReport = $this->report('2026-09-20 10:00');
        $this->blotter('2026-09-21 10:00', ['report_id' => $fromReport->id]);
        $this->blotter('2026-09-22 10:00');
        $this->report('2026-09-23 10:00', ['is_vawc' => true]);
        $this->report('2026-05-01 10:00');

        $this->actingAs($this->makeUser('secretary'))
            ->get(route('secretary.analytics'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Secretary/Analytics')
                ->where('dashboard.period.range', '90d')
                ->where('dashboard.totals.current', 2)
                ->where('dashboard.totals.reports', 1)
                ->where('dashboard.totals.walk_ins', 1)
                ->where('dashboard.totals.previous', 1)
                ->where('dashboard.caseOutcomes.filed', 2)
                ->where('dashboard.backlog.open', 2)
                ->has('dashboard.map.points', 0)
                ->has('dashboard.insights'));
    }

    public function test_range_selects_the_window_and_unknown_ranges_fall_back(): void
    {
        $this->report('2026-09-25 10:00');
        $this->report('2026-08-01 10:00');
        $secretary = $this->makeUser('secretary');

        $this->actingAs($secretary)->get(route('secretary.analytics', ['range' => '30d']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('dashboard.totals.current', 1)
                ->where('dashboard.totals.previous', 1)
                ->where('dashboard.period.bucket', 'day')
                ->has('dashboard.trend.labels', 30));

        $this->actingAs($secretary)->get(route('secretary.analytics', ['range' => 'forever']))
            ->assertInertia(fn (Assert $page) => $page->where('dashboard.period.range', '90d'));
    }

    public function test_time_of_week_uses_manila_time(): void
    {
        // Friday 2026-09-25, 7 PM Manila: the 6 PM–9 PM block.
        $this->report('2026-09-25 19:30');

        $this->actingAs($this->makeUser('secretary'))
            ->get(route('secretary.analytics'))
            ->assertInertia(fn (Assert $page) => $page->where('dashboard.timeOfWeek.grid.4.6', 1));
    }

    public function test_findings_name_the_busiest_time_and_the_rising_type(): void
    {
        foreach (range(1, 8) as $week) {
            $this->report(CarbonImmutable::parse('2026-09-25 19:30')->subWeeks($week % 4)->toDateTimeString(), ['incident_type' => 'Accident']);
        }
        $this->report('2026-09-01 08:00');
        $this->report('2026-09-02 08:00');
        $this->report('2026-05-01 08:00', ['incident_type' => 'Accident']);

        $this->actingAs($this->makeUser('secretary'))->get(route('secretary.analytics'))
            ->assertInertia(fn (Assert $page) => $page->where('dashboard.insights', fn ($insights) => collect($insights)->pluck('text')->contains(
                'The busiest time is Friday, 6 PM–9 PM (8 incidents). Plan patrols and desk coverage around it.'
            ) && collect($insights)->pluck('text')->contains(
                'Accident incidents climbed from 1 to 8, the sharpest increase of any type.'
            )));
    }

    public function test_case_outcomes_track_settlement_and_time_to_close(): void
    {
        $secretary = $this->makeUser('secretary');
        $settled = $this->blotter('2026-09-10 09:00', ['status' => BlotterStatus::Resolved]);
        CaseStatusHistory::create(['blotter_record_id' => $settled->id, 'from_status' => 'Pending', 'to_status' => 'Resolved', 'changed_by' => $secretary->id])
            ->forceFill(['created_at' => CarbonImmutable::parse('2026-09-14 09:00')])->save();
        $this->blotter('2026-09-11 09:00', ['status' => BlotterStatus::EscalatedToCourt]);

        $this->actingAs($secretary)->get(route('secretary.analytics'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('dashboard.caseOutcomes.settled', 1)
                ->where('dashboard.caseOutcomes.escalated', 1)
                ->where('dashboard.caseOutcomes.settlementRate', 50)
                ->where('dashboard.caseOutcomes.medianDaysToClose', 4));
    }

    public function test_sos_alerts_report_acknowledgement_time(): void
    {
        $sos = $this->report('2026-09-20 10:00', ['incident_type' => Report::SOS_TYPE, 'latitude' => 15.2952, 'longitude' => 120.9416]);
        $sos->update(['acknowledged_at' => CarbonImmutable::parse('2026-09-20 10:06')]);
        $this->report('2026-09-21 10:00', ['incident_type' => Report::SOS_TYPE]);

        $this->actingAs($this->makeUser('secretary'))->get(route('secretary.analytics'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('dashboard.sos.current', 2)
                ->where('dashboard.sos.unacknowledged', 1)
                ->where('dashboard.sos.medianAckMinutes', 6)
                ->where('dashboard.map.points.0', [15.2952, 120.9416, 2])
                ->where('dashboard.insights.0.tone', 'critical'));
    }

    public function test_vawc_dashboard_sees_only_vawc_records_and_has_no_map_or_sos(): void
    {
        $this->report('2026-09-20 10:00', ['is_vawc' => true]);
        $this->report('2026-09-20 11:00');
        $this->blotter('2026-09-21 10:00', vawc: true);
        $this->blotter('2026-09-21 11:00');

        $this->actingAs($this->makeUser('vawc'))->get(route('vawc.analytics'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('VAWC/Analytics')
                ->where('dashboard.totals.current', 2)
                ->where('dashboard.caseOutcomes.filed', 1)
                ->where('dashboard.sos', null)
                ->where('dashboard.map', null));
    }

    public function test_admin_dashboard_leaves_out_vawc_records_and_filters_by_barangay(): void
    {
        $elsewhere = Barangay::create(['name' => 'Elsewhere']);
        $this->report('2026-09-20 10:00');
        $this->report('2026-09-20 11:00', ['is_vawc' => true]);
        $this->blotter('2026-09-21 10:00', vawc: true);
        $this->blotter('2026-09-21 11:00', ['barangay_id' => $elsewhere->id]);
        $admin = $this->makeUser('admin', ['barangay_id' => null]);

        $this->actingAs($admin)->get(route('admin.analytics'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('dashboard.totals.current', 2)
                ->where('dashboard.barangays.0.current', 1)
                ->where('dashboard.barangays.1.current', 1)
                ->where('selectedBarangay', null));

        $this->actingAs($admin)->get(route('admin.analytics', ['barangay' => $elsewhere->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('dashboard.totals.current', 1)
                ->where('dashboard.totals.walk_ins', 1)
                ->where('dashboard.barangays', null)
                ->where('selectedBarangay', $elsewhere->id));
    }
}
