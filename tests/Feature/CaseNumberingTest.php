<?php

namespace Tests\Feature;

use App\Enums\BlotterStatus;
use App\Models\Barangay;
use App\Models\BlotterRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class CaseNumberingTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private Barangay $barangay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->barangay = Barangay::create(['name' => 'San Nicolas']);
    }

    private function nextNumber(string $prefix, ?int $barangayId = null): string
    {
        return DB::transaction(fn () => BlotterRecord::generateCaseNumber($prefix, $barangayId ?? $this->barangay->id));
    }

    private function fileWalkInCase(): BlotterRecord
    {
        $this->actingAs($this->makeUser('secretary'))->post(route('secretary.blotters.store'), [
            'complainant_id' => $this->makeUser('resident')->id,
            'incident_type' => 'Noise',
            'description' => 'Loud karaoke',
            'is_registered_respondent' => false,
            'receiver_name' => 'Neighbor',
        ])->assertSessionHasNoErrors();

        return BlotterRecord::latest('id')->firstOrFail();
    }

    public function test_numbers_are_sequential_per_barangay_and_year(): void
    {
        $year = now()->year;

        $this->assertSame("BLT-{$year}-0001", $this->nextNumber('BLT'));
        $this->assertSame("BLT-{$year}-0002", $this->nextNumber('BLT'));
        $this->assertSame("BLT-{$year}-0001", $this->nextNumber('BLT', Barangay::create(['name' => 'Elsewhere'])->id));
    }

    public function test_each_prefix_is_numbered_separately(): void
    {
        $year = now()->year;

        $this->nextNumber('BLT');
        $this->nextNumber('BLT');

        $this->assertSame("VAWC-{$year}-0001", $this->nextNumber('VAWC'));
        $this->assertSame("BLT-{$year}-0003", $this->nextNumber('BLT'));
    }

    public function test_deleting_a_case_never_causes_a_reused_number(): void
    {
        $first = $this->fileWalkInCase();
        $second = $this->fileWalkInCase();

        $second->delete();
        $third = $this->fileWalkInCase();

        $this->assertStringEndsWith('-0003', $third->case_number);
        $this->assertNotSame($first->case_number, $third->case_number);
    }

    public function test_a_rolled_back_filing_gives_its_number_back(): void
    {
        try {
            DB::transaction(function () {
                BlotterRecord::generateCaseNumber('BLT', $this->barangay->id);
                throw new \RuntimeException('filing failed');
            });
        } catch (\RuntimeException) {
        }

        $this->assertStringEndsWith('-0001', $this->nextNumber('BLT'));
    }

    public function test_migration_continues_from_the_highest_existing_number(): void
    {
        $year = now()->year;
        foreach (["BLT-{$year}-0004", "BLT-{$year}-0009", "VAWC-{$year}-0002"] as $number) {
            BlotterRecord::create([
                'barangay_id' => $this->barangay->id,
                'incident_type' => 'Noise',
                'case_number' => $number,
                'status' => BlotterStatus::Pending,
                'official_entry_date' => now(),
            ]);
        }

        $migration = require database_path('migrations/2026_09_30_140000_create_case_number_sequences_table.php');
        $migration->down();
        $migration->up();

        $this->assertSame("BLT-{$year}-0010", $this->nextNumber('BLT'));
        $this->assertSame("VAWC-{$year}-0003", $this->nextNumber('VAWC'));
    }
}
