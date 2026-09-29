<?php

namespace App\Jobs;

use App\Exceptions\SmsDeliveryException;
use App\Services\PhilSmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSmsJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [30, 120];

    public function __construct(public string $recipient, public string $message)
    {
    }

    public function handle(PhilSmsService $smsService): void
    {
        // Temporary failures throw SmsDeliveryException, which the queue retries.
        if (!$smsService->sendSms($this->recipient, $this->message)) {
            // Permanently undeliverable (bad number, rejected): retrying won't help.
            $this->fail(new SmsDeliveryException("SMS to {$this->recipient} was rejected and will not be retried."));
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::critical("SMS to {$this->recipient} permanently failed: {$exception->getMessage()}");
    }
}
