<?php

use App\Helpers\QrCode;
use App\Models\Customer;
use App\Models\Fabric;
use App\Models\Invoice;
use App\Models\ProductService;
use App\Traits\LogsActivity;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Invoice')] class extends Component {
    use \App\Traits\ChecksSubscriptionLimits;

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

    public bool $show_discount_column = false;
    public bool $hide_total = false;
    public string $custom_title = '';
    public bool $show_amount_in_words = false;
    public bool $act_as_delivery_note = false;
    public bool $tax_inclusive = false;

    public ?string $wht_type = null;
    public ?string $wht_rate = null;
    public float $wht_amount = 0;

    public float $payment_amount = 0;
    public string $payment_date = '';
    public string $payment_method = 'cash';
    public string $payment_reference = '';
    public string $payment_notes = '';

    public function mount($id = null, $quotation = null): void
    {
        if (! currentBusiness()) {
            $this->redirect(route('onboarding', absolute: false), navigate: true);
            return;
        }

        $this->issue_date = now()->format('Y-m-d');
        $this->due_date = now()->addDays(30)->format('Y-m-d');
        $this->payment_date = now()->format('Y-m-d');
        $this->tax_name = __('VAT');

        if ($quotation) {
            $q = Quotation::with('items')->where('business_id', currentBusiness()->id)->findOrFail($quotation);
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

            $this->wht_rate = $q->wht_rate !== null ? (string) $q->wht_rate : null;
            $this->wht_amount = (float) $q->wht_amount;
            $this->wht_type = (float) ($this->wht_rate ?? 0) > 0 ? 'percentage' : 'fixed';
            if ($this->wht_type === 'fixed' && $this->wht_amount > 0) {
                $this->wht_rate = (string) $this->wht_amount;
            }

            $this->recalculate();
        }

        if ($id) {
            $invoice = Invoice::with('items', 'payments')->where('business_id', currentBusiness()->id)->findOrFail($id);
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

            $this->show_discount_column = $invoice->show_discount_column;
            $this->hide_total = $invoice->hide_total;
            $this->custom_title = $invoice->custom_title ?? '';
            $this->show_amount_in_words = $invoice->show_amount_in_words;
            $this->act_as_delivery_note = $invoice->act_as_delivery_note;
            $this->tax_inclusive = $invoice->tax_inclusive;
            $this->wht_rate = $invoice->wht_rate !== null ? (string) $invoice->wht_rate : null;
            $this->wht_amount = (float) $invoice->wht_amount;
            $this->wht_type = (float) ($this->wht_rate ?? 0) > 0 ? 'percentage' : 'fixed';
            if ($this->wht_type === 'fixed' && $this->wht_amount > 0) {
                $this->wht_rate = (string) $this->wht_amount;
            }

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

        if (in_array($property, ['discount_type', 'discount_value', 'tax_rate', 'tax_name', 'wht_rate', 'wht_type'])) {
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
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
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
            $invoice = Invoice::where('business_id', currentBusiness()->id)->findOrFail($this->editingId);
            // Restore inventory from old items before replacing them
            $this->adjustInventory($invoice->items->toArray(), restore: true);
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
            $this->adjustInventory($this->items);
            $this->invoice_number = $invoice->fresh()->invoice_number;
            LogsActivity::log('invoice_updated', "Updated invoice {$invoice->invoice_number}", $invoice, ['total' => $invoice->total]);
            Flux::toast(variant: 'success', text: __('Invoice updated.'));
        } else {
            $check = $this->checkLimit('invoices');
            if (!$check['allowed']) {
                Flux::toast(variant: 'danger', text: __($check['reason']));
                return;
            }

            $last = Invoice::where('business_id', currentBusiness()->id)->orderBy('id', 'desc')->first();
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
            $this->adjustInventory($this->items);
            $this->invoice_number = $invoice->fresh()->invoice_number;
            $this->editingId = $invoice->id;
            LogsActivity::log('invoice_created', "Created invoice {$invoice->invoice_number}", $invoice, ['total' => $invoice->total]);
            Flux::toast(variant: 'success', text: __('Invoice created.'));
        }
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

        $invoice = Invoice::where('business_id', currentBusiness()->id)->findOrFail($this->editingId);

        $check = $this->checkLimit('receipts');
        if (!$check['allowed']) {
            Flux::toast(variant: 'danger', text: __($check['reason']));
            return;
        }

        $last = \App\Models\Payment::whereHas('invoice', fn($q) => $q->where('business_id', currentBusiness()->id))
            ->whereNotNull('receipt_number')
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

        LogsActivity::log('payment_recorded', "Recorded payment {$receiptNumber} on invoice {$invoice->invoice_number}", $invoice, ['amount' => $this->payment_amount, 'receipt' => $receiptNumber]);

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
        $payment = \App\Models\Payment::whereHas('invoice', fn($q) => $q->where('business_id', currentBusiness()->id))
            ->findOrFail($paymentId);
        LogsActivity::log('payment_deleted', "Deleted payment " . formatCurrency($payment->amount) . " from invoice {$payment->invoice->invoice_number}", $payment, ['amount' => $payment->amount, 'receipt' => $payment->receipt_number]);
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
        $data = $this->business?->name . "\n" . $number . "\n" . formatCurrency($this->total);
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
                            <flux:input wire:model="custom_title" x-on:input="$wire.$refresh()" type="text" :placeholder="__('Custom document title (default: INVOICE)')" />
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
                                                <span class="font-medium">{{ formatCurrency($p->amount) }}</span>
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
                            <span class="font-bold text-green-600">{{ formatCurrency($paid_amount) }}</span>
                        </div>
                        @if ($total > $paid_amount)
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-neutral-500">{{ __('Balance Due') }}</span>
                                <span class="font-bold text-amber-600">{{ formatCurrency(max(0, $total - $paid_amount)) }}</span>
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
                                <div class="custom-select relative">
                                    <button type="button" data-cs-trigger class="flex w-full items-center justify-between rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white dark:focus:border-accent">
                                        <span wire:ignore data-cs-display>{{ __('Cash') }}</span>
                                        <svg class="size-4 shrink-0 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                    </button>
                                    <div data-cs-dropdown class="absolute left-0 right-0 top-full z-50 mt-1 hidden overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-lg dark:border-neutral-700 dark:bg-neutral-800">
                                        <div class="border-b border-neutral-200 p-2 dark:border-neutral-700">
                                            <input type="text" data-cs-search placeholder="Search..." class="w-full rounded-md border border-neutral-200 bg-neutral-50 px-2.5 py-1.5 text-xs text-neutral-900 outline-none placeholder:text-neutral-400 focus:border-accent focus:ring-1 focus:ring-accent/30 dark:border-neutral-600 dark:bg-neutral-700 dark:text-white dark:placeholder:text-neutral-500">
                                        </div>
                                        <div data-cs-options class="max-h-48 overflow-y-auto py-1">
                                            <button type="button" data-cs-option data-cs-value="cash" data-cs-label="Cash" class="cs-selected flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('Cash') }}</button>
                                            <button type="button" data-cs-option data-cs-value="bank" data-cs-label="Bank" class="flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('Bank') }}</button>
                                            <button type="button" data-cs-option data-cs-value="mobile_money" data-cs-label="Mobile Money" class="flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('Mobile Money') }}</button>
                                            <button type="button" data-cs-option data-cs-value="card" data-cs-label="Card" class="flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('Card') }}</button>
                                            <button type="button" data-cs-option data-cs-value="other" data-cs-label="Other" class="flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('Other') }}</button>
                                        </div>
                                    </div>
                                    <select wire:model="payment_method" class="sr-only">
                                        <option value="cash">{{ __('Cash') }}</option>
                                        <option value="bank">{{ __('Bank') }}</option>
                                        <option value="mobile_money">{{ __('Mobile Money') }}</option>
                                        <option value="card">{{ __('Card') }}</option>
                                        <option value="other">{{ __('Other') }}</option>
                                    </select>
                                </div>
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
                            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $custom_title ?: __('INVOICE') }}</h1>
                            @if ($act_as_delivery_note)
                                <flux:badge variant="pill" color="blue" size="sm" class="mt-1">{{ __('Delivery Note') }}</flux:badge>
                            @endif
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

                    {{-- Receipts --}}
                    @if ($editingId)
                        <div class="mt-4">
                            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-500">{{ __('Receipts') }}</h3>
                            @if ($this->payments->isNotEmpty())
                                <div class="space-y-1 text-xs">
                                    @foreach ($this->payments as $p)
                                        <div class="flex justify-between">
                                            <span class="font-mono text-neutral-600 dark:text-neutral-400">{{ $p->receipt_number }}</span>
                                            <span class="font-medium">{{ formatCurrency($p->amount) }}</span>
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
                                    <span class="font-medium">{{ formatCurrency($total) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium text-neutral-500">{{ __('Paid') }}</span>
                                    <span class="font-medium text-green-600">{{ formatCurrency($paid_amount) }}</span>
                                </div>
                                <div class="flex justify-between border-t border-neutral-200 pt-1 dark:border-neutral-700">
                                    <span class="font-semibold text-neutral-600 dark:text-neutral-300">{{ __('Balance Due') }}</span>
                                    <span class="font-semibold {{ $total > $paid_amount ? 'text-amber-600' : 'text-green-600' }}">{{ formatCurrency(max(0, $total - $paid_amount)) }}</span>
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
