<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CodGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order, array $data = []): array
    {
        // COD is instant approval in terms of "payment flow initiation"
        return [
            'redirect_url' => null, // No redirection needed
            'transaction_id' => 'COD-' . Str::random(8),
            'payload' => []
        ];
    }

    public function handleCallback(Request $request): PaymentResult
    {
        // COD doesn't have a callback usually, but if hit, we treat as success
        return new PaymentResult(true, 'COD', 'Cash on Delivery confirmed');
    }
}
