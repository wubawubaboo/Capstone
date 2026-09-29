<?php

namespace Tests\Feature;

use App\Enums\BlotterStatus;
use App\Models\Barangay;
use App\Models\BlotterRecord;
use App\Models\MediationSchedule;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class DataProtectionTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private Barangay $barangay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->barangay = Barangay::create(['name' => 'San Nicolas']);
    }

    private function makeBlotter(array $overrides = []): BlotterRecord
    {
        return BlotterRecord::create(array_merge([
            'barangay_id' => $this->barangay->id,
            'incident_type' => 'Physical abuse',
            'incident_description' => 'Victim narrative',
            'case_number' => 'VAWC-2026-0001',
            'status' => BlotterStatus::Pending,
            'official_entry_date' => now(),
        ], $overrides));
    }

    private function rawColumn(string $table, int $id, string $column): ?string
    {
        return DB::table($table)->where('id', $id)->value($column);
    }

    public function test_confidential_text_is_encrypted_in_the_database(): void
    {
        $report = Report::create(['user_id' => $this->makeUser('resident')->id, 'incident_type' => 'Crime', 'description' => 'Report details', 'status' => 'pending']);
        $blotter = $this->makeBlotter();
        $mediation = MediationSchedule::create(['blotter_record_id' => $blotter->id, 'meeting_number' => 1, 'scheduled_date' => now(), 'status' => 'Scheduled', 'notes' => 'Mediation notes']);

        foreach ([
            ['reports', $report->id, 'description', 'Report details'],
            ['blotter_records', $blotter->id, 'incident_description', 'Victim narrative'],
            ['mediation_schedules', $mediation->id, 'notes', 'Mediation notes'],
        ] as [$table, $id, $column, $plain]) {
            $raw = $this->rawColumn($table, $id, $column);
            $this->assertNotSame($plain, $raw, "{$table}.{$column} is stored in plain text.");
            $this->assertSame($plain, Crypt::decryptString($raw));
        }

        $this->assertSame('Victim narrative', $blotter->fresh()->incident_description);
    }

    public function test_party_names_are_encrypted_in_the_database(): void
    {
        $blotter = $this->makeBlotter(['complainant_name' => 'Maria Santos', 'receiver_name' => 'Juan Dela Cruz']);

        foreach (['complainant_name' => 'Maria Santos', 'receiver_name' => 'Juan Dela Cruz'] as $column => $name) {
            $raw = $this->rawColumn('blotter_records', $blotter->id, $column);
            $this->assertNotSame($name, $raw, "blotter_records.{$column} is stored in plain text.");
            $this->assertSame($name, $blotter->fresh()->{$column});
        }
    }

    public function test_name_migration_encrypts_existing_names_and_is_safe_to_rerun(): void
    {
        $blotter = $this->makeBlotter();
        DB::table('blotter_records')->where('id', $blotter->id)->update(['complainant_name' => 'Old Plain Name', 'receiver_name' => null]);

        $migration = require database_path('migrations/2026_09_30_130000_encrypt_blotter_party_names.php');
        $migration->up();
        $migration->up();

        $fresh = $blotter->fresh();
        $this->assertSame('Old Plain Name', $fresh->complainant_name);
        $this->assertNull($fresh->receiver_name);
        $this->assertNotSame('Old Plain Name', $this->rawColumn('blotter_records', $blotter->id, 'complainant_name'));
    }

    public function test_migration_encrypts_existing_plain_text_and_is_safe_to_rerun(): void
    {
        $blotter = $this->makeBlotter();
        DB::table('blotter_records')->where('id', $blotter->id)->update(['incident_description' => 'Old plain narrative']);

        $migration = require database_path('migrations/2026_09_30_110000_encrypt_confidential_text_columns.php');
        $migration->up();
        $migration->up();

        $this->assertSame('Old plain narrative', $blotter->fresh()->incident_description);
        $this->assertNotSame('Old plain narrative', $this->rawColumn('blotter_records', $blotter->id, 'incident_description'));
    }

    public function test_id_photos_are_encrypted_at_rest_and_decrypted_for_the_secretary(): void
    {
        Storage::fake();
        $photo = UploadedFile::fake()->image('id.jpg');
        $original = $photo->get();

        $this->post('/register', [
            'name' => 'New Resident',
            'phone_number' => '09123456789',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'barangay_id' => $this->barangay->id,
            'address' => 'Purok 1',
            'date_of_birth' => '1995-01-01',
            'start_of_residency' => 2010,
            'id_photo' => $photo,
            'selfie_id_photo' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertSessionHasNoErrors();

        $resident = \App\Models\User::where('phone_number', '09123456789')->firstOrFail();
        $this->assertStringEndsWith('.enc', $resident->id_photo_path);
        $this->assertNotSame($original, Storage::get($resident->id_photo_path));

        $response = $this->actingAs($this->makeUser('secretary'))
            ->get(route('secretary.account-requests.id-photo', $resident))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame($original, $response->getContent());
    }

    public function test_report_attachments_are_encrypted_at_rest_and_viewable_by_the_reporter(): void
    {
        Storage::fake();
        $resident = $this->makeUser('resident');
        $photo = UploadedFile::fake()->image('evidence.png');
        $original = $photo->get();

        $this->actingAs($resident)->post(route('resident.reports.store'), [
            'incident_type' => 'Crime', 'description' => 'x', 'latitude' => null, 'longitude' => null, 'attachment' => $photo,
        ]);

        $report = Report::firstOrFail();
        $this->assertNotSame($original, Storage::get($report->attachment_path));

        $response = $this->actingAs($resident)->get(route('resident.reports.attachment', $report))->assertOk();
        $this->assertSame($original, $response->getContent());
    }

    public function test_files_stored_before_encryption_are_still_served_and_can_be_converted(): void
    {
        Storage::fake();
        Storage::put('id_photos/legacy.jpg', 'legacy-bytes');
        $resident = $this->makeUser('resident', ['is_verified' => false, 'id_photo_path' => 'id_photos/legacy.jpg']);
        $secretary = $this->makeUser('secretary');

        $this->actingAs($secretary)->get(route('secretary.account-requests.id-photo', $resident))->assertOk();

        $this->artisan('files:encrypt-sensitive')->assertSuccessful();

        $resident->refresh();
        $this->assertSame('id_photos/legacy.jpg.enc', $resident->id_photo_path);
        Storage::assertMissing('id_photos/legacy.jpg');
        $this->assertNotSame('legacy-bytes', Storage::get($resident->id_photo_path));

        $response = $this->actingAs($secretary)->get(route('secretary.account-requests.id-photo', $resident))->assertOk();
        $this->assertSame('legacy-bytes', $response->getContent());
    }
}
