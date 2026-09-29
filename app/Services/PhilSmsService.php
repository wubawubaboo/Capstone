<?php

namespace App\Services;

use App\Exceptions\SmsDeliveryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PhilSmsService
{
    protected $enabled;
    protected $token;
    protected $senderId;
    protected $baseUrl = 'https://app.philsms.com/api/v3/sms/send';

    public function __construct()
    {
        $this->enabled = (bool) config('services.philsms.enabled');
        $this->token = config('services.philsms.token');
        $this->senderId = config('services.philsms.sender_id');
    }

    public function sendSms(string $recipient, string $message): bool
    {
        if (!$this->enabled) {
            Log::info('PhilSMS disabled (SMS_ENABLED=false); message not sent.', [
                'recipient' => $recipient,
                'message' => $message,
            ]);

            return true;
        }

        if (empty($this->token)) {
            Log::error('PhilSMS: no API token configured; message not sent.', ['recipient' => $recipient]);

            return false;
        }

        $recipient = $this->normalizeRecipient($recipient);

        if (!preg_match('/^63\d{10}$/', $recipient)) {
            Log::error('PhilSMS: invalid recipient number; message not sent.', ['recipient' => $recipient]);

            return false;
        }

        try {
            $response = Http::withToken($this->token)->timeout(15)->post($this->baseUrl, [
                'recipient' => $recipient,
                'sender_id' => $this->senderId,
                'type' => 'plain',
                'message' => $message,
            ]);
        } catch (ConnectionException $e) {
            throw new SmsDeliveryException("PhilSMS: connection error: {$e->getMessage()}", previous: $e);
        }

        if ($response->successful()) {
            return true;
        }

        if ($response->clientError()) {
            Log::error('PhilSMS: request rejected, not retrying.', [
                'recipient' => $recipient,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        throw new SmsDeliveryException("PhilSMS: server error (HTTP {$response->status()}).");
    }

    private function normalizeRecipient(string $recipient): string
    {
        $recipient = preg_replace('/\D/', '', $recipient);

        if (str_starts_with($recipient, '09')) {
            return '63' . substr($recipient, 1);
        }

        return $recipient;
    }
}
