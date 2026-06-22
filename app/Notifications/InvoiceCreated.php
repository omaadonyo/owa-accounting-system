<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class InvoiceCreated extends Notification
{
    public function __construct(
        public string $invoiceNumber,
        public string $customerName,
        public float $total,
        public int $invoiceId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'invoice_created',
            'title' => 'Invoice Created',
            'description' => "Invoice {$this->invoiceNumber} for {$this->customerName} — " . number_format($this->total, 0),
            'action_url' => route('invoices.edit', $this->invoiceId),
            'icon' => 'file-invoice',
            'color' => 'blue',
        ];
    }
}
