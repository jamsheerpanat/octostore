<?php

namespace App\Services\Notifications\Channels;

use Illuminate\Support\Facades\Log;

class PushChannel implements NotificationChannelInterface
{
    public function send(string $recipient, string $content, array $meta = []): bool
    {
        // Placeholder for Firebase/FCM
        // recipient would be fcm_token
        Log::info("Push Mock Send -> To: $recipient | Body: $content");
        return true;
    }
}
