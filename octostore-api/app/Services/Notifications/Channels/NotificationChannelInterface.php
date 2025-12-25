<?php

namespace App\Services\Notifications\Channels;

interface NotificationChannelInterface
{
    public function send(string $recipient, string $content, array $meta = []): bool;
}
