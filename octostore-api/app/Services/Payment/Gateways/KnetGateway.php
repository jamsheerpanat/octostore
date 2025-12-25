<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentResult;
use Illuminate\Http\Request;

class KnetGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order, array $data = []): array
    {
        // Placeholder for KNET integration (e.g. MyFatoorah or Hesabe)
        // 1. Calculate Signature
        // 2. Build Payload
        // 3. Return Redirect URL
        
        return [
            'redirect_url' => 'https://knet-test.com/pay', // Fake
            'transaction_id' => 'KNET-PENDING',
            'payload' => ['error' => 'Not implemented yet']
        ];
    }

    public function handleCallback(Request $request): PaymentResult
    {
        // 1. Verify Signature
        // 2. Check Result Code
        
        return new PaymentResult(false, 'UNK', 'KNET Not implemented');
    }
}
