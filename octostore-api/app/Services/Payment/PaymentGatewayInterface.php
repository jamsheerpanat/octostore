<?php

namespace App\Services\Payment;

use App\Models\Order;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Initiate a payment request.
     * 
     * @param Order $order
     * @param array $data Extra data (token, metadata)
     * @return array ['redirect_url' => string|null, 'transaction_id' => string, 'payload' => array]
     */
    public function initiate(Order $order, array $data = []): array;

    /**
     * Handle the callback/webhook from the provider.
     * 
     * @param Request $request
     * @return PaymentResult
     */
    public function handleCallback(Request $request): PaymentResult;
}
