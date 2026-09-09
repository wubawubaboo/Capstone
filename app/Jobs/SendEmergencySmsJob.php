<?php

namespace App\Jobs;

use App\Services\PhilSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SendEmergencySmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $officers;
    protected $message;

    public function __construct(Collection $officers, string $message)
    {
        $this->officers = $officers;
        $this->message = $message;
    }

    public function handle(PhilSmsService $smsService): void
    {
        foreach ($this->officers as $officer) {
            if (strlen($officer->phone_number) >= 10) {
                $smsService->sendSms($officer->phone_number, $this->message);
            }
        }
    }
}