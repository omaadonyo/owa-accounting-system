<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class InvoicePaid extends Notification
{
    public function __construct(
        public string $invoiceNumber,
        public string $customerName,
        public float $amount,
        public int $invoiceId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'invoice_paid',
            'title' => 'Payment Received',
            'description' => number_format($this->amount, 0) . " received for Invoice {$this->invoiceNumber} from {$this->customerName}",
            'action_url' => route('invoices.edit', $this->invoiceId),
            'icon' => 'banknotes',
            'color' => 'emerald',
        ];
    }
}
