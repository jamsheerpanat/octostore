<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function initiate(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);
        
        // Security check: ensure user owns order (if not admin)
        if ($request->user() && $order->user_id !== $request->user()->id) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $gatewayName = $request->input('gateway', 'mock'); // Default to mock for dev
        
        try {
            $gateway = PaymentGatewayFactory::make($gatewayName);
            $response = $gateway->initiate($order, $request->all());
            
            // Log Pending Payment
            Payment::create([
                'order_id' => $order->id,
                'transaction_id' => $response['transaction_id'],
                'gateway' => $gatewayName,
                'amount' => $order->grand_total,
                'currency' => $order->currency,
                'status' => 'pending',
                'gateway_response' => $response['payload'] ?? []
            ]);
            
            return response()->json([
                'data' => $response,
                'message' => 'Payment initiated'
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function callback(Request $request, $gatewayName)
    {
        try {
            $gateway = PaymentGatewayFactory::make($gatewayName);
            $result = $gateway->handleCallback($request);
            
            // Logic to find order based on transaction ID or request param
            // Assuming transaction ID or Order ID is passed back.
            // For Mock, we passed order_id in query.
            
            $orderId = $request->input('order_id'); 
            // In real world, we look up Payment by transaction_id to find order
            
            if (!$orderId) {
                // Try finding by transaction ID if available in result
                 $payment = Payment::where('transaction_id', $result->transactionId)->first();
                 $orderId = $payment ? $payment->order_id : null;
            }
            
            if (!$orderId) {
                Log::error("Payment callback received but could not link to order.", ['result' => $result]);
                return response()->json(['message' => 'Order not found'], 404);
            }
            
            $order = Order::findOrFail($orderId);
            
            // Update Payment Record
            $payment = Payment::where('order_id', $order->id)
                ->where('transaction_id', $result->transactionId) // or match loose pending
                ->first();
                
            if ($payment) {
                $payment->status = $result->success ? 'success' : 'failed';
                $payment->gateway_response = array_merge($payment->gateway_response ?? [], $result->rawResponse ?? []);
                $payment->save();
            } else {
                // Create if didn't exist (e.g. direct webhook without init)
                 Payment::create([
                    'order_id' => $order->id,
                    'transaction_id' => $result->transactionId,
                    'gateway' => $gatewayName,
                    'amount' => $order->grand_total,
                    'currency' => $order->currency,
                    'status' => $result->success ? 'success' : 'failed',
                    'gateway_response' => $result->rawResponse
                ]);
            }
            
            if ($result->success) {
                if ($order->payment_status !== 'paid') {
                    $order->payment_status = 'paid';
                    $order->status = 'confirmed'; // Auto confirm on payment?
                    $order->save();
                    
                    event(new OrderStatusUpdated($order, 'pending', 'confirmed'));
                }
                
                // Return generic success page or redirect to frontend success
                return response()->json(['status' => 'success', 'message' => 'Payment successful']);
                // In production, this would often redirect:
                // return redirect("https://frontend.com/checkout/success?order={$order->id}");
            } else {
                 return response()->json(['status' => 'failed', 'message' => $result->message]);
                 // return redirect("https://frontend.com/checkout/failed");
            }

        } catch (\Exception $e) {
            Log::error("Payment callback error: " . $e->getMessage());
             return response()->json(['message' => $e->getMessage()], 400);
        }
    }
    
    // Dev Tool: Mock Payment Page
    public function mockPage(Request $request) 
    {
        $orderId = $request->input('order_id');
        $trx = $request->input('transaction_id');
        $amount = $request->input('amount');
        
        // Return a simple HTML form to simulate bank page
        $html = "
        <html>
        <head><title>Mock Bank</title></head>
        <body style='text-align:center; padding: 50px; font-family: sans-serif;'>
            <h1>Mock Payment Gateway</h1>
            <p>Order ID: {$orderId}</p>
            <p>Transaction: {$trx}</p>
            <p>Amount: {$amount}</p>
            <hr/>
            <div style='margin-top: 20px;'>
                <a href='/api/payment/callback/mock?status=success&transaction_id={$trx}&order_id={$orderId}' style='background: green; color: white; padding: 10px 20px; text-decoration: none;'>Simulate Success</a>
                <span style='margin: 0 10px;'>OR</span>
                <a href='/api/payment/callback/mock?status=failed&transaction_id={$trx}&order_id={$orderId}' style='background: red; color: white; padding: 10px 20px; text-decoration: none;'>Simulate Failure</a>
            </div>
        </body>
        </html>
        ";
        
        return response($html);
    }
}
