<?php

use App\Helpers\QrCode;
use App\Models\Customer;
use App\Models\Fabric;
use App\Traits\LogsActivity;
use App\Models\Invoice;
use App\Models\ProductService;
use App\Models\Quotation;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Quotation')] class extends Component {
    use \App\Traits\ChecksSubscriptionLimits;

    public ?int $editingId = null;

    public ?int $customer_id = null;

    public string $issue_date = '';
    public string $valid_until = '';

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

    public string $quotation_number = '';

    public bool $show_discount_column = false;
    public bool $hide_total = false;
    public string $custom_title = '';
    public bool $show_amount_in_words = false;
    public bool $act_as_delivery_note = false;
    public bool $tax_inclusive = false;

    public ?string $wht_type = null;
    public ?string $wht_rate = null;
    public float $wht_amount = 0;

    public function mount($id = null): void
    {
        if (! currentBusiness()) {
            $this->redirect(route('onboarding', absolute: false), navigate: true);
            return;
        }

        $this->issue_date = now()->format('Y-m-d');
        $this->valid_until = now()->addDays(14)->format('Y-m-d');
        $this->tax_name = __('VAT');

        if ($id) {
            $quotation = Quotation::with('items')->where('business_id', currentBusiness()->id)->findOrFail($id);
            $this->editingId = $quotation->id;
            $this->quotation_number = $quotation->quotation_number;
            $this->customer_id = $quotation->customer_id;
            $this->issue_date = $quotation->issue_date->format('Y-m-d');
            $this->valid_until = $quotation->valid_until?->format('Y-m-d') ?? '';
            $this->discount_type = $quotation->discount_type;
            $this->discount_value = $quotation->discount_value !== null ? (string) $quotation->discount_value : null;
            $this->tax_name = $quotation->tax_name;
            $this->tax_rate = $quotation->tax_rate !== null ? (string) $quotation->tax_rate : null;
            $this->notes = $quotation->notes ?? '';

            $this->items = $quotation->items->map(fn($item) => [
                'key' => Str::random(8),
                'type' => $item->type,
                'item_id' => $item->item_id,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total' => (float) $item->total,
                'is_from_inventory' => $item->type !== 'custom',
            ])->toArray();

            $this->show_discount_column = $quotation->show_discount_column;
            $this->hide_total = $quotation->hide_total;
            $this->custom_title = $quotation->custom_title ?? '';
            $this->show_amount_in_words = $quotation->show_amount_in_words;
            $this->act_as_delivery_note = $quotation->act_as_delivery_note;
            $this->tax_inclusive = $quotation->tax_inclusive;
            $this->wht_rate = $quotation->wht_rate !== null ? (string) $quotation->wht_rate : null;
            $this->wht_amount = (float) $quotation->wht_amount;
            $this->wht_type = (float) ($this->wht_rate ?? 0) > 0 ? 'percentage' : 'fixed';
            if ($this->wht_type === 'fixed' && $this->wht_amount > 0) {
                $this->wht_rate = (string) $this->wht_amount;
            }
        }

        if (! $id) {
            $this->addItem();
        }
        $this->recalculate();
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
            $product = ProductService::where('business_id', currentBusiness()?->id)->find($id);
            if ($product) {
                $desc = $product->name;
                if ($product->description) $desc .= ' — ' . $product->description;
                $this->items[$index]['description'] = $desc;
                $this->items[$index]['unit_price'] = (float) ($product->selling_price ?? 0);
            }
        } elseif ($type === 'fabric') {
            $fabric = Fabric::where('business_id', currentBusiness()?->id)->find($id);
            if ($fabric) {
                $desc = $fabric->name;
                if ($fabric->color) $desc .= ' (' . $fabric->color . ')';
                if ($fabric->description) $desc .= ' — ' . $fabric->description;
                $this->items[$index]['description'] = $desc;
                $this->items[$index]['unit_price'] = (float) ($fabric->selling_price_per_meter ?? 0);
            }
        } elseif ($type === 'office_rent') {
            $rental = ProductService::where('business_id', currentBusiness()?->id)->where('type', 'office_rent')->find($id);
            if ($rental) {
                $desc = $rental->name;
                if ($rental->description) $desc .= ' — ' . $rental->description;
                $this->items[$index]['description'] = $desc;
                $this->items[$index]['unit_price'] = (float) ($rental->selling_price ?? 0);
            }
        }

        $this->recalculate();
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

        if ($this->tax_inclusive && (float) ($this->tax_rate ?? 0) > 0) {
            $taxRate = (float) $this->tax_rate;
            $this->tax_amount = round($afterDiscount * ($taxRate / 100), 2);
            $this->total = round($afterDiscount + $this->tax_amount, 2);
        } else {
            $this->tax_amount = 0;
            $this->total = round($afterDiscount, 2);
        }

        $whtValue = (float) ($this->wht_rate ?? 0);
        if ($this->wht_type === 'fixed') {
            $this->wht_amount = $whtValue;
        } else {
            $this->wht_amount = $whtValue > 0 ? round($afterDiscount * ($whtValue / 100), 2) : 0;
        }
    }

    public function save(): void
    {
        $this->validate([
            'issue_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:percentage,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'tax_name' => ['nullable', 'string', 'max:100'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'wht_type' => ['nullable', 'in:percentage,fixed'],
            'wht_rate' => ['nullable', 'numeric', 'min:0', ($this->wht_type === 'percentage' ? 'max:100' : 'max:999999999')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->recalculate();

        $data = [
            'business_id' => currentBusiness()->id,
            'customer_id' => $this->customer_id,
            'issue_date' => $this->issue_date,
            'valid_until' => $this->valid_until ?: null,
            'subtotal' => $this->subtotal,
            'discount_type' => $this->discount_type,
            'discount_value' => (float) ($this->discount_value ?? 0),
            'discount_amount' => $this->discount_amount,
            'tax_name' => $this->tax_name,
            'tax_rate' => (float) ($this->tax_rate ?? 0),
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
            'notes' => $this->notes ?: null,
            'show_discount_column' => $this->show_discount_column,
            'hide_total' => $this->hide_total,
            'custom_title' => $this->custom_title ?: null,
            'show_amount_in_words' => $this->show_amount_in_words,
            'act_as_delivery_note' => $this->act_as_delivery_note,
            'tax_inclusive' => $this->tax_inclusive,
            'wht_rate' => $this->wht_type === 'fixed' ? 0 : (float) ($this->wht_rate ?? 0),
            'wht_amount' => $this->wht_amount,
        ];

        if ($this->editingId) {
            $quotation = Quotation::where('business_id', currentBusiness()->id)->findOrFail($this->editingId);
            $data['updated_by'] = auth()->id();
            $quotation->update($data);
            $quotation->items()->delete();

            foreach ($this->items as $item) {
                $quotation->items()->create([
                    'type' => $item['type'],
                    'item_id' => $item['item_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                    'updated_by' => auth()->id(),
                    'created_by' => auth()->id(),
                ]);
            }

            $this->quotation_number = $quotation->fresh()->quotation_number;
            LogsActivity::log('quotation_updated', "Updated quotation {$quotation->quotation_number}", $quotation, ['total' => $quotation->total]);
            Flux::toast(variant: 'success', text: __('Quotation updated.'));
        } else {
            $check = $this->checkLimit('quotations');
            if (!$check['allowed']) {
                Flux::toast(variant: 'danger', text: __($check['reason']));
                return;
            }

            $last = Quotation::where('business_id', currentBusiness()->id)->orderBy('id', 'desc')->first();
            $next = $last ? ((int) substr($last->quotation_number, -4)) + 1 : 1;
            $data['quotation_number'] = 'QOT-' . str_pad($next, 4, '0', STR_PAD_LEFT);
            $data['status'] = 'draft';
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();

            $quotation = Quotation::create($data);
            foreach ($this->items as $item) {
                $quotation->items()->create([
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

            LogsActivity::log('quotation_created', "Created quotation {$quotation->quotation_number}", $quotation, ['total' => $quotation->total]);
            $this->quotation_number = $quotation->fresh()->quotation_number;
            $this->editingId = $quotation->id;
            Flux::toast(variant: 'success', text: __('Quotation created.'));
        }
    }

    public function convertToInvoice(): void
    {
        if (! $this->editingId) {
            return;
        }

        $quotation = Quotation::with('items')->where('business_id', currentBusiness()->id)->findOrFail($this->editingId);

        if ($quotation->status === 'converted') {
            Flux::toast(variant: 'warning', text: __('This quotation has already been converted.'));
            return;
        }

        $check = $this->checkLimit('invoices');
        if (!$check['allowed']) {
            Flux::toast(variant: 'danger', text: __($check['reason']));
            return;
        }

        $last = Invoice::where('business_id', currentBusiness()->id)->orderBy('id', 'desc')->first();
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
            'wht_rate' => $quotation->wht_rate,
            'wht_amount' => $quotation->wht_amount,
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

        $this->adjustInventory($quotation->items->toArray());

        $quotation->update(['status' => 'converted', 'updated_by' => auth()->id()]);

        LogsActivity::log('quotation_converted', "Converted quotation {$quotation->quotation_number} to invoice {$invoiceNumber}", $quotation, ['total' => $quotation->total]);

        Flux::toast(variant: 'success', text: __('Quotation converted to invoice.'));
        $this->redirect(route('invoices.edit', $invoice->id), navigate: true);
    }

    private function adjustInventory(array $items, bool $restore = false): void
    {
        foreach ($items as $item) {
            if (! $item['item_id']) continue;

            if ($item['type'] === 'product' || $item['type'] === 'office_rent') {
                $product = ProductService::find($item['item_id']);
                if ($product) {
                    $restore
                        ? $product->increment('quantity', $item['quantity'])
                        : $product->decrement('quantity', $item['quantity']);
                }
            } elseif ($item['type'] === 'fabric') {
                $fabric = Fabric::find($item['item_id']);
                if ($fabric) {
                    $restore
                        ? $fabric->decrement('used_meters', $item['quantity'])
                        : $fabric->increment('used_meters', $item['quantity']);
                }
            }
        }
    }

    public function getInventoryItemsProperty(): array
    {
        $businessId = currentBusiness()?->id;
        if (! $businessId) return [];

        $products = ProductService::where('business_id', $businessId)
            ->where('type', 'product')
            ->get(['id', 'name', 'description', 'selling_price'])
            ->map(fn($p) => [
                'value' => 'product:' . $p->id,
                'label' => $p->name . '  —  ' . formatCurrency($p->selling_price ?? 0, 0),
                'group' => 'products',
                'description' => $p->description,
            ]);

        $fabrics = Fabric::where('business_id', $businessId)
            ->get(['id', 'name', 'color', 'selling_price_per_meter'])
            ->map(fn($f) => [
                'value' => 'fabric:' . $f->id,
                'label' => $f->name . ($f->color ? ' (' . $f->color . ')' : '') . '  —  ' . formatCurrency($f->selling_price_per_meter ?? 0, 0) . '/m',
                'group' => 'fabrics',
            ]);

        $officeRents = ProductService::where('business_id', $businessId)
            ->where('type', 'office_rent')
            ->get(['id', 'name', 'description', 'selling_price'])
            ->map(fn($r) => [
                'value' => 'office_rent:' . $r->id,
                'label' => $r->name . '  —  ' . formatCurrency($r->selling_price ?? 0, 0) . '/mo',
                'group' => 'office_rents',
                'description' => $r->description,
            ]);

        return $products->concat($fabrics)->concat($officeRents)->toArray();
    }

    public function getCustomerOptionsProperty(): array
    {
        $businessId = currentBusiness()?->id;
        if (! $businessId) return [];

        return Customer::where('business_id', $businessId)
            ->get(['id', 'name', 'email'])
            ->toArray();
    }

    public function getBusinessProperty()
    {
        return currentBusiness();
    }

    public function getQrSvgProperty(): string
    {
        $number = $this->editingId ? $this->quotation_number : 'PREVIEW';
        $data = $this->business?->name . "\n" . $number . "\n" . formatCurrency($this->total);
        return QrCode::generate($data, 120);
    }
}; ?>


<div style="width: 80%; margin: 0 auto;">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $editingId ? __('Edit Quotation') : __('New Quotation') }}</flux:heading>
            <flux:subheading class="mt-1">{{ __('Fill in the details below. The preview updates in real time.') }}</flux:subheading>
        </div>
        <flux:button variant="ghost" :href="route('quotations')" wire:navigate>{{ __('Back to Quotations') }}</flux:button>
    </div>

    <div class="grid grid-cols-1 gap-16 lg:grid-cols-2">
        {{-- LEFT: Form --}}
        <div class="space-y-6">
            <form wire:submit="save" class="space-y-6">
                {{-- Customer --}}
                <flux:field>
                    <flux:label>{{ __('Customer') }}</flux:label>
                    <div class="custom-select relative">
                        <button type="button" data-cs-trigger class="flex w-full items-center justify-between rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white dark:focus:border-accent">
                            <span wire:ignore data-cs-display>{{ __('Walk-in Customer') }}</span>
                            <svg class="size-4 shrink-0 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div data-cs-dropdown class="absolute left-0 right-0 top-full z-50 mt-1 hidden overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-lg dark:border-neutral-700 dark:bg-neutral-800">
                            <div class="border-b border-neutral-200 p-2 dark:border-neutral-700">
                                <input type="text" data-cs-search placeholder="Search..." class="w-full rounded-md border border-neutral-200 bg-neutral-50 px-2.5 py-1.5 text-xs text-neutral-900 outline-none placeholder:text-neutral-400 focus:border-accent focus:ring-1 focus:ring-accent/30 dark:border-neutral-600 dark:bg-neutral-700 dark:text-white dark:placeholder:text-neutral-500">
                            </div>
                            <div data-cs-options class="max-h-48 overflow-y-auto py-1">
                                <button type="button" data-cs-option data-cs-value="" data-cs-label="Walk-in Customer" class="cs-selected flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('Walk-in Customer') }}</button>
                                @foreach ($this->customerOptions as $c)
                                    <button type="button" data-cs-option data-cs-value="{{ $c['id'] }}" data-cs-label="{{ $c['name'] }} {{ $c['email'] ? '— ' . $c['email'] : '' }}" class="flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ $c['name'] }} {{ $c['email'] ? '— ' . $c['email'] : '' }}</button>
                                @endforeach
                            </div>
                        </div>
                        <select wire:model="customer_id" class="sr-only">
                            <option value="">{{ __('Walk-in Customer') }}</option>
                            @foreach ($this->customerOptions as $c)
                                <option value="{{ $c['id'] }}">{{ $c['name'] }} {{ $c['email'] ? '— ' . $c['email'] : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                </flux:field>

                {{-- Dates --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>{{ __('Issue Date') }} <span class="text-red-500">*</span></flux:label>
                        <flux:input wire:model="issue_date" type="date" required />
                        <flux:error name="issue_date" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Valid Until') }}</flux:label>
                        <flux:input wire:model="valid_until" type="date" />
                        <flux:error name="valid_until" />
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
                            <div wire:key="item-{{ $item['key'] }}" class="rounded-lg border border-neutral-200 bg-white p-3 dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
                                <div class="mb-2 flex items-center justify-between">
                                    <div class="custom-select relative w-full">
                                        @php $grouped = collect($this->inventoryItems)->groupBy('group'); @endphp
                                        <button type="button" data-cs-trigger class="flex w-full items-center justify-between rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 shadow-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white dark:focus:border-accent">
                                            <span wire:ignore data-cs-display>{{ __('Custom entry') }}</span>
                                            <svg class="size-4 shrink-0 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                        </button>
                                        <div data-cs-dropdown class="absolute left-0 right-0 top-full z-50 mt-1 hidden overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-lg dark:border-neutral-700 dark:bg-neutral-800">
                                            <div class="border-b border-neutral-200 p-2 dark:border-neutral-700">
                                                <input type="text" data-cs-search placeholder="Search..." class="w-full rounded-md border border-neutral-200 bg-neutral-50 px-2.5 py-1.5 text-xs text-neutral-900 outline-none placeholder:text-neutral-400 focus:border-accent focus:ring-1 focus:ring-accent/30 dark:border-neutral-600 dark:bg-neutral-700 dark:text-white dark:placeholder:text-neutral-500">
                                            </div>
                                            <div data-cs-options class="max-h-48 overflow-y-auto py-1">
                                                <button type="button" data-cs-option data-cs-value="" data-cs-label="Custom entry" class="cs-selected flex w-full items-center px-3 py-2 text-left text-xs text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('Custom entry') }}</button>
                                                @if ($grouped->has('products') && $grouped->get('products')->isNotEmpty())
                                                    <div class="px-3 py-1.5 text-[10px] font-medium uppercase tracking-wider text-neutral-400">{{ __('Products') }}</div>
                                                    @foreach ($grouped->get('products') as $opt)
                                                        <button type="button" data-cs-option data-cs-value="{{ $opt['value'] }}" data-cs-label="{{ $opt['label'] }}" class="flex w-full flex-col items-start px-3 py-2 text-left text-xs text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300 {{ $item['is_from_inventory'] && $item['type'] === 'product' && $item['item_id'] == explode(':', $opt['value'])[1] ? 'cs-selected' : '' }}">
                                                            <span class="block truncate">{{ $opt['label'] }}</span>
                                                            @if ($opt['description'] ?? null)
                                                                <span class="block truncate text-[10px] text-neutral-400">{{ $opt['description'] }}</span>
                                                            @endif
                                                        </button>
                                                    @endforeach
                                                @endif
                                                @if ($grouped->has('fabrics') && $grouped->get('fabrics')->isNotEmpty())
                                                    <div class="px-3 py-1.5 text-[10px] font-medium uppercase tracking-wider text-neutral-400">{{ __('Fabrics') }}</div>
                                                    @foreach ($grouped->get('fabrics') as $opt)
                                                        <button type="button" data-cs-option data-cs-value="{{ $opt['value'] }}" data-cs-label="{{ $opt['label'] }}" class="flex w-full flex-col items-start px-3 py-2 text-left text-xs text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300 {{ $item['is_from_inventory'] && $item['type'] === 'fabric' && $item['item_id'] == explode(':', $opt['value'])[1] ? 'cs-selected' : '' }}">
                                                            <span class="block truncate">{{ $opt['label'] }}</span>
                                                            @if ($opt['description'] ?? null)
                                                                <span class="block truncate text-[10px] text-neutral-400">{{ $opt['description'] }}</span>
                                                            @endif
                                                        </button>
                                                    @endforeach
                                                @endif
                                                @if ($grouped->has('office_rents') && $grouped->get('office_rents')->isNotEmpty())
                                                    <div class="px-3 py-1.5 text-[10px] font-medium uppercase tracking-wider text-neutral-400">{{ __('Office Rentals') }}</div>
                                                    @foreach ($grouped->get('office_rents') as $opt)
                                                        <button type="button" data-cs-option data-cs-value="{{ $opt['value'] }}" data-cs-label="{{ $opt['label'] }}" class="flex w-full flex-col items-start px-3 py-2 text-left text-xs text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300 {{ $item['is_from_inventory'] && $item['type'] === 'office_rent' && $item['item_id'] == explode(':', $opt['value'])[1] ? 'cs-selected' : '' }}">
                                                            <span class="block truncate">{{ $opt['label'] }}</span>
                                                            @if ($opt['description'] ?? null)
                                                                <span class="block truncate text-[10px] text-neutral-400">{{ $opt['description'] }}</span>
                                                            @endif
                                                        </button>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                        <select wire:change="selectInventoryItem({{ $index }}, $event.target.value)" class="sr-only">
                                            <option value="">{{ __('Custom entry') }}</option>
                                            @if ($grouped->has('products') && $grouped->get('products')->isNotEmpty())
                                                @foreach ($grouped->get('products') as $opt)
                                                    <option value="{{ $opt['value'] }}" @selected($item['is_from_inventory'] && $item['type'] === 'product' && $item['item_id'] == explode(':', $opt['value'])[1])>{{ $opt['label'] }}</option>
                                                @endforeach
                                            @endif
                                            @if ($grouped->has('fabrics') && $grouped->get('fabrics')->isNotEmpty())
                                                @foreach ($grouped->get('fabrics') as $opt)
                                                    <option value="{{ $opt['value'] }}" @selected($item['is_from_inventory'] && $item['type'] === 'fabric' && $item['item_id'] == explode(':', $opt['value'])[1])>{{ $opt['label'] }}</option>
                                                @endforeach
                                            @endif
                                            @if ($grouped->has('office_rents') && $grouped->get('office_rents')->isNotEmpty())
                                                @foreach ($grouped->get('office_rents') as $opt)
                                                    <option value="{{ $opt['value'] }}" @selected($item['is_from_inventory'] && $item['type'] === 'office_rent' && $item['item_id'] == explode(':', $opt['value'])[1])>{{ $opt['label'] }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <button type="button" wire:click="removeItem({{ $index }})" class="ml-2 shrink-0 text-xs text-red-500 hover:text-red-700">&times;</button>
                                </div>

                                <flux:field class="mt-2">
                                    <flux:input wire:model="items.{{ $index }}.description" type="text" placeholder="{{ __('Description') }}" required />
                                    <flux:error name="items.{{ $index }}.description" />
                                </flux:field>

                                <div class="mt-2 grid grid-cols-3 gap-2">
                                    <flux:field>
                                        <flux:input wire:model="items.{{ $index }}.quantity" wire:input="recalculate" type="number" step="0.01" min="0.01" placeholder="{{ __('Qty') }}" />
                                        <flux:error name="items.{{ $index }}.quantity" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:input wire:model="items.{{ $index }}.unit_price" wire:input="recalculate" type="number" step="0.01" min="0" placeholder="{{ __('Price') }}" />
                                        <flux:error name="items.{{ $index }}.unit_price" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:input type="text" value="{{ formatCurrency($item['total']) }}" readonly class="bg-neutral-50 dark:bg-neutral-800" />
                                    </flux:field>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Discount, Tax, WHT — grouped in one row --}}
                <div class="grid grid-cols-3 gap-3">
                    <flux:field>
                        <flux:label class="text-xs">{{ __('Discount') }}</flux:label>
                        <div class="flex gap-1">
                            <div class="custom-select relative w-16 shrink-0">
                                <button type="button" data-cs-trigger class="flex w-full items-center justify-between rounded-lg border border-neutral-300 bg-white px-2 py-2 text-xs text-neutral-900 shadow-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white dark:focus:border-accent">
                                    <span wire:ignore data-cs-display>{{ __('—') }}</span>
                                    <svg class="size-3 shrink-0 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div data-cs-dropdown class="absolute left-0 right-0 top-full z-50 mt-1 hidden overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-lg dark:border-neutral-700 dark:bg-neutral-800">
                                    <div class="border-b border-neutral-200 p-2 dark:border-neutral-700">
                                        <input type="text" data-cs-search placeholder="Search..." class="w-full rounded-md border border-neutral-200 bg-neutral-50 px-2 py-1 text-xs text-neutral-900 outline-none placeholder:text-neutral-400 focus:border-accent focus:ring-1 focus:ring-accent/30 dark:border-neutral-600 dark:bg-neutral-700 dark:text-white dark:placeholder:text-neutral-500">
                                    </div>
                                    <div data-cs-options class="max-h-40 overflow-y-auto py-1">
                                        <button type="button" data-cs-option data-cs-value="" data-cs-label="—" class="cs-selected flex w-full items-center px-3 py-1.5 text-left text-xs text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('—') }}</button>
                                        <button type="button" data-cs-option data-cs-value="percentage" data-cs-label="%" class="flex w-full items-center px-3 py-1.5 text-left text-xs text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('%') }}</button>
                                        <button type="button" data-cs-option data-cs-value="fixed" data-cs-label="Fixed" class="flex w-full items-center px-3 py-1.5 text-left text-xs text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('Fixed') }}</button>
                                    </div>
                                </div>
                                <select wire:model="discount_type" wire:change="recalculate" class="sr-only">
                                    <option value="">{{ __('—') }}</option>
                                    <option value="percentage">{{ __('%') }}</option>
                                    <option value="fixed">{{ __('Fixed') }}</option>
                                </select>
                            </div>
                            <flux:input wire:model="discount_value" wire:input="recalculate" type="number" step="0.01" min="0" placeholder="0" class="flex-1 min-w-0" />
                        </div>
                    </flux:field>
                    <flux:field>
                        <flux:label class="text-xs">{{ __('Tax') }} <span class="text-[10px] text-neutral-400">(VAT 18%)</span></flux:label>
                        <flux:input wire:model="tax_rate" wire:input="recalculate" type="number" step="0.01" min="0" max="100" placeholder="%" class="w-full" />
                    </flux:field>
                    <flux:field>
                        <flux:label class="text-xs">{{ __('WHT') }}</flux:label>
                        <div class="flex gap-1">
                            <div class="custom-select relative w-16 shrink-0">
                                <button type="button" data-cs-trigger class="flex w-full items-center justify-between rounded-lg border border-neutral-300 bg-white px-2 py-2 text-xs text-neutral-900 shadow-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white dark:focus:border-accent">
                                    <span wire:ignore data-cs-display>{{ __('%') }}</span>
                                    <svg class="size-3 shrink-0 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div data-cs-dropdown class="absolute left-0 right-0 top-full z-50 mt-1 hidden overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-lg dark:border-neutral-700 dark:bg-neutral-800">
                                    <div class="border-b border-neutral-200 p-2 dark:border-neutral-700">
                                        <input type="text" data-cs-search placeholder="Search..." class="w-full rounded-md border border-neutral-200 bg-neutral-50 px-2 py-1 text-xs text-neutral-900 outline-none placeholder:text-neutral-400 focus:border-accent focus:ring-1 focus:ring-accent/30 dark:border-neutral-600 dark:bg-neutral-700 dark:text-white dark:placeholder:text-neutral-500">
                                    </div>
                                    <div data-cs-options class="max-h-40 overflow-y-auto py-1">
                                        <button type="button" data-cs-option data-cs-value="percentage" data-cs-label="%" class="cs-selected flex w-full items-center px-3 py-1.5 text-left text-xs text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('%') }}</button>
                                        <button type="button" data-cs-option data-cs-value="fixed" data-cs-label="Fixed" class="flex w-full items-center px-3 py-1.5 text-left text-xs text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('Fixed') }}</button>
                                    </div>
                                </div>
                                <select wire:model="wht_type" wire:change="recalculate" class="sr-only">
                                    <option value="percentage">{{ __('%') }}</option>
                                    <option value="fixed">{{ __('Fixed') }}</option>
                                </select>
                            </div>
                            <flux:input wire:model="wht_rate" wire:input="recalculate" type="number" step="0.01" min="0" placeholder="{{ $wht_type === 'fixed' ? 'Amount' : '%' }}" class="flex-1 min-w-0" />
                        </div>
                        @if ($wht_amount > 0)
                            <p class="mt-0.5 text-[10px] text-amber-600">{{ $wht_type === 'fixed' ? __('WHT Amount:') : __('WHT') }} {{ formatCurrency($wht_amount) }}</p>
                        @endif
                    </flux:field>
                </div>

                {{-- Notes --}}
                <flux:field>
                    <flux:label>{{ __('Notes') }}</flux:label>
                    <flux:textarea wire:model="notes" rows="2" placeholder="{{ __('Optional notes for the customer...') }}" />
                </flux:field>

                {{-- PDF Options --}}
                <div x-data="{ open: true }" class="rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                    <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-sm font-medium text-neutral-700 dark:text-neutral-300">
                        <span>{{ __('PDF Options') }}</span>
                        <svg class="size-4 transition" :class="open && 'rotate-180'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div x-show="open" class="mt-3 space-y-3">
                        <flux:field>
                            <flux:input wire:model="custom_title" x-on:input="$wire.$refresh()" type="text" :placeholder="__('Custom document title (default: QUOTATION)')" />
                        </flux:field>
                        <div class="grid grid-cols-2 gap-3">
                            <flux:checkbox wire:model="show_discount_column" x-on:change="$wire.$refresh()" :label="__('Show discount column')" />
                            <flux:checkbox wire:model="hide_total" x-on:change="$wire.$refresh()" :label="__('Hide grand total')" />
                            <flux:checkbox wire:model="show_amount_in_words" x-on:change="$wire.$refresh()" :label="__('Show amount in words')" />
                            <flux:checkbox wire:model="act_as_delivery_note" x-on:change="$wire.$refresh()" :label="__('Act as delivery note')" />
                            <flux:checkbox wire:model="tax_inclusive" x-on:change="$wire.$refresh()" :label="__('Tax inclusive (×1.18)')" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between border-t border-neutral-200 pt-6 dark:border-neutral-700">
                    <div>
                        @if ($editingId)
                            <flux:button wire:click="convertToInvoice" variant="warning" class="cursor-pointer">{{ __('Convert to Invoice') }}</flux:button>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <flux:button variant="filled" :href="route('quotations')" wire:navigate>{{ __('Cancel') }}</flux:button>
                        <flux:button variant="primary" type="submit">{{ $editingId ? __('Update') : __('Save Quotation') }}</flux:button>
                    </div>
                </div>
            </form>
        </div>

        {{-- RIGHT: Preview --}}
        <div class="print-area">
            <div class="sticky top-8">
                <div class="mb-3 flex items-center justify-between">
                    <flux:heading size="sm">{{ __('Preview') }}</flux:heading>
                    <button type="button" onclick="printPreview()" class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-gradient-to-r from-sky-500 to-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:from-sky-600 hover:to-blue-700 active:scale-95">
                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                        {{ __('Print') }}
                    </button>
                </div>
                <div class="preview-card rounded-xl border border-neutral-200 bg-white p-8 shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
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
                            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $custom_title ?: __('QUOTATION') }}</h1>
                            @if ($act_as_delivery_note)
                                <flux:badge variant="pill" color="blue" size="sm" class="mt-1">{{ __('Delivery Note') }}</flux:badge>
                            @endif
                            <p class="mt-1 font-mono text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                {{ $editingId ? $quotation_number : 'PREVIEW' }}
                            </p>
                        </div>
                    </div>

                    <hr class="my-4 border-neutral-200 dark:border-neutral-700">

                    {{-- Dates & Customer --}}
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <p><span class="font-medium text-neutral-500">{{ __('Issue Date:') }}</span> {{ $issue_date ? \Carbon\Carbon::parse($issue_date)->format('d M Y') : '—' }}</p>
                            <p class="mt-0.5"><span class="font-medium text-neutral-500">{{ __('Valid Until:') }}</span> {{ $valid_until ? \Carbon\Carbon::parse($valid_until)->format('d M Y') : '—' }}</p>
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
                                    <th class="px-3 py-2 text-left font-medium text-neutral-500">{{ __('Description') }}</th>
                                    <th class="px-3 py-2 text-right font-medium text-neutral-500">{{ __('Qty') }}</th>
                                    <th class="px-3 py-2 text-right font-medium text-neutral-500">{{ __('Price') }}</th>
                                    <th class="px-3 py-2 text-right font-medium text-neutral-500">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                                @forelse ($items as $item)
                                    @php
                                        $parts = explode(' — ', $item['description'], 2);
                                        $itemName = $parts[0] ?: '—';
                                        $itemDesc = $parts[1] ?? '';
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2">{{ $itemName }}</td>
                                        <td class="px-3 py-2 text-neutral-500">{{ $itemDesc ?: '—' }}</td>
                                        <td class="px-3 py-2 text-right">{{ number_format($item['quantity'], 2) }}</td>
                                        <td class="px-3 py-2 text-right">{{ formatCurrency($item['unit_price']) }}</td>
                                        <td class="px-3 py-2 text-right font-medium">{{ formatCurrency($item['total']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-4 text-center text-neutral-400">{{ __('No items added yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Totals --}}
                    @php
                        $previewSubtotal = $subtotal;
                        $previewDiscount = $discount_amount;
                        $previewTax = $tax_inclusive && $tax_rate > 0 ? round(($subtotal - $discount_amount) * ((float) $tax_rate / 100), 2) : 0;
                        $previewWht = $wht_amount;
                        $previewTotal = $previewTax > 0
                            ? round($subtotal - $discount_amount + $previewTax, 2)
                            : round($subtotal - $discount_amount, 2);
                    @endphp
                    <div class="mt-3 space-y-1 text-right text-xs">
                        <p><span class="inline-block w-28 text-neutral-500">{{ __('Subtotal:') }}</span> <span class="font-medium">{{ formatCurrency($previewSubtotal) }}</span></p>
                        @if (!$hide_total && $show_discount_column && $previewDiscount > 0)
                            <p><span class="inline-block w-28 text-neutral-500">{{ __('Discount:') }}</span> <span class="font-medium">-{{ formatCurrency($previewDiscount) }}</span></p>
                        @endif
                        @if ($previewTax > 0)
                            <p><span class="inline-block w-28 text-neutral-500">{{ $tax_name ?? 'Tax' }} ({{ $tax_rate ?? 0 }}%):</span> <span class="font-medium">{{ formatCurrency($previewTax) }}</span></p>
                        @endif
                        @if ($previewWht > 0)
                            <p><span class="inline-block w-28 text-neutral-500">{{ __('WHT') }}@if($wht_type === 'fixed') {{ __('(Fixed)') }}@else ({{ $wht_rate ?? 0 }}%)@endif:</span> <span class="font-medium text-amber-600">-{{ formatCurrency($previewWht) }}</span></p>
                        @endif
                        @if (!$hide_total)
                            <hr class="my-1 border-neutral-200 dark:border-neutral-700">
                            <p class="text-base font-bold">{{ formatCurrency($previewTotal) }}</p>
                            @if ($previewWht > 0)
                                <p class="text-xs text-neutral-500">{{ __('Payable:') }} <span class="font-semibold text-amber-600">{{ formatCurrency($previewTotal - $previewWht) }}</span></p>
                            @endif
                            @if ($show_amount_in_words)
                                <p class="text-[10px] italic text-neutral-500">{{ \App\Helpers\AmountInWords::convert($previewTotal) }}</p>
                            @endif
                        @endif
                    </div>

                    {{-- Notes & QR --}}
                    <div class="mt-4 flex items-end justify-between">
                        <div class="max-w-xs text-xs text-neutral-500">
                            @if ($notes)
                                <p>{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $notes))) !!}</p>
                            @endif
                            @if ($this->business && $this->business->quotes_notes)
                                <p class="mt-1 italic">{!! nl2br(e(preg_replace('/<br\s*\/?>/i', "\n", $this->business->quotes_notes))) !!}</p>
                            @endif
                        </div>
                <div class="shrink-0 text-right">
                    <p class="mb-1 text-[10px] text-neutral-400">{{ __('Scan to pay') }}</p>
                    {!! $this->qrSvg !!}
                </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
