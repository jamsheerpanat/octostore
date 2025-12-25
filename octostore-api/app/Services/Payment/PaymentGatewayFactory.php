<?php

namespace App\Services\Payment;

use App\Services\Payment\Gateways\CodGateway;
use App\Services\Payment\Gateways\KnetGateway;
use App\Services\Payment\Gateways\MockGateway;
use Exception;

class PaymentGatewayFactory
{
    public static function make(string $gateway): PaymentGatewayInterface
    {
        switch (strtolower($gateway)) {
            case 'cod':
                return new CodGateway();
            case 'mock':
                return new MockGateway();
            case 'knet':
                return new KnetGateway();
            // case 'visa': return new CreditCardGateway();
            default:
                throw new Exception("Payment gateway [$gateway] not supported.");
        }
    }
}
