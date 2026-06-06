<?php

use App\Models\Payment;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Payments')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    public function delete(Payment $payment): void
    {
        $payment->delete();
        $this->dispatch('payment-deleted');
    }

    public function with(): array
    {
        return [
            'payments' => Payment::query()
                ->with(['invoice:id,invoice_number,total,paid_amount', 'creator:id,name'])
                ->when($this->search, fn($q, $search) => $q->where(function($q) use ($search) {
                    $q->whereHas('invoice', fn($q) => $q->where('invoice_number', 'like', "%{$search}%"))
                      ->orWhere('receipt_number', 'like', "%{$search}%")
                      ->orWhere('reference', 'like', "%{$search}%")
                      ->orWhere('payment_method', 'like', "%{$search}%")
                      ->orWhere('amount', 'like', "%{$search}%");
                }))
                ->latest()
                ->paginate(15),
        ];
    }
}; ?>

<div style="width: 80%; margin: 0 auto;">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Payments') }}</flux:heading>
        <div class="flex items-center gap-3">
            <flux:input wire:model.live="search" placeholder="{{ __('Search payments...') }}" class="w-64" />
            <flux:button href="{{ route('payments.export') }}" variant="ghost" icon="arrow-down-tray">
                {{ __('Export') }}
            </flux:button>
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column sortable>{{ __('Receipt') }}</flux:table.column>
            <flux:table.column sortable>{{ __('Invoice') }}</flux:table.column>
            <flux:table.column sortable>{{ __('Amount') }}</flux:table.column>
            <flux:table.column sortable>{{ __('Date') }}</flux:table.column>
            <flux:table.column sortable>{{ __('Method') }}</flux:table.column>
            <flux:table.column>{{ __('Reference') }}</flux:table.column>
            <flux:table.column>{{ __('Recorded By') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($payments as $payment)
                <flux:table.row>
                    <flux:table.cell class="font-mono text-xs font-medium text-indigo-500">{{ $payment->receipt_number ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="font-medium">{{ $payment->invoice?->invoice_number ?? '—' }}</flux:table.cell>
                    <flux:table.cell>UGX {{ number_format($payment->amount, 2) }}</flux:table.cell>
                    <flux:table.cell>{{ $payment->payment_date->format('d M Y') }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge variant="pill" size="sm" color="lime">
                            {{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-neutral-500">{{ $payment->reference ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-neutral-500">{{ $payment->creator?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-1">
                            <flux:button variant="ghost" size="sm" icon="trash"
                                wire:click="delete({{ $payment->id }})"
                                wire:confirm="{{ __('Delete this receipt?') }}"
                                class="cursor-pointer" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8">
                        <div class="flex flex-col items-center py-12 text-center">
                            <flux:heading class="text-neutral-400">{{ __('No receipts recorded yet') }}</flux:heading>
                            <flux:subheading class="mt-1 text-neutral-400">{{ __('Record payments against invoices to see them here.') }}</flux:subheading>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $payments->links() }}
    </div>

    <flux:toast on="payment-deleted" variant="success" message="{{ __('Payment deleted.') }}" />
</div>
