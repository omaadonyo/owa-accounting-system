<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class QuotationCreated extends Notification
{
    public function __construct(
        public string $quotationNumber,
        public string $customerName,
        public float $total,
        public int $quotationId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'quotation_created',
            'title' => 'Quotation Created',
            'description' => "Quotation {$this->quotationNumber} for {$this->customerName} — " . number_format($this->total, 0),
            'action_url' => route('quotations.edit', $this->quotationId),
            'icon' => 'document-text',
            'color' => 'indigo',
        ];
    }
}
