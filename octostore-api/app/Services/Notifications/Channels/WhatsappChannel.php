<?php

namespace App\Services\Notifications\Channels;

use Illuminate\Support\Facades\Log;

class WhatsappChannel implements NotificationChannelInterface
{
    public function send(string $recipient, string $content, array $meta = []): bool
    {
        // Placeholder for WhatsApp Business API
        Log::info("WhatsApp Mock Send -> To: $recipient | Body: $content");
        return true;
    }
}
