<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Jobs\SendOrderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderNotifications
{
    public function handleOrderCreated(OrderCreated $event): void
    {
        SendOrderNotification::dispatch($event->order, 'order_created');
    }

    public function handleOrderStatusUpdated(OrderStatusUpdated $event): void
    {
        // Only send if status actually changed
        if ($event->oldStatus !== $event->newStatus) {
            SendOrderNotification::dispatch($event->order, 'order_status_changed');
            
            // Could also have specific events like 'order_delivered' if template exists
            // But 'order_status_changed' is generic enough if body uses {status}
        }
    }
}
