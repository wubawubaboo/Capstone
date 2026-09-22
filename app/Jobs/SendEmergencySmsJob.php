<?php

namespace App\Jobs;

use App\Services\PhilSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SendEmergencySmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Queue-level retries — on top of PhilSmsService's own per-message retries. */
    public $tries = 3;
    public $backoff = 30;

    protected $officers;
    protected $message;

    public function __construct(Collection $officers, string $message)
    {
        $this->officers = $officers;
        $this->message = $message;
    }

    public function handle(PhilSmsService $smsService): void
    {
        $failedNumbers = [];

        foreach ($this->officers as $officer) {
            if (strlen($officer->phone_number) < 10) {
                continue;
            }

            if (!$smsService->sendSms($officer->phone_number, $this->message)) {
                $failedNumbers[] = $officer->phone_number;
            }
        }

        if (!empty($failedNumbers)) {
            // Throwing marks the job as failed so Laravel retries it (per $tries/$backoff)
            // rather than silently treating an undelivered emergency alert as a success.
            throw new \RuntimeException(
                'Failed to deliver emergency SMS to: ' . implode(', ', $failedNumbers)
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('SendEmergencySmsJob permanently failed after all retries: ' . $exception->getMessage());
    }
}
