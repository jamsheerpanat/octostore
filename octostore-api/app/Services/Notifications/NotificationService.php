<?php

namespace App\Services\Notifications;

use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Order;
use App\Services\Notifications\Channels\EmailChannel;
use App\Services\Notifications\Channels\PushChannel;
use App\Services\Notifications\Channels\SmsChannel;
use App\Services\Notifications\Channels\WhatsappChannel;
use App\Services\Notifications\Channels\NotificationChannelInterface;
use Illuminate\Support\Str;

class NotificationService
{
    private function getChannel(string $channel): NotificationChannelInterface
    {
        return match ($channel) {
            'email' => new EmailChannel(),
            'sms' => new SmsChannel(),
            'whatsapp' => new WhatsappChannel(),
            'push' => new PushChannel(),
            default => throw new \Exception("Channel $channel not supported"),
        };
    }

    public function sendOrderNotification(Order $order, string $event)
    {
        // 1. Fetch active templates for this event
        $templates = NotificationTemplate::where('event', $event)
            ->where('is_active', true)
            ->get();
            
        if ($templates->isEmpty()) {
            return;
        }
        
        $customer = $order->user;
        $orderData = [
            '{order_number}' => $order->order_number,
            '{customer_name}' => $customer ? $customer->name : ($order->billing_address['first_name'] ?? 'Guest'),
            '{status}' => $order->status,
            '{total}' => $order->grand_total . ' ' . $order->currency
        ];
        
        foreach ($templates as $template) {
            $lang = 'en'; // Detect preferred lang from user or request
            $channelName = $template->channel;
            
            // Resolve Recipient
            $recipient = $this->resolveRecipient($channelName, $order);
            if (!$recipient) continue;
            
            // Resolve Content
            $subject = $template->subject[$lang] ?? ($template->subject['en'] ?? '');
            $body = $template->body[$lang] ?? ($template->body['en'] ?? '');
            
            // Replace Variables
            $subject = str_replace(array_keys($orderData), array_values($orderData), $subject);
            $body = str_replace(array_keys($orderData), array_values($orderData), $body);
            
            // Send
            $channel = $this->getChannel($channelName);
            $success = $channel->send($recipient, $body, ['subject' => $subject]);
            
            // Log
            NotificationLog::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'event' => $event,
                'channel' => $channelName,
                'recipient' => $recipient,
                'content' => $body,
                'status' => $success ? 'sent' : 'failed'
            ]);
        }
    }
    
    private function resolveRecipient($channel, $order)
    {
        // Snapshot addresses
        $billing = $order->billing_address;
        
        return match($channel) {
            'email' => $order->user ? $order->user->email : ($billing['email'] ?? null),
            'sms', 'whatsapp' => $billing['phone'] ?? null,
            'push' => $order->user ? ($order->user->fcm_token ?? null) : null,
            default => null
        };
    }
}
