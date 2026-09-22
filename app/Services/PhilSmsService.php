<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PhilSmsService
{
    protected $token;
    protected $senderId;
    protected $baseUrl = 'https://app.philsms.com/api/v3/sms/send';

    private const MAX_ATTEMPTS = 3;
    private const RETRY_DELAY_US = 300_000;

    public function __construct()
    {
        $this->token = config('services.philsms.token');
        $this->senderId = config('services.philsms.sender_id');
    }


    public function sendSms($recipient, $message): bool
    {
        return true;
        // if (empty($this->token)) {
        //     Log::error('PhilSMS: no API token configured; message not sent.', ['recipient' => $recipient]);
        //     return false;
        // }

        // $recipient = $this->normalizeRecipient($recipient);

        // for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
        //     try {
        //         $response = Http::withToken($this->token)->post($this->baseUrl, [
        //             'recipient' => $recipient,
        //             'sender_id' => $this->senderId,
        //             'type' => 'plain',
        //             'message' => $message,
        //         ]);

        //         if ($response->successful()) {
        //             return true;
        //         }

        //         if ($response->clientError()) {
        //             Log::error('PhilSMS: request rejected, not retrying.', [
        //                 'recipient' => $recipient,
        //                 'status' => $response->status(),
        //                 'body' => $response->body(),
        //             ]);

        //             return false;
        //         }

        //         Log::warning("PhilSMS: server error on attempt {$attempt}/" . self::MAX_ATTEMPTS . '.', [
        //             'recipient' => $recipient,
        //             'status' => $response->status(),
        //         ]);
        //     } catch (ConnectionException $e) {
        //         Log::warning("PhilSMS: connection error on attempt {$attempt}/" . self::MAX_ATTEMPTS . ": {$e->getMessage()}", [
        //             'recipient' => $recipient,
        //         ]);
        //     }

        //     if ($attempt < self::MAX_ATTEMPTS) {
        //         usleep(self::RETRY_DELAY_US * $attempt);
        //     }
        // }

        // Log::error('PhilSMS: failed to deliver message after ' . self::MAX_ATTEMPTS . ' attempts.', [
        //     'recipient' => $recipient,
        // ]);

        // return false;
    }

    private function normalizeRecipient(string $recipient): string
    {
        if (str_starts_with($recipient, '09')) {
            return '63' . substr($recipient, 1);
        }

        return $recipient;
    }
}
