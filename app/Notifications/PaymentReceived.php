<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification
{
    public function __construct(
        public string $receiptNumber,
        public string $customerName,
        public float $amount,
        public int $paymentId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'payment_recorded',
            'title' => 'Payment Recorded',
            'description' => number_format($this->amount, 0) . " received from {$this->customerName} — Receipt #{$this->receiptNumber}",
            'action_url' => route('invoices.edit', $this->paymentId),
            'icon' => 'credit-card',
            'color' => 'teal',
        ];
    }
}
