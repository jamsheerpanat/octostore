# Payment Provider Integration Guide

## Overview
OctoStore uses a standard `PaymentGatewayInterface` to abstract specific payment provider logic. To add a new provider (e.g., Stripe, PayPal, MyFatoorah), follow these steps.

## Steps

### 1. Create Gateway Class
Create a new class in `app/Services/Payment/Gateways/` implementing `PaymentGatewayInterface`.

```php
namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\PaymentResult;
use Illuminate\Http\Request;

class StripeGateway implements PaymentGatewayInterface
{
    public function initiate(Order $order, array $data = []): array
    {
        // 1. Call Stripe API to create PaymentIntent
        // 2. Return client_secret or redirect URL
    }

    public function handleCallback(Request $request): PaymentResult
    {
        // 1. Verify webhook signature
        // 2. Extract status
        // 3. Return PaymentResult(true/false, ...)
    }
}
```

### 2. Register in Factory
Update `app/Services/Payment/PaymentGatewayFactory.php` to include your new gateway.

```php
case 'stripe':
    return new StripeGateway();
```

### 3. Add Credentials
Add necessary API keys to `.env` and `config/services.php`.

```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
```

### 4. Testing
Use the `mock` gateway during development to simulate successful and failed transactions without real API calls.
