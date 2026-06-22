<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class QuotationConverted extends Notification
{
    public function __construct(
        public string $quotationNumber,
        public string $customerName,
        public int $quotationId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'quotation_converted',
            'title' => 'Quotation Converted',
            'description' => "Quotation {$this->quotationNumber} for {$this->customerName} was converted to invoice",
            'action_url' => route('quotations.edit', $this->quotationId),
            'icon' => 'arrow-right-circle',
            'color' => 'amber',
        ];
    }
}
