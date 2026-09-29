<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class ReportScopingTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private Barangay $barangay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->barangay = Barangay::create(['name' => 'San Nicolas']);
    }

    private function fileReport(User $resident, array $overrides = []): Report
    {
        return Report::create(array_merge([
            'user_id' => $resident->id,
            'incident_type' => 'Noise',
            'description' => 'Loud karaoke',
            'status' => 'pending',
        ], $overrides));
    }

    public function test_report_records_the_barangay_it_was_filed_in(): void
    {
        $resident = $this->makeUser('resident');

        $this->assertSame($this->barangay->id, $this->fileReport($resident)->barangay_id);
    }

    public function test_report_stays_with_its_barangay_when_the_resident_moves(): void
    {
        $resident = $this->makeUser('resident');
        $report = $this->fileReport($resident);
        $secretary = $this->makeUser('secretary');

        $elsewhere = Barangay::create(['name' => 'Elsewhere']);
        $resident->update(['barangay_id' => $elsewhere->id]);

        $this->assertTrue(Report::visibleTo($secretary)->whereKey($report->id)->exists());
        $this->assertFalse(Report::visibleTo($this->makeUser('secretary', ['barangay_id' => $elsewhere->id]))->whereKey($report->id)->exists());
        $this->assertTrue($secretary->can('update', $report->fresh()));
    }

    public function test_sos_alerts_go_to_the_secretary_desk_only(): void
    {
        $sos = $this->fileReport($this->makeUser('resident'), ['incident_type' => Report::SOS_TYPE]);
        $secretary = $this->makeUser('secretary');
        $vawc = $this->makeUser('vawc');

        $this->assertTrue(Report::visibleTo($secretary)->whereKey($sos->id)->exists());
        $this->assertTrue($secretary->can('update', $sos));

        $this->assertFalse(Report::visibleTo($vawc)->whereKey($sos->id)->exists());
        $this->assertFalse($vawc->can('view', $sos));
        $this->actingAs($vawc)->get(route('vawc.reports'))
            ->assertInertia(fn (Assert $page) => $page->has('reports.data', 0));
        $this->actingAs($vawc)->put(route('vawc.reports.update-status', $sos), ['status' => 'in_progress'])->assertNotFound();
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('vawc.reports.acknowledge'));
    }

    public function test_pending_report_search_is_scoped_to_the_desk(): void
    {
        $resident = $this->makeUser('resident', ['full_name' => 'Maria Santos']);
        $noise = $this->fileReport($resident, ['incident_type' => 'Noise complaint']);
        $vawc = $this->fileReport($resident, ['incident_type' => 'Physical abuse', 'is_vawc' => true]);
        $this->fileReport($this->makeUser('resident', ['barangay_id' => Barangay::create(['name' => 'Elsewhere'])->id]), ['incident_type' => 'Noise elsewhere']);

        $secretaryIds = collect($this->actingAs($this->makeUser('secretary'))
            ->getJson(route('secretary.reports.pending-search', ['q' => 'Maria']))
            ->assertOk()->json())->pluck('id')->all();
        $this->assertSame([$noise->id], $secretaryIds);

        $vawcIds = collect($this->actingAs($this->makeUser('vawc'))
            ->getJson(route('vawc.reports.pending-search', ['q' => 'abuse']))
            ->assertOk()->json())->pluck('id')->all();
        $this->assertSame([$vawc->id], $vawcIds);

        $byNumber = $this->actingAs($this->makeUser('secretary'))
            ->getJson(route('secretary.reports.pending-search', ['q' => "#{$noise->id}"]))->json();
        $this->assertSame($noise->id, $byNumber[0]['id']);
    }

    public function test_create_blotter_preselects_only_a_report_the_desk_may_file(): void
    {
        $resident = $this->makeUser('resident');
        $noise = $this->fileReport($resident);
        $vawc = $this->fileReport($resident, ['is_vawc' => true]);
        $secretary = $this->makeUser('secretary');

        $this->actingAs($secretary)->get(route('secretary.blotters.create', ['report_id' => $noise->id]))
            ->assertInertia(fn (Assert $page) => $page->where('selectedReport.id', $noise->id)->missing('pendingReports'));

        $this->actingAs($secretary)->get(route('secretary.blotters.create', ['report_id' => $vawc->id]))
            ->assertInertia(fn (Assert $page) => $page->where('selectedReport', null));
    }
}
