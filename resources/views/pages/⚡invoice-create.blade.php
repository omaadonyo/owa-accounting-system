<?php

use App\Helpers\QrCode;
use App\Models\Customer;
use App\Models\Fabric;
use App\Models\Invoice;
use App\Models\ProductService;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Invoice')] class extends Component {
    public ?int $customer_id = null;

    public string $issue_date = '';
    public string $due_date = '';

    public array $items = [];

    public ?string $discount_type = null;
    public ?string $discount_value = null;
    public float $discount_amount = 0;

    public ?string $tax_name = null;
    public ?string $tax_rate = null;
    public float $tax_amount = 0;

    public float $subtotal = 0;
    public float $total = 0;

    public string $notes = '';

    public string $invoice_number = '';

    public int $editingId = 0;

    public ?int $quotation_id = null;

    public float $paid_amount = 0;

    public float $payment_amount = 0;
    public string $payment_date = '';
    public string $payment_method = 'cash';
    public string $payment_reference = '';
    public string $payment_notes = '';

    public function mount($id = null, $quotation = null): void
    {
        if (! auth()->user()->business) {
            $this->redirect(route('onboarding', absolute: false), navigate: true);
            return;
        }

        $this->issue_date = now()->format('Y-m-d');
        $this->due_date = now()->addDays(30)->format('Y-m-d');
        $this->payment_date = now()->format('Y-m-d');

        if ($quotation) {
            $q = Quotation::with('items')->where('business_id', auth()->user()->business->id)->findOrFail($quotation);
            $this->quotation_id = $q->id;
            $this->customer_id = $q->customer_id;
            $this->issue_date = now()->format('Y-m-d');
            $this->due_date = now()->addDays(30)->format('Y-m-d');
            $this->discount_type = $q->discount_type;
            $this->discount_value = $q->discount_value !== null ? (string) $q->discount_value : null;
            $this->tax_name = $q->tax_name;
            $this->tax_rate = $q->tax_rate !== null ? (string) $q->tax_rate : null;
            $this->notes = $q->notes ?? '';

            $this->items = $q->items->map(fn($item) => [
                'key' => Str::random(8),
                'type' => $item->type,
                'item_id' => $item->item_id,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total' => (float) $item->total,
                'is_from_inventory' => $item->type !== 'custom',
            ])->toArray();

            $this->recalculate();
        }

        if ($id) {
            $invoice = Invoice::with('items', 'payments')->where('business_id', auth()->user()->business->id)->findOrFail($id);
            $this->editingId = $invoice->id;
            $this->invoice_number = $invoice->invoice_number;
            $this->quotation_id = $invoice->quotation_id;
            $this->paid_amount = (float) $invoice->paid_amount;
            $this->customer_id = $invoice->customer_id;
            $this->issue_date = $invoice->issue_date->format('Y-m-d');
            $this->due_date = $invoice->due_date?->format('Y-m-d') ?? '';
            $this->discount_type = $invoice->discount_type;
            $this->discount_value = $invoice->discount_value !== null ? (string) $invoice->discount_value : null;
            $this->tax_name = $invoice->tax_name;
            $this->tax_rate = $invoice->tax_rate !== null ? (string) $invoice->tax_rate : null;
            $this->notes = $invoice->notes ?? '';

            $this->items = $invoice->items->map(fn($item) => [
                'key' => Str::random(8),
                'type' => $item->type,
                'item_id' => $item->item_id,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total' => (float) $item->total,
                'is_from_inventory' => $item->type !== 'custom',
            ])->toArray();

            $this->recalculate();
            $this->payment_amount = max(0, $this->total - $this->paid_amount);
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'key' => Str::random(8),
            'type' => 'custom',
            'item_id' => null,
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'total' => 0,
            'is_from_inventory' => false,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->recalculate();
    }

    public function selectInventoryItem(int $index, string $selection): void
    {
        if (empty($selection)) {
            $this->items[$index]['type'] = 'custom';
            $this->items[$index]['item_id'] = null;
            $this->items[$index]['description'] = '';
            $this->items[$index]['unit_price'] = 0;
            $this->items[$index]['is_from_inventory'] = false;
            $this->recalculate();
            return;
        }

        $parts = explode(':', $selection);
        $type = $parts[0];
        $id = (int) ($parts[1] ?? 0);

        $this->items[$index]['type'] = $type;
        $this->items[$index]['item_id'] = $id;
        $this->items[$index]['is_from_inventory'] = true;

        if ($type === 'product') {
            $product = ProductService::where('business_id', auth()->user()->business?->id)->find($id);
            if ($product) {
                $this->items[$index]['description'] = $product->name;
                $this->items[$index]['unit_price'] = (float) ($product->selling_price ?? 0);
            }
        } elseif ($type === 'fabric') {
            $fabric = Fabric::where('business_id', auth()->user()->business?->id)->find($id);
            if ($fabric) {
                $desc = $fabric->name;
                if ($fabric->color) $desc .= ' (' . $fabric->color . ')';
                $this->items[$index]['description'] = $desc;
                $this->items[$index]['unit_price'] = (float) ($fabric->selling_price_per_meter ?? 0);
            }
        } elseif ($type === 'office_rent') {
            $rental = ProductService::where('business_id', auth()->user()->business?->id)->where('type', 'office_rent')->find($id);
            if ($rental) {
                $this->items[$index]['description'] = $rental->name;
                $this->items[$index]['unit_price'] = (float) ($rental->selling_price ?? 0);
            }
        }

        $this->recalculate();
    }

    public function updated($property, $value): void
    {
        if (str_starts_with($property, 'items.')) {
            $parts = explode('.', $property);
            $index = (int) $parts[1];
            $field = $parts[2] ?? null;

            if (in_array($field, ['quantity', 'unit_price'])) {
            $this->recalculate();
            $this->payment_amount = max(0, $this->total - $this->paid_amount);
        }
    }

        if (in_array($property, ['discount_type', 'discount_value', 'tax_rate', 'tax_name'])) {
            $this->recalculate();
        }
    }

    public function recalculate(): void
    {
        $this->subtotal = 0;

        foreach ($this->items as &$item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $item['total'] = round($qty * $price, 2);
            $this->subtotal += $item['total'];
        }
        unset($item);

        $discountValue = (float) ($this->discount_value ?? 0);
        if ($this->discount_type === 'percentage') {
            $this->discount_amount = round($this->subtotal * ($discountValue / 100), 2);
        } elseif ($this->discount_type === 'fixed') {
            $this->discount_amount = $discountValue;
        } else {
            $this->discount_amount = 0;
        }

        $afterDiscount = $this->subtotal - $this->discount_amount;
        $taxRate = (float) ($this->tax_rate ?? 0);
        $this->tax_amount = $taxRate > 0 ? round($afterDiscount * ($taxRate / 100), 2) : 0;

        $this->total = round($afterDiscount + $this->tax_amount, 2);
    }

    public function save(): void
    {
        $this->validate([
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:percentage,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'tax_name' => ['nullable', 'string', 'max:100'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->recalculate();

        $data = [
            'business_id' => auth()->user()->business->id,
            'customer_id' => $this->customer_id,
            'issue_date' => $this->issue_date,
            'due_date' => $this->due_date ?: null,
            'subtotal' => $this->subtotal,
            'discount_type' => $this->discount_type,
            'discount_value' => (float) ($this->discount_value ?? 0),
            'discount_amount' => $this->discount_amount,
            'tax_name' => $this->tax_name,
            'tax_rate' => (float) ($this->tax_rate ?? 0),
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'notes' => $this->notes ?: null,
        ];

        if ($this->editingId) {
            $invoice = Invoice::where('business_id', auth()->user()->business->id)->findOrFail($this->editingId);
            $data['updated_by'] = auth()->id();
            $invoice->update($data);
            $invoice->items()->delete();
            foreach ($this->items as $item) {
                $invoice->items()->create([
                    'type' => $item['type'],
                    'item_id' => $item['item_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
            $this->invoice_number = $invoice->fresh()->invoice_number;
            Flux::toast(variant: 'success', text: __('Invoice updated.'));
        } else {
            $last = Invoice::where('business_id', auth()->user()->business->id)->orderBy('id', 'desc')->first();
            $next = $last ? ((int) substr($last->invoice_number, -4)) + 1 : 1;
            $data['invoice_number'] = 'INV-' . str_pad($next, 4, '0', STR_PAD_LEFT);
            $data['status'] = 'draft';
            $data['quotation_id'] = $this->quotation_id;
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();

            $invoice = Invoice::create($data);
            foreach ($this->items as $item) {
                $invoice->items()->create([
                    'type' => $item['type'],
                    'item_id' => $item['item_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
            $this->invoice_number = $invoice->fresh()->invoice_number;
            $this->editingId = $invoice->id;
            Flux::toast(variant: 'success', text: __('Invoice created.'));
        }
    }

    public function recordPayment(): void
    {
        $this->validate([
            'payment_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'payment_notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $this->editingId) {
            return;
        }

        $invoice = Invoice::where('business_id', auth()->user()->business->id)->findOrFail($this->editingId);

        $last = \App\Models\Payment::whereHas('invoice', fn($q) => $q->where('business_id', auth()->user()->business->id))
            ->orderBy('id', 'desc')
            ->first();
        $next = $last ? ((int) substr($last->receipt_number ?? 'RCT-0000', -4)) + 1 : 1;
        $receiptNumber = 'RCT-' . str_pad($next, 4, '0', STR_PAD_LEFT);

        $invoice->payments()->create([
            'receipt_number' => $receiptNumber,
            'amount' => $this->payment_amount,
            'payment_date' => $this->payment_date,
            'payment_method' => $this->payment_method,
            'reference' => $this->payment_reference ?: str_pad((string) mt_rand(1, 999999999999), 12, '0', STR_PAD_LEFT),
            'notes' => $this->payment_notes ?: null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $paid = $invoice->payments()->sum('amount');
        $invoice->update(['paid_amount' => $paid, 'updated_by' => auth()->id()]);
        $this->paid_amount = (float) $paid;

        $this->payment_amount = max(0, $this->total - $this->paid_amount);
        $this->payment_date = now()->format('Y-m-d');
        $this->payment_method = 'cash';
        $this->payment_reference = '';
        $this->payment_notes = '';

        Flux::toast(variant: 'success', text: __('Receipt ' . $receiptNumber . ' recorded.'));
    }

    public function deletePayment(int $paymentId): void
    {
        $payment = \App\Models\Payment::whereHas('invoice', fn($q) => $q->where('business_id', auth()->user()->business->id))
            ->findOrFail($paymentId);
        $payment->delete();

        $invoice = Invoice::find($this->editingId);
        if ($invoice) {
            $paid = $invoice->payments()->sum('amount');
            $invoice->update(['paid_amount' => $paid, 'updated_by' => auth()->id()]);
            $this->paid_amount = (float) $paid;
        }

        Flux::toast(variant: 'success', text: __('Payment deleted.'));
    }

    public function getInventoryItemsProperty(): array
    {
        $businessId = auth()->user()->business?->id;
        if (! $businessId) return [];

        $products = ProductService::where('business_id', $businessId)
            ->where('type', 'product')
            ->get(['id', 'name', 'selling_price'])
            ->map(fn($p) => [
                'value' => 'product:' . $p->id,
                'label' => $p->name . '  —  UGX ' . number_format($p->selling_price ?? 0, 0),
                'group' => 'products',
            ]);

        $fabrics = Fabric::where('business_id', $businessId)
            ->get(['id', 'name', 'color', 'selling_price_per_meter'])
            ->map(fn($f) => [
                'value' => 'fabric:' . $f->id,
                'label' => $f->name . ($f->color ? ' (' . $f->color . ')' : '') . '  —  UGX ' . number_format($f->selling_price_per_meter ?? 0, 0) . '/m',
                'group' => 'fabrics',
            ]);

        $officeRents = ProductService::where('business_id', $businessId)
            ->where('type', 'office_rent')
            ->get(['id', 'name', 'selling_price'])
            ->map(fn($r) => [
                'value' => 'office_rent:' . $r->id,
                'label' => $r->name . '  —  UGX ' . number_format($r->selling_price ?? 0, 0) . '/mo',
                'group' => 'office_rents',
            ]);

        return $products->concat($fabrics)->concat($officeRents)->toArray();
    }

    public function getCustomerOptionsProperty(): array
    {
        $businessId = auth()->user()->business?->id;
        if (! $businessId) return [];

        return Customer::where('business_id', $businessId)
            ->get(['id', 'name', 'email'])
            ->toArray();
    }

    public function getBusinessProperty()
    {
        return auth()->user()->business;
    }

    public function getPaymentsProperty()
    {
        if (! $this->editingId) {
            return collect();
        }
        return \App\Models\Payment::with('creator:id,name')
            ->where('invoice_id', $this->editingId)
            ->latest()
            ->get();
    }

    public function getQrSvgProperty(): string
    {
        $number = $this->editingId ? $this->invoice_number : 'PREVIEW';
        $data = $this->business?->name . "\n" . $number . "\nUGX " . number_format($this->total, 2);
        return QrCode::generate($data, 120);
    }
}; ?>

<div style="width: 80%; margin: 0 auto;">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $editingId ? __('Edit Invoice') : __('New Invoice') }}</flux:heading>
            <flux:subheading class="mt-1">{{ __('Fill in the details below. The preview updates in real time.') }}</flux:subheading>
        </div>
        <flux:button variant="ghost" :href="route('invoices')" wire:navigate>{{ __('Back to Invoices') }}</flux:button>
    </div>

    <div class="grid grid-cols-1 gap-16 lg:grid-cols-2">
        {{-- LEFT: Form --}}
        <div class="space-y-6">
            <form wire:submit="save" class="space-y-6">
                {{-- Customer --}}
                <flux:field>
                    <flux:label>{{ __('Customer') }}</flux:label>
                    <flux:select wire:model="customer_id" placeholder="{{ __('Select a customer...') }}">
                        <option value="">{{ __('Walk-in Customer') }}</option>
                        @foreach ($this->customerOptions as $c)
                            <option value="{{ $c['id'] }}">{{ $c['name'] }} {{ $c['email'] ? '— ' . $c['email'] : '' }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>

                {{-- Dates --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>{{ __('Issue Date') }} <span class="text-red-500">*</span></flux:label>
                        <flux:input wire:model="issue_date" type="date" required />
                        <flux:error name="issue_date" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Due Date') }}</flux:label>
                        <flux:input wire:model="due_date" type="date" />
                        <flux:error name="due_date" />
                    </flux:field>
                </div>

                {{-- Items --}}
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <flux:label>{{ __('Items') }}</flux:label>
                        <flux:button type="button" variant="ghost" size="sm" wire:click="addItem">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
                            {{ __('Add Item') }}
                        </flux:button>
                    </div>
                    <flux:error name="items" />
                    <div class="space-y-3">
                        @foreach ($items as $index => $item)
                            <div wire:key="item-{{ $item['key'] }}" class="rounded-lg border border-neutral-200 bg-white p-3 dark:border-neutral-700 dark:bg-neutral-900">
                                <div class="mb-2 flex items-center justify-between">
                                    <flux:select wire:change="selectInventoryItem({{ $index }}, $event.target.value)" class="w-full text-xs">
                                        <option value="">{{ __('Custom entry') }}</option>
                                        @php
                                            $grouped = collect($this->inventoryItems)->groupBy('group');
                                        @endphp
                                        @if ($grouped->has('products') && $grouped->get('products')->isNotEmpty())
                                            <option disabled class="text-neutral-400">─── {{ __('Products') }} ───</option>
                                            @foreach ($grouped->get('products') as $opt)
                                                <option value="{{ $opt['value'] }}" @selected($item['is_from_inventory'] && $item['type'] === 'product' && $item['item_id'] == explode(':', $opt['value'])[1])>{{ $opt['label'] }}</option>
                                            @endforeach
                                        @endif
                                        @if ($grouped->has('fabrics') && $grouped->get('fabrics')->isNotEmpty())
                                            <option disabled class="text-neutral-400">─── {{ __('Fabrics') }} ───</option>
                                            @foreach ($grouped->get('fabrics') as $opt)
                                                <option value="{{ $opt['value'] }}" @selected($item['is_from_inventory'] && $item['type'] === 'fabric' && $item['item_id'] == explode(':', $opt['value'])[1])>{{ $opt['label'] }}</option>
                                            @endforeach
                                        @endif
                                        @if ($grouped->has('office_rents') && $grouped->get('office_rents')->isNotEmpty())
                                            <option disabled class="text-neutral-400">─── {{ __('Office Rentals') }} ───</option>
                                            @foreach ($grouped->get('office_rents') as $opt)
                                                <option value="{{ $opt['value'] }}" @selected($item['is_from_inventory'] && $item['type'] === 'office_rent' && $item['item_id'] == explode(':', $opt['value'])[1])>{{ $opt['label'] }}</option>
                                            @endforeach
                                        @endif
                                    </flux:select>
                                    <button type="button" wire:click="removeItem({{ $index }})" class="ml-2 shrink-0 text-xs text-red-500 hover:text-red-700">&times;</button>
                                </div>

                                <flux:field class="mt-2">
                                    <flux:input wire:model="items.{{ $index }}.description" type="text" placeholder="{{ __('Description') }}" required :readonly="$item['is_from_inventory']" />
                                    <flux:error name="items.{{ $index }}.description" />
                                </flux:field>

                                <div class="mt-2 grid grid-cols-3 gap-2">
                                    <flux:field>
                                        <flux:input wire:model="items.{{ $index }}.quantity" wire:input="recalculate" type="number" step="0.01" min="0.01" placeholder="{{ __('Qty') }}" />
                                        <flux:error name="items.{{ $index }}.quantity" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:input wire:model="items.{{ $index }}.unit_price" wire:input="recalculate" type="number" step="0.01" min="0" placeholder="{{ __('Price') }}" :readonly="$item['is_from_inventory']" />
                                        <flux:error name="items.{{ $index }}.unit_price" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:input type="text" value="UGX {{ number_format($item['total'], 2) }}" readonly class="bg-neutral-50 dark:bg-neutral-800" />
                                    </flux:field>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Discount & Tax --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>{{ __('Discount') }}</flux:label>
                        <div class="flex gap-2">
                            <flux:select wire:model="discount_type" wire:change="recalculate" class="w-32">
                                <option value="">{{ __('None') }}</option>
                                <option value="percentage">{{ __('%') }}</option>
                                <option value="fixed">{{ __('Fixed') }}</option>
                            </flux:select>
                            <flux:input wire:model="discount_value" wire:input="recalculate" type="number" step="0.01" min="0" placeholder="0" class="flex-1" />
                        </div>
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Tax') }}</flux:label>
                        <div class="flex gap-2">
                            <flux:input wire:model="tax_name" type="text" placeholder="{{ __('e.g. VAT') }}" class="flex-1" />
                            <flux:input wire:model="tax_rate" wire:input="recalculate" type="number" step="0.01" min="0" max="100" placeholder="%" class="w-20" />
                        </div>
                    </flux:field>
                </div>

                {{-- Notes --}}
                <flux:field>
                    <flux:label>{{ __('Notes') }}</flux:label>
                    <flux:textarea wire:model="notes" rows="2" placeholder="{{ __('Optional notes for the customer...') }}" />
                </flux:field>

                @if ($editingId)
                    {{-- Payments / Receipts --}}
                    <div id="receipts" class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700 scroll-mt-8">
                        <flux:heading size="sm" class="mb-3">{{ __('Receipts') }}</flux:heading>

                        @if ($this->payments->isNotEmpty())
                            <div class="mb-3 space-y-2 text-sm">
                                @foreach ($this->payments as $p)
                                    <div class="flex items-center justify-between rounded-lg bg-neutral-50 px-3 py-2 dark:bg-neutral-800">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-mono text-xs font-medium text-indigo-500">{{ $p->receipt_number }}</span>
                                                <span class="font-medium">UGX {{ number_format($p->amount, 2) }}</span>
                                            </div>
                                            <div class="mt-0.5 flex items-center gap-2 text-xs text-neutral-500">
                                                <span>{{ $p->payment_date->format('d M Y') }}</span>
                                                <flux:badge variant="pill" size="sm" color="lime" class="text-[10px]" :icon="match($p->payment_method) { 'cash' => 'banknotes', 'bank_transfer' => 'building-bank', 'mobile_money' => 'device-phone-mobile', 'credit_card' => 'credit-card', 'cheque' => 'document-text', default => 'clock' }">
                                                    {{ ucwords(str_replace('_', ' ', $p->payment_method)) }}
                                                </flux:badge>
                                                @if ($p->reference)
                                                    <span>#{{ $p->reference }}</span>
                                                @endif
                                                <span>{{ $p->creator?->name ?? '' }}</span>
                                            </div>
                                        </div>
                                        <flux:button wire:click="deletePayment({{ $p->id }})" variant="ghost" size="xs" icon="trash"
                                            wire:confirm="{{ __('Delete receipt ' . $p->receipt_number . '?') }}"
                                            class="size-7 shrink-0 cursor-pointer text-red-500 hover:text-red-700!" />
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="flex items-center justify-between border-t border-neutral-200 pt-2 text-sm dark:border-neutral-700">
                            <span class="font-medium text-neutral-500">{{ __('Paid') }}</span>
                            <span class="font-bold text-green-600">UGX {{ number_format($paid_amount, 2) }}</span>
                        </div>
                        @if ($total > $paid_amount)
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-neutral-500">{{ __('Balance Due') }}</span>
                                <span class="font-bold text-amber-600">UGX {{ number_format(max(0, $total - $paid_amount), 2) }}</span>
                            </div>
                        @endif

                        <hr class="my-3 border-neutral-200 dark:border-neutral-700">

                        <flux:field>
                            <flux:label>{{ __('New Receipt') }}</flux:label>
                            <div class="grid grid-cols-2 gap-2">
                                <flux:input wire:model="payment_amount" type="number" step="0.01" min="0.01" placeholder="{{ __('Amount') }}" />
                                <flux:input wire:model="payment_date" type="date" />
                            </div>
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <flux:select wire:model="payment_method">
                                    <option value="cash">{{ __('Cash') }}</option>
                                    <option value="bank">{{ __('Bank') }}</option>
                                    <option value="mobile_money">{{ __('Mobile Money') }}</option>
                                    <option value="card">{{ __('Card') }}</option>
                                    <option value="other">{{ __('Other') }}</option>
                                </flux:select>
                                <flux:input wire:model="payment_reference" placeholder="{{ __('Reference (opt)') }}" />
                            </div>
                            <flux:textarea wire:model="payment_notes" rows="1" placeholder="{{ __('Notes (opt)') }}" class="mt-2" />
                            <flux:button variant="primary" size="sm" wire:click="recordPayment" class="mt-2 cursor-pointer">{{ __('Record Receipt') }}</flux:button>
                            <flux:error name="payment_amount" />
                            <flux:error name="payment_date" />
                            <flux:error name="payment_method" />
                        </flux:field>
                    </div>
                @endif

                <div class="flex justify-end gap-2 border-t border-neutral-200 pt-6 dark:border-neutral-700">
                    <flux:button variant="filled" :href="route('invoices')" wire:navigate>{{ __('Cancel') }}</flux:button>
                    <flux:button variant="primary" type="submit">{{ $editingId ? __('Update') : __('Save Invoice') }}</flux:button>
                </div>
            </form>
        </div>

        {{-- RIGHT: Preview --}}
        <div>
            <div class="sticky top-8">
                <flux:heading size="sm" class="mb-3">{{ __('Preview') }}</flux:heading>
                <div class="rounded-xl border border-neutral-200 bg-white p-8 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                    {{-- Header --}}
                    <div class="flex items-start justify-between">
                        <div>
                            @if ($this->business && $this->business->logo)
                                <img src="{{ Storage::url($this->business->logo) }}" alt="Logo" class="mb-3 h-12 object-contain">
                            @endif
                            <h2 class="text-lg font-bold text-neutral-900 dark:text-white">{{ $this->business->name ?? __('Business Name') }}</h2>
                            @if ($this->business && $this->business->address)
                                <p class="mt-1 text-xs text-neutral-500">{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $this->business->address))) !!}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">{{ __('INVOICE') }}</h1>
                            <p class="mt-1 font-mono text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                {{ $editingId ? $this->invoice_number : 'PREVIEW' }}
                            </p>
                        </div>
                    </div>

                    <hr class="my-4 border-neutral-200 dark:border-neutral-700">

                    {{-- Dates & Customer --}}
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <p><span class="font-medium text-neutral-500">{{ __('Issue Date:') }}</span> {{ $issue_date ? \Carbon\Carbon::parse($issue_date)->format('d M Y') : '—' }}</p>
                            <p class="mt-0.5"><span class="font-medium text-neutral-500">{{ __('Due Date:') }}</span> {{ $due_date ? \Carbon\Carbon::parse($due_date)->format('d M Y') : '—' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-neutral-700 dark:text-neutral-300">{{ $customer_id ? \App\Models\Customer::find($customer_id)?->name : __('Walk-in Customer') }}</p>
                            @if ($customer_id)
                                @php $c = \App\Models\Customer::find($customer_id); @endphp
                                @if ($c && $c->email)
                                    <p class="text-neutral-500">{{ $c->email }}</p>
                                @endif
                            @endif
                        </div>
                    </div>

                    {{-- Items Table --}}
                    <div class="mt-4 overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-700">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="bg-neutral-50 dark:bg-neutral-800">
                                    <th class="px-3 py-2 text-left font-medium text-neutral-500">{{ __('Item') }}</th>
                                    <th class="px-3 py-2 text-right font-medium text-neutral-500">{{ __('Qty') }}</th>
                                    <th class="px-3 py-2 text-right font-medium text-neutral-500">{{ __('Price') }}</th>
                                    <th class="px-3 py-2 text-right font-medium text-neutral-500">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                                @forelse ($items as $item)
                                    <tr>
                                        <td class="px-3 py-2">{{ $item['description'] ?: '—' }}</td>
                                        <td class="px-3 py-2 text-right">{{ number_format($item['quantity'], 2) }}</td>
                                        <td class="px-3 py-2 text-right">UGX {{ number_format($item['unit_price'], 2) }}</td>
                                        <td class="px-3 py-2 text-right font-medium">UGX {{ number_format($item['total'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-4 text-center text-neutral-400">{{ __('No items added yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Totals --}}
                    <div class="mt-3 space-y-1 text-right text-xs">
                        <p><span class="inline-block w-24 text-neutral-500">{{ __('Subtotal:') }}</span> <span class="font-medium">UGX {{ number_format($subtotal, 2) }}</span></p>
                        @if ($discount_amount > 0)
                            <p><span class="inline-block w-24 text-neutral-500">{{ __('Discount:') }}</span> <span class="font-medium">-UGX {{ number_format($discount_amount, 2) }}</span></p>
                        @endif
                        @if ($tax_amount > 0)
                            <p><span class="inline-block w-24 text-neutral-500">{{ $tax_name ?? 'Tax' }} ({{ $tax_rate ?? 0 }}%):</span> <span class="font-medium">UGX {{ number_format($tax_amount, 2) }}</span></p>
                        @endif
                        <hr class="my-1 border-neutral-200 dark:border-neutral-700">
                        <p class="text-base font-bold">UGX {{ number_format($total, 2) }}</p>
                    </div>

                    {{-- Receipts --}}
                    @if ($editingId)
                        <div class="mt-4">
                            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Receipts') }}</h3>
                            @if ($this->payments->isNotEmpty())
                                <div class="space-y-1 text-xs">
                                    @foreach ($this->payments as $p)
                                        <div class="flex justify-between">
                                            <span class="font-mono text-neutral-600 dark:text-neutral-400">{{ $p->receipt_number }}</span>
                                            <span class="font-medium">UGX {{ number_format($p->amount, 2) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-neutral-400 italic">{{ __('No receipts recorded yet.') }}</p>
                            @endif
                            <hr class="my-2 border-neutral-200 dark:border-neutral-700">
                            <div class="space-y-1 text-xs">
                                <div class="flex justify-between">
                                    <span class="font-medium text-neutral-500">{{ __('Total') }}</span>
                                    <span class="font-medium">UGX {{ number_format($total, 2) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium text-neutral-500">{{ __('Paid') }}</span>
                                    <span class="font-medium text-green-600">UGX {{ number_format($paid_amount, 2) }}</span>
                                </div>
                                <div class="flex justify-between border-t border-neutral-200 pt-1 dark:border-neutral-700">
                                    <span class="font-semibold text-neutral-600 dark:text-neutral-300">{{ __('Balance Due') }}</span>
                                    <span class="font-semibold {{ $total > $paid_amount ? 'text-amber-600' : 'text-green-600' }}">UGX {{ number_format(max(0, $total - $paid_amount), 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Notes & QR --}}
                    <div class="mt-4 flex items-end justify-between">
                        <div class="max-w-xs text-xs text-neutral-500">
                            @if ($notes)
                                <p>{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $notes))) !!}</p>
                            @endif
                            @if ($this->business && $this->business->invoice_notes)
                                <p class="mt-1 italic">{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $this->business->invoice_notes))) !!}</p>
                            @endif
                        </div>
                        <div class="shrink-0">
                            {!! $this->qrSvg !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
