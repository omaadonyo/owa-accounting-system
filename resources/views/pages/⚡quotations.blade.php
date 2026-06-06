<?php

use App\Models\Invoice;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Quotations')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    public function delete(Quotation $quotation): void
    {
        $quotation->items()->delete();
        $quotation->delete();
        Flux::toast(variant: 'success', text: __('Quotation deleted.'));
    }

    public function convertToInvoice(int $id): void
    {
        $quotation = Quotation::with('items')->where('business_id', auth()->user()->business->id)->findOrFail($id);

        if ($quotation->status === 'converted') {
            Flux::toast(variant: 'warning', text: __('Already converted.'));
            return;
        }

        $last = Invoice::where('business_id', auth()->user()->business->id)->orderBy('id', 'desc')->first();
        $next = $last ? ((int) substr($last->invoice_number, -4)) + 1 : 1;
        $invoiceNumber = 'INV-' . str_pad($next, 4, '0', STR_PAD_LEFT);

        $invoice = Invoice::create([
            'business_id' => $quotation->business_id,
            'customer_id' => $quotation->customer_id,
            'quotation_id' => $quotation->id,
            'invoice_number' => $invoiceNumber,
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'subtotal' => $quotation->subtotal,
            'discount_type' => $quotation->discount_type,
            'discount_value' => $quotation->discount_value,
            'discount_amount' => $quotation->discount_amount,
            'tax_name' => $quotation->tax_name,
            'tax_rate' => $quotation->tax_rate,
            'tax_amount' => $quotation->tax_amount,
            'total' => $quotation->total,
            'notes' => $quotation->notes,
            'status' => 'draft',
            'paid_amount' => 0,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        foreach ($quotation->items as $item) {
            $invoice->items()->create([
                'type' => $item->type,
                'item_id' => $item->item_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }

        $quotation->update(['status' => 'converted', 'updated_by' => auth()->id()]);

        Flux::toast(variant: 'success', text: __('Quotation converted to invoice.'));
    }

    public function exportPdf(Quotation $quotation)
    {
        $quotation->load('items', 'customer', 'business');

        $pdf = Pdf::loadView('pdf.quotation', ['quotation' => $quotation]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $quotation->quotation_number . '.pdf'
        );
    }
}; ?>

<div class="mx-auto" style="width: 80%;">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Quotations') }}</flux:heading>
            <flux:subheading class="mt-1">{{ __('Create and manage quotations for your customers.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" :href="route('quotations.create')" wire:navigate>
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
            {{ __('New Quotation') }}
        </flux:button>
    </div>

    <div class="mt-6">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search quotations...')" clearable class="w-72" />
    </div>

    <div class="mt-4">
        <flux:table :paginate="Quotation::where('business_id', auth()->user()->business->id)
            ->when($this->search, fn($q) => $q->where(function($q) {
                $q->where('quotation_number', 'like', '%'.$this->search.'%')
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', '%'.$this->search.'%'));
            }))
            ->orderBy('id', 'desc')
            ->paginate(10)">
            <flux:table.columns>
                <flux:table.column>{{ __('Number') }}</flux:table.column>
                <flux:table.column>{{ __('Customer') }}</flux:table.column>
                <flux:table.column>{{ __('Issue Date') }}</flux:table.column>
                <flux:table.column>{{ __('Valid Until') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Total') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse (Quotation::where('business_id', auth()->user()->business->id)
                    ->when($this->search, fn($q) => $q->where(function($q) {
                        $q->where('quotation_number', 'like', '%'.$this->search.'%')
                          ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', '%'.$this->search.'%'));
                    }))
                    ->orderBy('id', 'desc')
                    ->paginate(10) as $quotation)
                    <flux:table.row :key="$quotation->id">
                        <flux:table.cell class="font-mono text-xs font-medium">{{ $quotation->quotation_number }}</flux:table.cell>
                        <flux:table.cell>{{ $quotation->customer?->name ?? __('Walk-in') }}</flux:table.cell>
                        <flux:table.cell>{{ $quotation->issue_date->format('d M Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $quotation->valid_until?->format('d M Y') ?? '—' }}</flux:table.cell>
                        <flux:table.cell align="end" class="font-medium">UGX {{ number_format($quotation->total, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :variant="match($quotation->status) { 'draft' => 'ghost', 'sent' => 'primary', 'accepted' => 'success', 'converted' => 'warning', 'rejected' => 'danger', default => 'ghost' }" size="sm">
                                {{ ucfirst($quotation->status) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button wire:click="exportPdf({{ $quotation->id }})" variant="ghost" size="sm" icon="arrow-down-tray" class="cursor-pointer" title="{{ __('Download PDF') }}" />
                                <flux:button :href="route('quotations.edit', $quotation->id)" variant="ghost" size="sm" icon="pencil-square" wire:navigate />
                                @if ($quotation->status !== 'converted')
                                    <flux:button wire:click="convertToInvoice({{ $quotation->id }})" variant="ghost" size="sm" icon="file-invoice" class="cursor-pointer" title="{{ __('Convert to Invoice') }}" />
                                @endif
                                <flux:button wire:click="delete({{ $quotation->id }})" variant="ghost" size="sm" icon="trash" class="text-red-500 hover:text-red-700!" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <flux:heading class="text-zinc-500 dark:text-zinc-400">{{ __('No quotations yet') }}</flux:heading>
                                <flux:subheading class="mt-1">{{ __('Create your first quotation to send to a customer.') }}</flux:subheading>
                                <flux:button variant="primary" :href="route('quotations.create')" wire:navigate class="mt-4">{{ __('New Quotation') }}</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
