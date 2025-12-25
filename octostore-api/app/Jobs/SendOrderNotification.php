<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Notifications\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $order;
    public $event;

    public function __construct(Order $order, string $event)
    {
        $this->order = $order;
        $this->event = $event;
    }

    public function handle(NotificationService $service): void
    {
        // Pass service dependency injection handled by Laravel
        $service->sendOrderNotification($this->order, $this->event);
    }
}
