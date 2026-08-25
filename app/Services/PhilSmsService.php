<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PhilSmsService
{
    protected $token;
    protected $senderId;
    protected $baseUrl = 'https://app.philsms.com/api/v3/sms/send';

    public function __construct()
    {
        $this->token = config('services.philsms.token');
        $this->senderId = config('services.philsms.sender_id');
    }

    public function sendSms($recipient, $message)
    {
        try {
            $response = Http::withToken($this->token)->post($this->baseUrl, [
                'recipient' => $recipient,
                'sender_id' => $this->senderId,
                'type'      => 'plain',
                'message'   => $message,
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('PhilSMS Error: ' . $e->getMessage());
            return false;
        }
    }
}