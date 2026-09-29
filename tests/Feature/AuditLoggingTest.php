<?php

namespace Tests\Feature;

use App\Enums\BlotterStatus;
use App\Enums\ReportStatus;
use App\Models\Barangay;
use App\Models\BlotterRecord;
use App\Models\DocumentType;
use App\Models\Report;
use App\Models\SystemLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private Barangay $barangay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->barangay = Barangay::create(['name' => 'San Nicolas']);
    }

    private function assertLogged(string $actionType, string $descriptionContains): SystemLog
    {
        $log = SystemLog::where('action_type', $actionType)
            ->where('description', 'like', "%{$descriptionContains}%")
            ->first();

        $this->assertNotNull($log, "No {$actionType} log containing \"{$descriptionContains}\".");

        return $log;
    }

    public function test_sign_in_and_sign_out_are_logged(): void
    {
        $secretary = $this->makeUser('secretary');

        $this->post('/portal/secure-login', ['phone_number' => $secretary->phone_number, 'password' => 'password']);
        $this->post(route('secretary.logout'));

        $this->assertSame($secretary->id, $this->assertLogged('LOGIN', 'signed in')->actor_id);
        $this->assertLogged('LOGOUT', 'signed out');
    }

    public function test_failed_sign_in_is_logged_without_the_password(): void
    {
        $resident = $this->makeUser('resident');

        $this->post('/login', ['phone_number' => $resident->phone_number, 'password' => 'not-it']);
        $this->post('/login', ['phone_number' => '09999999999', 'password' => 'guess']);

        $this->assertLogged('LOGIN_FAILED', "{$resident->phone_number} (wrong password)");
        $unknown = $this->assertLogged('LOGIN_FAILED', '09999999999 (unknown phone number)');
        $this->assertNull($unknown->barangay_id);
        $this->assertSame(0, SystemLog::where('description', 'like', '%not-it%')->orWhere('description', 'like', '%guess%')->count());
    }

    public function test_lockout_is_logged(): void
    {
        $resident = $this->makeUser('resident');

        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['phone_number' => $resident->phone_number, 'password' => 'wrong']);
        }

        $this->assertLogged('LOCKOUT', $resident->phone_number);
    }

    public function test_self_registration_is_logged(): void
    {
        Storage::fake();

        $this->post('/register', [
            'name' => 'New Resident',
            'phone_number' => '09123456789',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'barangay_id' => $this->barangay->id,
            'address' => 'Purok 1',
            'date_of_birth' => '1995-01-01',
            'start_of_residency' => 2010,
            'id_photo' => UploadedFile::fake()->image('id.jpg'),
            'selfie_id_photo' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $this->assertLogged('CREATE', 'Self-registered resident account for New Resident');
    }

    public function test_vawc_report_is_logged_without_its_incident_type(): void
    {
        $resident = $this->makeUser('resident');

        $this->actingAs($resident)->post(route('resident.reports.store'), ['incident_type' => 'Crime', 'description' => 'x', 'is_vawc' => true]);
        $this->actingAs($resident)->post(route('resident.reports.store'), ['incident_type' => 'Fire', 'description' => 'y']);

        $vawc = $this->assertLogged('CREATE', 'Filed confidential VAWC report');
        $this->assertStringNotContainsString('Crime', $vawc->description);
        $this->assertLogged('CREATE', '(Fire)');
    }

    public function test_resident_document_request_is_logged(): void
    {
        $type = DocumentType::create(['name' => 'Barangay Clearance', 'is_active' => true]);

        $this->actingAs($this->makeUser('resident'))
            ->post(route('resident.documents.store'), ['document_type_id' => $type->id, 'purpose' => 'Employment'])
            ->assertSessionHasNoErrors();

        $this->assertLogged('CREATE', 'Requested Barangay Clearance (Ref #DOC-');
    }

    public function test_secretary_resolving_a_case_is_logged(): void
    {
        $blotter = BlotterRecord::create([
            'barangay_id' => $this->barangay->id,
            'incident_type' => 'Noise',
            'case_number' => 'BLT-2026-0001',
            'status' => BlotterStatus::Pending,
            'official_entry_date' => now(),
        ]);

        $this->actingAs($this->makeUser('secretary'))->post(route('secretary.cases.resolve', $blotter));

        $this->assertLogged('UPDATE', 'Resolved Case #BLT-2026-0001');
    }

    public function test_document_type_changes_are_logged(): void
    {
        $type = DocumentType::create(['name' => 'Clearance', 'is_active' => true]);

        $this->actingAs($this->makeUser('secretary'))->post(route('secretary.document-types.toggle-active', $type));

        $this->assertLogged('UPDATE', "Deactivated document type 'Clearance'");
    }

    public function test_citywide_admin_actions_can_be_logged_without_a_barangay(): void
    {
        $admin = $this->makeUser('admin', ['barangay_id' => null]);

        $this->actingAs($admin)->post(route('admin.accounts.store'), [
            'full_name' => 'Second Admin',
            'phone_number' => '09120000000',
            'role' => 'admin',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'address' => 'City Hall',
            'date_of_birth' => '1985-01-01',
            'start_of_residency' => 2000,
        ])->assertSessionHasNoErrors();

        $this->assertNull($this->assertLogged('CREATE', 'Created admin account for Second Admin')->barangay_id);
    }

    public function test_report_inserted_without_a_status_loads_as_pending(): void
    {
        $id = DB::table('reports')->insertGetId([
            'user_id' => $this->makeUser('resident')->id,
            'incident_type' => 'Fire',
            'description' => 'x',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(ReportStatus::Pending, Report::find($id)->status);
    }
}
