<?php

namespace Tests\Feature;

use App\Enums\BlotterStatus;
use App\Enums\MediationStatus;
use App\Exceptions\SmsDeliveryException;
use App\Jobs\SendSmsJob;
use App\Models\Barangay;
use App\Models\BlotterRecord;
use App\Models\MediationSchedule;
use App\Models\Report;
use App\Services\OpenStreetMapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class SmsDeliveryTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private Barangay $barangay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->barangay = Barangay::create(['name' => 'San Nicolas']);
        config(['services.philsms.enabled' => true, 'services.philsms.token' => 'test-token']);
    }

    private function sms(): \App\Services\PhilSmsService
    {
        return app(\App\Services\PhilSmsService::class);
    }

    public function test_disabled_sms_is_only_logged(): void
    {
        config(['services.philsms.enabled' => false]);
        Http::fake();

        $this->assertTrue($this->sms()->sendSms('09171234567', 'Hello'));
        Http::assertNothingSent();
    }

    public function test_accepted_message_is_sent_with_normalized_number(): void
    {
        Http::fake(['app.philsms.com/*' => Http::response(['status' => 'success'])]);

        $this->assertTrue($this->sms()->sendSms('0917-123-4567', 'Hello'));
        Http::assertSent(fn ($request) => $request['recipient'] === '639171234567' && $request['message'] === 'Hello');
    }

    public function test_rejected_message_returns_false(): void
    {
        Http::fake(['app.philsms.com/*' => Http::response(['status' => 'error'], 422)]);

        $this->assertFalse($this->sms()->sendSms('09171234567', 'Hello'));
    }

    public function test_provider_outage_throws_so_the_job_retries(): void
    {
        Http::fake(['app.philsms.com/*' => Http::response('down', 503)]);

        $this->expectException(SmsDeliveryException::class);
        $this->sms()->sendSms('09171234567', 'Hello');
    }

    public function test_invalid_number_is_rejected_without_calling_the_provider(): void
    {
        Http::fake();

        $this->assertFalse($this->sms()->sendSms('12345', 'Hello'));
        Http::assertNothingSent();
    }

    public function test_job_gives_up_on_a_rejected_message(): void
    {
        $job = (new SendSmsJob('12345', 'Hello'))->withFakeQueueInteractions();

        $job->handle($this->sms());

        $job->assertFailed();
    }

    public function test_job_lets_temporary_failures_retry(): void
    {
        Http::fake(['app.philsms.com/*' => Http::response('down', 503)]);
        $job = (new SendSmsJob('09171234567', 'Hello'))->withFakeQueueInteractions();

        $this->expectException(SmsDeliveryException::class);
        $job->handle($this->sms());
    }

    public function test_sos_queues_one_sms_per_officer_in_the_barangay(): void
    {
        Queue::fake();
        $this->mock(OpenStreetMapService::class)
            ->shouldReceive('reverseGeocode')
            ->andReturn(['full_address' => 'Purok 1, San Nicolas', 'village' => 'San Nicolas']);

        $officerA = $this->makeUser('barangay_police');
        $officerB = $this->makeUser('barangay_police');
        $this->makeUser('barangay_police', ['barangay_id' => Barangay::create(['name' => 'Elsewhere'])->id]);

        $this->actingAs($this->makeUser('resident'))
            ->post(route('resident.sos.trigger'), ['emergency_type' => 'Fire', 'latitude' => 14.6, 'longitude' => 121.0])
            ->assertSessionHas('success');

        Queue::assertPushed(SendSmsJob::class, 2);
        foreach ([$officerA, $officerB] as $officer) {
            Queue::assertPushed(SendSmsJob::class, fn ($job) => $job->recipient === $officer->phone_number && str_contains($job->message, 'URGENT SOS'));
        }
        $this->assertSame(1, Report::where('incident_type', Report::SOS_TYPE)->count());
    }

    public function test_approving_an_account_queues_an_sms_to_the_resident(): void
    {
        Queue::fake();
        $resident = $this->makeUser('resident', ['is_verified' => false]);

        $this->actingAs($this->makeUser('secretary'))
            ->post(route('secretary.account-requests.approve', $resident))
            ->assertSessionHas('success');

        Queue::assertPushed(SendSmsJob::class, fn ($job) => $job->recipient === $resident->phone_number);
    }

    public function test_hearing_reminder_is_queued_for_the_registered_complainant(): void
    {
        Queue::fake();
        $complainant = $this->makeUser('resident');

        $blotter = BlotterRecord::create([
            'barangay_id' => $this->barangay->id,
            'complainant_id' => $complainant->id,
            'complainant_name' => $complainant->full_name,
            'incident_type' => 'Noise',
            'case_number' => 'BLT-2026-0001',
            'status' => BlotterStatus::UnderMediation,
            'official_entry_date' => now(),
        ]);
        MediationSchedule::create([
            'blotter_record_id' => $blotter->id,
            'meeting_number' => 1,
            'scheduled_date' => now()->addDays(2)->setTime(10, 0),
            'status' => MediationStatus::Scheduled,
        ]);

        $this->artisan('schedule:test', ['--name' => 'mediation-hearing-reminders'])->assertSuccessful();

        Queue::assertPushed(SendSmsJob::class, fn ($job) => $job->recipient === $complainant->phone_number && str_contains($job->message, '10:00 AM'));
    }
}
