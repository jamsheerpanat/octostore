<?php

namespace App\Services\Notifications\Channels;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailChannel implements NotificationChannelInterface
{
    public function send(string $recipient, string $content, array $meta = []): bool
    {
        try {
            $subject = $meta['subject'] ?? 'Notification';
            
            // Using raw mail for simplicity demonstration. 
            // Ideally use Mailable classes for better structure.
            Mail::raw($content, function ($message) use ($recipient, $subject) {
                $message->to($recipient)
                        ->subject($subject);
            });
            
            return true;
        } catch (\Exception $e) {
            Log::error("Email send failed: " . $e->getMessage());
            return false;
        }
    }
}
