<?php

use App\Helpers\QrCode;
use App\Models\Customer;
use App\Models\Fabric;
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

    public function mount($id = null): void
    {
        if (! auth()->user()->business) {
            $this->redirect(route('onboarding', absolute: false), navigate: true);
            return;
        }

        $this->issue_date = now()->format('Y-m-d');
        $this->valid_until = now()->addDays(14)->format('Y-m-d');

        if ($id) {
            $quotation = Quotation::with('items')->where('business_id', auth()->user()->business->id)->findOrFail($id);
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
        }

        $this->addItem();
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
            $product = ProductService::where('business_id', auth()->user()->business?->id)->find($id);
            if ($product) {
                $desc = $product->name;
                if ($product->description) $desc .= ' — ' . $product->description;
                $this->items[$index]['description'] = $desc;
                $this->items[$index]['unit_price'] = (float) ($product->selling_price ?? 0);
            }
        } elseif ($type === 'fabric') {
            $fabric = Fabric::where('business_id', auth()->user()->business?->id)->find($id);
            if ($fabric) {
                $desc = $fabric->name;
                if ($fabric->color) $desc .= ' (' . $fabric->color . ')';
                if ($fabric->description) $desc .= ' — ' . $fabric->description;
                $this->items[$index]['description'] = $desc;
                $this->items[$index]['unit_price'] = (float) ($fabric->selling_price_per_meter ?? 0);
            }
        } elseif ($type === 'office_rent') {
            $rental = ProductService::where('business_id', auth()->user()->business?->id)->where('type', 'office_rent')->find($id);
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
        $taxRate = (float) ($this->tax_rate ?? 0);
        $this->tax_amount = $taxRate > 0 ? round($afterDiscount * ($taxRate / 100), 2) : 0;

        $this->total = round($afterDiscount + $this->tax_amount, 2);
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
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->recalculate();

        $data = [
            'business_id' => auth()->user()->business->id,
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
        ];

        if ($this->editingId) {
            $quotation = Quotation::where('business_id', auth()->user()->business->id)->findOrFail($this->editingId);
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
            Flux::toast(variant: 'success', text: __('Quotation updated.'));
        } else {
            $check = $this->checkLimit(auth()->user()->business, 'quotations');
            if (!$check['allowed']) {
                Flux::toast(variant: 'danger', text: __($check['reason']));
                return;
            }

            $last = Quotation::where('business_id', auth()->user()->business->id)->orderBy('id', 'desc')->first();
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

        $quotation = Quotation::with('items')->where('business_id', auth()->user()->business->id)->findOrFail($this->editingId);

        if ($quotation->status === 'converted') {
            Flux::toast(variant: 'warning', text: __('This quotation has already been converted.'));
            return;
        }

        $check = $this->checkLimit(auth()->user()->business, 'invoices');
        if (!$check['allowed']) {
            Flux::toast(variant: 'danger', text: __($check['reason']));
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
        $this->redirect(route('invoices.edit', $invoice->id), navigate: true);
    }

    public function getInventoryItemsProperty(): array
    {
        $businessId = auth()->user()->business?->id;
        if (! $businessId) return [];

        $products = ProductService::where('business_id', $businessId)
            ->where('type', 'product')
            ->get(['id', 'name', 'description', 'selling_price'])
            ->map(fn($p) => [
                'value' => 'product:' . $p->id,
                'label' => $p->name . '  —  UGX ' . number_format($p->selling_price ?? 0, 0),
                'group' => 'products',
                'description' => $p->description,
            ]);

        $fabrics = Fabric::where('business_id', $businessId)
            ->get(['id', 'name', 'color', 'description', 'selling_price_per_meter'])
            ->map(fn($f) => [
                'value' => 'fabric:' . $f->id,
                'label' => $f->name . ($f->color ? ' (' . $f->color . ')' : '') . '  —  UGX ' . number_format($f->selling_price_per_meter ?? 0, 0) . '/m',
                'group' => 'fabrics',
                'description' => $f->description,
            ]);

        $officeRents = ProductService::where('business_id', $businessId)
            ->where('type', 'office_rent')
            ->get(['id', 'name', 'description', 'selling_price'])
            ->map(fn($r) => [
                'value' => 'office_rent:' . $r->id,
                'label' => $r->name . '  —  UGX ' . number_format($r->selling_price ?? 0, 0) . '/mo',
                'group' => 'office_rents',
                'description' => $r->description,
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

    public function getQrSvgProperty(): string
    {
        $number = $this->editingId ? $this->quotation_number : 'PREVIEW';
        $data = $this->business?->name . "\n" . $number . "\nUGX " . number_format($this->total, 2);
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
                            <span data-cs-display>{{ __('Walk-in Customer') }}</span>
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
                            <div wire:key="item-{{ $item['key'] }}" class="rounded-lg border border-neutral-200 bg-white p-3 dark:border-neutral-700 dark:bg-neutral-900">
                                <div class="mb-2 flex items-center justify-between">
                                    <div class="custom-select relative w-full">
                                        @php $grouped = collect($this->inventoryItems)->groupBy('group'); @endphp
                                        <button type="button" data-cs-trigger class="flex w-full items-center justify-between rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs text-neutral-900 shadow-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white dark:focus:border-accent">
                                            <span data-cs-display>{{ __('Custom entry') }}</span>
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
                            <div class="custom-select relative w-32">
                                <button type="button" data-cs-trigger class="flex w-full items-center justify-between rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white dark:focus:border-accent">
                                    <span data-cs-display>{{ __('None') }}</span>
                                    <svg class="size-4 shrink-0 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div data-cs-dropdown class="absolute left-0 right-0 top-full z-50 mt-1 hidden overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-lg dark:border-neutral-700 dark:bg-neutral-800">
                                    <div class="border-b border-neutral-200 p-2 dark:border-neutral-700">
                                        <input type="text" data-cs-search placeholder="Search..." class="w-full rounded-md border border-neutral-200 bg-neutral-50 px-2.5 py-1.5 text-xs text-neutral-900 outline-none placeholder:text-neutral-400 focus:border-accent focus:ring-1 focus:ring-accent/30 dark:border-neutral-600 dark:bg-neutral-700 dark:text-white dark:placeholder:text-neutral-500">
                                    </div>
                                    <div data-cs-options class="max-h-48 overflow-y-auto py-1">
                                        <button type="button" data-cs-option data-cs-value="" data-cs-label="None" class="cs-selected flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('None') }}</button>
                                        <button type="button" data-cs-option data-cs-value="percentage" data-cs-label="%" class="flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('%') }}</button>
                                        <button type="button" data-cs-option data-cs-value="fixed" data-cs-label="Fixed" class="flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('Fixed') }}</button>
                                    </div>
                                </div>
                                <select wire:model="discount_type" wire:change="recalculate" class="sr-only">
                                    <option value="">{{ __('None') }}</option>
                                    <option value="percentage">{{ __('%') }}</option>
                                    <option value="fixed">{{ __('Fixed') }}</option>
                                </select>
                            </div>
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
                            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">{{ __('QUOTATION') }}</h1>
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
                        <div class="shrink-0">
                            {!! $this->qrSvg !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
