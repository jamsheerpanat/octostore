<?php

namespace App\Services\Payment;

class PaymentResult
{
    public bool $success;
    public string $transactionId;
    public string $message;
    public ?array $rawResponse;

    public function __construct(bool $success, string $transactionId, string $message = '', ?array $rawResponse = null)
    {
        $this->success = $success;
        $this->transactionId = $transactionId;
        $this->message = $message;
        $this->rawResponse = $rawResponse;
    }
}
