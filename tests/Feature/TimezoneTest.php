<?php

namespace Tests\Feature;

use App\Enums\BlotterStatus;
use App\Exports\ReportsExport;
use App\Models\Barangay;
use App\Models\BlotterRecord;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\MediationSchedule;
use App\Models\Report;
use App\Support\DocumentFieldCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private Barangay $barangay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->barangay = Barangay::create(['name' => 'San Nicolas']);
    }

    private function manila(string $time): CarbonImmutable
    {
        return CarbonImmutable::parse($time, 'Asia/Manila');
    }

    public function test_app_runs_in_manila_time(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
    }

    public function test_hearing_typed_as_11am_reaches_the_browser_as_11am_manila(): void
    {
        $this->travelTo($this->manila('2026-09-30 09:00'));
        $blotter = BlotterRecord::create([
            'barangay_id' => $this->barangay->id,
            'incident_type' => 'Noise',
            'case_number' => 'BLT-2026-0001',
            'status' => BlotterStatus::Pending,
            'official_entry_date' => now(),
        ]);

        // What the datetime-local input sends: wall-clock time, no offset.
        $this->actingAs($this->makeUser('secretary'))
            ->post(route('secretary.cases.schedule-mediation', $blotter), ['scheduled_date' => '2026-10-01T11:00'])
            ->assertSessionHasNoErrors();

        $json = MediationSchedule::firstOrFail()->toArray()['scheduled_date'];
        $this->assertTrue(CarbonImmutable::parse($json)->equalTo($this->manila('2026-10-01 11:00')), "Sent {$json}");
    }

    public function test_hearing_earlier_today_counts_as_in_the_past(): void
    {
        $this->travelTo($this->manila('2026-09-30 10:00'));
        $blotter = BlotterRecord::create([
            'barangay_id' => $this->barangay->id,
            'incident_type' => 'Noise',
            'case_number' => 'BLT-2026-0001',
            'status' => BlotterStatus::Pending,
            'official_entry_date' => now(),
        ]);

        $this->actingAs($this->makeUser('secretary'))
            ->post(route('secretary.cases.schedule-mediation', $blotter), ['scheduled_date' => '2026-09-30T09:00'])
            ->assertSessionHasErrors('scheduled_date');
    }

    public function test_certificate_issued_just_after_midnight_carries_that_days_date(): void
    {
        $this->travelTo($this->manila('2026-10-01 00:30'));
        $request = DocumentRequest::create([
            'requester_id' => $this->makeUser('resident')->id,
            'barangay_id' => $this->barangay->id,
            'document_type_id' => DocumentType::create(['name' => 'Clearance'])->id,
            'purpose' => 'Employment',
            'status' => 'Pending',
            'reference_no' => 'DOC-1',
        ]);

        $fields = DocumentFieldCatalog::resolve($request);

        $this->assertSame('October 01, 2026', $fields['date_issued']);
        $this->assertSame('01', $fields['date_day']);
    }

    public function test_export_date_filter_covers_the_whole_manila_day(): void
    {
        Excel::fake();
        $resident = $this->makeUser('resident');
        $secretary = $this->makeUser('secretary');

        foreach (['2026-09-30 23:30' => 'Late Sept 30', '2026-10-01 07:00' => 'Early Oct 1', '2026-10-01 23:30' => 'Late Oct 1'] as $time => $type) {
            $this->travelTo($this->manila($time));
            Report::create(['user_id' => $resident->id, 'incident_type' => $type, 'description' => 'x', 'status' => 'pending']);
        }

        $this->actingAs($secretary)->get(route('secretary.reports.export', ['start_date' => '2026-10-01', 'end_date' => '2026-10-01']));

        Excel::assertDownloaded('reports-2026-10-01-to-2026-10-01.xlsx', function (ReportsExport $export) {
            $types = $export->collection()->pluck('incident_type')->sort()->values()->all();
            $this->assertSame(['Early Oct 1', 'Late Oct 1'], $types);
            $this->assertSame('2026-10-01 07:00', $export->map($export->collection()->firstWhere('incident_type', 'Early Oct 1'))[0]);

            return true;
        });
    }

    public function test_hearing_reminders_run_at_8am_manila(): void
    {
        $event = collect(app(Schedule::class)->events())->firstOrFail(fn ($e) => $e->description === 'mediation-hearing-reminders');

        $this->travelTo($this->manila('2026-10-01 08:00'));
        $this->assertTrue($event->isDue($this->app));

        $this->travelTo(CarbonImmutable::parse('2026-10-01 08:00', 'UTC'));
        $this->assertFalse($event->isDue($this->app));
    }

    public function test_date_of_birth_is_sent_as_a_plain_date(): void
    {
        $this->assertSame('1990-05-01', $this->makeUser('resident', ['date_of_birth' => '1990-05-01'])->toArray()['date_of_birth']);
    }

    public function test_migration_shifts_server_times_but_not_typed_hearing_times(): void
    {
        $blotter = BlotterRecord::create([
            'barangay_id' => $this->barangay->id,
            'incident_type' => 'Noise',
            'case_number' => 'BLT-2026-0001',
            'status' => BlotterStatus::UnderMediation,
            'official_entry_date' => now(),
        ]);
        DB::table('blotter_records')->where('id', $blotter->id)->update(['created_at' => '2026-09-17 01:00:00', 'official_entry_date' => '2026-09-17 01:00:00']);
        $hearing = MediationSchedule::create(['blotter_record_id' => $blotter->id, 'meeting_number' => 1, 'scheduled_date' => '2026-09-18 11:00:00', 'status' => 'Scheduled']);
        DB::table('mediation_schedules')->where('id', $hearing->id)->update(['created_at' => '2026-09-17 02:00:00']);

        $migration = require database_path('migrations/2026_09_30_170000_shift_timestamps_to_manila_time.php');
        $migration->up();

        $this->assertSame('2026-09-17 09:00:00', DB::table('blotter_records')->where('id', $blotter->id)->value('created_at'));
        $this->assertSame('2026-09-17 09:00:00', DB::table('blotter_records')->where('id', $blotter->id)->value('official_entry_date'));
        $this->assertSame('2026-09-17 10:00:00', DB::table('mediation_schedules')->where('id', $hearing->id)->value('created_at'));
        $this->assertSame('2026-09-18 11:00:00', DB::table('mediation_schedules')->where('id', $hearing->id)->value('scheduled_date'));

        $migration->down();
        $this->assertSame('2026-09-17 01:00:00', DB::table('blotter_records')->where('id', $blotter->id)->value('created_at'));
    }
}
