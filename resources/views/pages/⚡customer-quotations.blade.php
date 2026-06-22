<?php

use App\Models\Customer;
use App\Models\CustomerQuotation;
use App\Models\Quotation;
use App\Models\QuotationItem;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;

new #[Title('Customer Requests')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    public $viewingQuotation = null;

    public bool $showViewModal = false;

    public function viewRequest(CustomerQuotation $quotation): void
    {
        if ($quotation->business_id !== currentBusiness()?->id) {
            return;
        }
        $this->viewingQuotation = $quotation->load('item');
        $this->showViewModal = true;
    }

    public function markResponded(CustomerQuotation $quotation): void
    {
        if ($quotation->business_id !== currentBusiness()?->id) {
            return;
        }
        $quotation->update(['status' => 'responded']);
        $this->showViewModal = false;
        Flux::toast(variant: 'success', text: __('Marked as responded.'));
    }

    public function convertToQuotation(CustomerQuotation $quotation): void
    {
        if ($quotation->business_id !== currentBusiness()?->id) {
            return;
        }

        $quotation->load('item');
        $businessId = currentBusiness()->id;

        $customer = Customer::firstOrCreate(
            [
                'business_id' => $businessId,
                'email' => $quotation->customer_email,
            ],
            [
                'name' => $quotation->customer_name,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        $last = Quotation::where('business_id', $businessId)
            ->orderBy('id', 'desc')
            ->first();
        $next = $last ? ((int) substr($last->quotation_number ?? 'QOT-0000', -4)) + 1 : 1;
        $quotationNumber = 'QOT-' . str_pad($next, 4, '0', STR_PAD_LEFT);

        $unitPrice = $quotation->item_type === 'fabric'
            ? ($quotation->item?->selling_price_per_meter ?? 0)
            : ($quotation->item?->selling_price ?? 0);
        $itemQuantity = $quotation->length_meters ?: 1;
        $itemTotal = $unitPrice * $itemQuantity;

        $newQuotation = Quotation::create([
            'business_id' => $businessId,
            'customer_id' => $customer->id,
            'quotation_number' => $quotationNumber,
            'issue_date' => now()->format('Y-m-d'),
            'valid_until' => now()->addDays(14)->format('Y-m-d'),
            'subtotal' => $itemTotal,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_name' => null,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => $itemTotal,
            'notes' => $quotation->customer_message ?: null,
            'status' => 'draft',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $description = $quotation->item?->name
            . ($quotation->item_type === 'fabric' && $quotation->item?->color ? ' (' . $quotation->item->color . ')' : '')
            . ' — ' . number_format($itemQuantity, 2) . ($quotation->item_type === 'fabric' ? 'm' : ' ' . ($quotation->item->unit ?? 'units'));

        $newQuotation->items()->create([
            'type' => $quotation->item_type === 'fabric' ? 'fabric' : 'product',
            'item_id' => $quotation->item_id,
            'description' => $description,
            'quantity' => $itemQuantity,
            'unit_price' => $unitPrice,
            'total' => $itemTotal,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $quotation->update(['status' => 'converted']);

        Flux::toast(variant: 'success', text: __('Quotation created.'));

        $this->redirect(route('quotations.edit', $newQuotation->id), navigate: true);
    }

    public function delete(CustomerQuotation $quotation): void
    {
        if ($quotation->business_id !== currentBusiness()?->id) {
            return;
        }
        $quotation->delete();
        Flux::toast(variant: 'success', text: __('Request deleted.'));
    }

    public function with(): array
    {
        $businessId = currentBusiness()?->id;

        $query = CustomerQuotation::with('item')
            ->where('business_id', $businessId);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('customer_name', 'like', "%{$this->search}%")
                  ->orWhere('customer_email', 'like', "%{$this->search}%")
                  ->orWhere('customer_phone', 'like', "%{$this->search}%");
            });
        }

        return [
            'quotations' => $query->latest()->paginate(15),
        ];
    }
}; ?>

<div style="width: 90%; margin: 0 auto;">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Customer Requests') }}</flux:heading>
            <flux:subheading class="mt-1">{{ __('Quotation requests submitted from the public site.') }}</flux:subheading>
        </div>
        <flux:input wire:model.live.debounce="search" icon="magnifying-glass" placeholder="{{ __('Search by name, email or phone...') }}" class="max-w-sm" />
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column sortable>{{ __('Date') }}</flux:table.column>
            <flux:table.column sortable>{{ __('Customer') }}</flux:table.column>
            <flux:table.column>{{ __('Item') }}</flux:table.column>
            <flux:table.column class="text-right">{{ __('Length') }}</flux:table.column>
            <flux:table.column class="text-right">{{ __('Total') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column class="text-right">{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($quotations as $q)
                <flux:table.row>
                    <flux:table.cell class="whitespace-nowrap text-xs text-neutral-500">{{ $q->created_at->format('d M Y H:i') }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="text-sm font-medium text-neutral-900 dark:text-white">{{ $q->customer_name }}</div>
                        <div class="text-xs text-neutral-500">{{ $q->customer_email }}</div>
                    </flux:table.cell>
                    <flux:table.cell class="text-sm">{{ $q->item?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="text-right text-sm font-mono">{{ number_format($q->length_meters, 2) }}m</flux:table.cell>
                    <flux:table.cell class="text-right text-sm font-semibold">{{ formatCurrency($q->total_price, 0) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge variant="pill" size="sm"
                            :color="match($q->status) { 'pending' => 'amber', 'responded' => 'green', 'converted' => 'indigo', default => 'neutral' }"
                            :icon="match($q->status) { 'pending' => 'clock', 'responded' => 'check-badge', 'converted' => 'arrow-right-circle', default => 'clock' }"
                        >
                            {{ ucfirst($q->status) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="text-right">
                        <div class="flex items-center justify-end gap-1">
                            <flux:button variant="ghost" size="sm" icon="eye"
                                wire:click="viewRequest({{ $q->id }})"
                                class="cursor-pointer text-indigo-600! hover:text-indigo-800! dark:text-indigo-400! dark:hover:text-indigo-300!" />
                            @if ($q->status === 'pending')
                            <flux:button variant="ghost" size="sm" icon="file-text"
                                wire:click="convertToQuotation({{ $q->id }})"
                                wire:confirm="{{ __('Convert this request into a quotation?') }}"
                                class="cursor-pointer text-amber-600! hover:text-amber-800! dark:text-amber-400! dark:hover:text-amber-300!" />
                            @endif
                            <flux:button variant="ghost" size="sm" icon="trash"
                                wire:click="delete({{ $q->id }})"
                                wire:confirm="{{ __('Delete this request?') }}"
                                class="cursor-pointer text-red-500! hover:text-red-700!" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">
                        <div class="flex flex-col items-center py-12 text-center">
                            <flux:heading class="text-neutral-400">{{ __('No requests yet') }}</flux:heading>
                            <flux:subheading class="mt-1 text-neutral-400">{{ __('Customer quotation requests from the public site will appear here.') }}</flux:subheading>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $quotations->links() }}
    </div>

    {{-- View Modal --}}
    <flux:modal wire:model="showViewModal" class="min-w-xl">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Request Details') }}</flux:heading>
            </div>

            @if ($viewingQuotation)
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:label>{{ __('Customer Name') }}</flux:label>
                        <p class="mt-1 text-sm font-medium text-neutral-900 dark:text-white">{{ $viewingQuotation->customer_name }}</p>
                    </div>
                    <div>
                        <flux:label>{{ __('Email') }}</flux:label>
                        <p class="mt-1 text-sm font-medium text-neutral-900 dark:text-white">
                            <a href="mailto:{{ $viewingQuotation->customer_email }}" class="text-indigo-500 hover:underline">{{ $viewingQuotation->customer_email }}</a>
                        </p>
                    </div>
                    @if ($viewingQuotation->customer_phone)
                        <div>
                            <flux:label>{{ __('Phone') }}</flux:label>
                            <p class="mt-1 text-sm font-medium text-neutral-900 dark:text-white">{{ $viewingQuotation->customer_phone }}</p>
                        </div>
                    @endif
                    <div>
                        <flux:label>{{ __('Item') }}</flux:label>
                        <p class="mt-1 text-sm font-medium text-neutral-900 dark:text-white">{{ $viewingQuotation->item?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <flux:label>{{ ucfirst($viewingQuotation->item_type) === 'Fabric' ? __('Length') : __('Quantity') }}</flux:label>
                        <p class="mt-1 text-sm font-medium text-neutral-900 dark:text-white">{{ number_format($viewingQuotation->length_meters ?: 1, 2) }}{{ $viewingQuotation->item_type === 'fabric' ? 'm' : ' ' . ($viewingQuotation->item->unit ?? 'units') }}</p>
                    </div>
                    @if ($viewingQuotation->width_meters && $viewingQuotation->item_type === 'fabric')
                        <div>
                            <flux:label>{{ __('Width') }}</flux:label>
                            <p class="mt-1 text-sm font-medium text-neutral-900 dark:text-white">{{ number_format($viewingQuotation->width_meters, 2) }}m</p>
                        </div>
                    @endif
                    <div>
                        <flux:label>{{ $viewingQuotation->item_type === 'fabric' ? __('Price per Meter') : __('Price per Unit') }}</flux:label>
                        <p class="mt-1 text-sm font-medium text-neutral-900 dark:text-white">{{ formatCurrency($viewingQuotation->item_type === 'fabric' ? ($viewingQuotation->item?->selling_price_per_meter ?? 0) : ($viewingQuotation->item?->selling_price ?? 0), 0) }}</p>
                    </div>
                    <div>
                        <flux:label>{{ __('Estimated Total') }}</flux:label>
                        <p class="mt-1 text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ formatCurrency($viewingQuotation->total_price, 0) }}</p>
                    </div>
                    <div>
                        <flux:label>{{ __('Status') }}</flux:label>
                        <flux:badge variant="pill"
                            :color="match($viewingQuotation->status) { 'pending' => 'amber', 'responded' => 'green', 'converted' => 'indigo', default => 'neutral' }"
                            :icon="match($viewingQuotation->status) { 'pending' => 'clock', 'responded' => 'check-badge', 'converted' => 'arrow-right-circle', default => 'clock' }"
                            class="mt-1"
                        >
                            {{ ucfirst($viewingQuotation->status) }}
                        </flux:badge>
                    </div>
                    <div>
                        <flux:label>{{ __('Submitted') }}</flux:label>
                        <p class="mt-1 text-sm font-medium text-neutral-900 dark:text-white">{{ $viewingQuotation->created_at->format('d M Y H:i') }}</p>
                    </div>
                </div>

                @if ($viewingQuotation->customer_message)
                    <div>
                        <flux:label>{{ __('Message') }}</flux:label>
                        <p class="mt-1 rounded-lg bg-neutral-50 p-3 text-sm text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">{{ $viewingQuotation->customer_message }}</p>
                    </div>
                @endif
            @endif

            <div class="flex items-center justify-end gap-2 pt-2">
                @if ($viewingQuotation && $viewingQuotation->status === 'pending')
                    <flux:button wire:click="markResponded({{ $viewingQuotation->id }})" variant="primary">
                        {{ __('Mark Responded') }}
                    </flux:button>
                    <flux:button wire:click="convertToQuotation({{ $viewingQuotation->id }})" variant="warning">
                        {{ __('Convert to Quotation') }}
                    </flux:button>
                @endif
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Close') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
