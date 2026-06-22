<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CustomerCreated extends Notification
{
    public function __construct(
        public string $customerName,
        public ?string $customerEmail,
        public int $customerId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'customer_created',
            'title' => 'New Customer',
            'description' => "{$this->customerName}" . ($this->customerEmail ? " ({$this->customerEmail})" : '') . " added",
            'action_url' => route('customers'),
            'icon' => 'user-plus',
            'color' => 'violet',
        ];
    }
}
