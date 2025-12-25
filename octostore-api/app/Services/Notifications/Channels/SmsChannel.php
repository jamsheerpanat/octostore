<?php

namespace App\Services\Notifications\Channels;

use Illuminate\Support\Facades\Log;

class SmsChannel implements NotificationChannelInterface
{
    public function send(string $recipient, string $content, array $meta = []): bool
    {
        // Placeholder for SMS provider implementation (e.g. Twilio, Vonage)
        Log::info("SMS Mock Send -> To: $recipient | Body: $content");
        
        // return Http::post('sms-gateway-url', [...])->successful();
        
        return true; 
    }
}
