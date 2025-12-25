<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class MockGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order, array $data = []): array
    {
        $transactionId = 'MOCK-' . Str::random(12);
        
        // Generate a signed URL to our own mock page
        $callbackUrl = route('payment.mock.page', [
            'order_id' => $order->id, 
            'amount' => $order->grand_total,
            'transaction_id' => $transactionId
        ]);

        return [
            'redirect_url' => $callbackUrl,
            'transaction_id' => $transactionId,
            'payload' => []
        ];
    }

    public function handleCallback(Request $request): PaymentResult
    {
        $status = $request->input('status'); // success or failed
        $trx = $request->input('transaction_id');
        
        if ($status === 'success') {
            return new PaymentResult(true, $trx, 'Mock payment successful', $request->all());
        }
        
        return new PaymentResult(false, $trx, 'Mock payment failed', $request->all());
    }
}
