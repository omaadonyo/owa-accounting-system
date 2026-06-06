<?php

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Invoices')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    public function delete(Invoice $invoice): void
    {
        $invoice->items()->delete();
        $invoice->delete();
        Flux::toast(variant: 'success', text: __('Invoice deleted.'));
    }

    public function exportPdf(Invoice $invoice)
    {
        $invoice->load('items', 'payments', 'customer', 'business');

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $invoice->invoice_number . '.pdf'
        );
    }
}; ?>

<div class="mx-auto" style="width: 80%;">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Invoices') }}</flux:heading>
            <flux:subheading class="mt-1">{{ __('Create and manage invoices for your customers.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" :href="route('invoices.create')" wire:navigate>
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
            {{ __('New Invoice') }}
        </flux:button>
    </div>

    <div class="mt-6">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search invoices...')" clearable class="w-72" />
    </div>

    <div class="mt-4">
        <flux:table :paginate="Invoice::where('business_id', auth()->user()->business->id)
            ->when($this->search, fn($q) => $q->where(function($q) {
                $q->where('invoice_number', 'like', '%'.$this->search.'%')
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', '%'.$this->search.'%'));
            }))
            ->orderBy('id', 'desc')
            ->paginate(10)">
            <flux:table.columns>
                <flux:table.column>{{ __('Number') }}</flux:table.column>
                <flux:table.column>{{ __('Customer') }}</flux:table.column>
                <flux:table.column>{{ __('Issue Date') }}</flux:table.column>
                <flux:table.column>{{ __('Due Date') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Total') }}</flux:table.column>
                <flux:table.column>{{ __('Paid') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse (Invoice::where('business_id', auth()->user()->business->id)
                    ->when($this->search, fn($q) => $q->where(function($q) {
                        $q->where('invoice_number', 'like', '%'.$this->search.'%')
                          ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', '%'.$this->search.'%'));
                    }))
                    ->orderBy('id', 'desc')
                    ->paginate(10) as $invoice)
                    <flux:table.row :key="$invoice->id">
                        <flux:table.cell class="font-mono text-xs font-medium">{{ $invoice->invoice_number }}</flux:table.cell>
                        <flux:table.cell>{{ $invoice->customer?->name ?? __('Walk-in') }}</flux:table.cell>
                        <flux:table.cell>{{ $invoice->issue_date->format('d M Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $invoice->due_date?->format('d M Y') ?? '—' }}</flux:table.cell>
                        <flux:table.cell align="end" class="font-medium">UGX {{ number_format($invoice->total, 2) }}</flux:table.cell>
                        <flux:table.cell class="font-medium">
                            @if ((float) $invoice->paid_amount >= (float) $invoice->total)
                                <span class="text-green-600">UGX {{ number_format($invoice->paid_amount, 2) }}</span>
                            @elseif ((float) $invoice->paid_amount > 0)
                                <span class="text-amber-600">UGX {{ number_format($invoice->paid_amount, 2) }}</span>
                            @else
                                <span class="text-neutral-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :variant="match($invoice->status) { 'draft' => 'ghost', 'sent' => 'primary', 'paid' => 'success', 'overdue' => 'warning', 'cancelled' => 'danger', 'partial' => 'warning', default => 'ghost' }" size="sm">
                                {{ ucfirst($invoice->status) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button wire:click="exportPdf({{ $invoice->id }})" variant="ghost" size="sm" icon="arrow-down-tray" class="cursor-pointer" title="{{ __('Download PDF') }}" />
                                <flux:button :href="route('invoices.edit', $invoice->id)" variant="ghost" size="sm" icon="pencil-square" wire:navigate />
                                <flux:button :href="route('invoices.edit', $invoice->id) . '#receipts'" variant="ghost" size="sm" icon="credit-card" wire:navigate class="cursor-pointer" title="{{ __('Receipts') }}" />
                                <flux:button wire:click="delete({{ $invoice->id }})" variant="ghost" size="sm" icon="trash" class="text-red-500 hover:text-red-700!" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <flux:heading class="text-zinc-500 dark:text-zinc-400">{{ __('No invoices yet') }}</flux:heading>
                                <flux:subheading class="mt-1">{{ __('Create your first invoice to bill a customer.') }}</flux:subheading>
                                <flux:button variant="primary" :href="route('invoices.create')" wire:navigate class="mt-4">{{ __('New Invoice') }}</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
