<?php

namespace Tests\Feature;

use App\Enums\BlotterStatus;
use App\Enums\MediationStatus;
use App\Models\Barangay;
use App\Models\BlotterRecord;
use App\Models\MediationSchedule;
use App\Models\Report;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private Barangay $barangay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->barangay = Barangay::create(['name' => 'San Nicolas']);
    }

    private function hearingOn(CarbonImmutable $date): MediationSchedule
    {
        static $case = 0;
        $case++;

        $blotter = BlotterRecord::create([
            'barangay_id' => $this->barangay->id,
            'incident_type' => 'Noise',
            'case_number' => sprintf('BLT-2026-%04d', $case),
            'status' => BlotterStatus::UnderMediation,
            'official_entry_date' => now(),
        ]);

        return MediationSchedule::create([
            'blotter_record_id' => $blotter->id,
            'meeting_number' => 1,
            'scheduled_date' => $date,
            'status' => MediationStatus::Scheduled,
        ]);
    }

    public function test_calendar_loads_only_the_requested_month(): void
    {
        $inMonth = $this->hearingOn(CarbonImmutable::create(2026, 3, 15, 10));
        $this->hearingOn(CarbonImmutable::create(2026, 5, 15, 10));

        $this->actingAs($this->makeUser('secretary'))
            ->get(route('secretary.mediation-calendar', ['month' => '2026-03']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('month', '2026-03')
                ->has('schedules', 1)
                ->where('schedules.0.id', $inMonth->id));
    }

    public function test_calendar_falls_back_to_the_current_month_for_bad_input(): void
    {
        $this->actingAs($this->makeUser('vawc'))
            ->get(route('vawc.mediation-calendar', ['month' => '2026-13']))
            ->assertInertia(fn (Assert $page) => $page->where('month', now()->format('Y-m')));
    }

    public function test_upcoming_sidebar_lists_future_hearings_whatever_month_is_shown(): void
    {
        $this->hearingOn(CarbonImmutable::now()->subDays(3));
        $next = $this->hearingOn(CarbonImmutable::now()->addMonths(2));

        $this->actingAs($this->makeUser('secretary'))
            ->get(route('secretary.mediation-calendar', ['month' => '2020-01']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('schedules', 0)
                ->has('upcoming', 1)
                ->where('upcoming.0.id', $next->id));
    }

    public function test_admin_accounts_are_paginated(): void
    {
        $admin = $this->makeUser('admin', ['barangay_id' => null]);
        for ($i = 0; $i < 17; $i++) {
            $this->makeUser('secretary');
        }

        $this->actingAs($admin)->get(route('admin.accounts'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('staffAccounts.total', 18)
                ->has('staffAccounts.data', 15));
    }

    public function test_tracking_tabs_are_paginated_independently(): void
    {
        $resident = $this->makeUser('resident');
        for ($i = 0; $i < 17; $i++) {
            Report::create(['user_id' => $resident->id, 'incident_type' => 'Noise', 'description' => 'x', 'status' => 'pending']);
        }

        $this->actingAs($resident)->get(route('resident.tracking', ['reports_page' => 2]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('reports.total', 17)
                ->has('reports.data', 2)
                ->where('serviceRequests.current_page', 1));
    }
}
