<?php

use App\Models\Fabric;
use App\Models\ProductService;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Title('Inventory')] class extends Component {
    use WithFileUploads, WithPagination;

    #[Url]
    public string $tab = 'fabrics';

    #[Url]
    public string $search = '';

    #[Url]
    public string $sortField = 'created_at';

    #[Url]
    public string $sortDirection = 'desc';

    public bool $showFabricModal = false;

    public ?int $fabricEditingId = null;

    public string $fabric_roll_code = '';

    public string $fabric_name = '';

    public string $fabric_color = '';

    public string $fabric_supplier = '';

    public ?string $fabric_date_received = null;

    public ?string $fabric_claimed_meters = null;

    public ?string $fabric_verified_meters = null;

    public ?string $fabric_used_meters = null;

    public ?string $fabric_buying_price = null;

    public ?string $fabric_selling_price_per_meter = null;

    public string $fabric_width = '';

    public $fabric_image = null;

    public bool $fabric_remove_image = false;

    public bool $showProductModal = false;

    public ?int $productEditingId = null;

    public string $ps_type = 'product';

    public string $ps_name = '';

    public string $ps_sku = '';

    public string $ps_description = '';

    public ?string $ps_buying_price = null;

    public ?string $ps_selling_price = null;

    public string $ps_unit = '';

    public $ps_image = null;

    public bool $ps_remove_image = false;

    public bool $showOfficeRentModal = false;

    public ?int $officeRentEditingId = null;

    public string $or_name = '';

    public string $or_location = '';

    public ?string $or_monthly_rent = null;

    public string $or_description = '';

    public $or_image = null;

    public bool $or_remove_image = false;

    public function mount(): void
    {
        if (! auth()->user()->business) {
            $this->redirect(route('onboarding', absolute: false), navigate: true);
        }
    }

    public function fabrics()
    {
        return Fabric::where('business_id', auth()->user()->business->id)
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('roll_code', 'like', '%' . $this->search . '%')
                  ->orWhere('name', 'like', '%' . $this->search . '%')
                  ->orWhere('supplier', 'like', '%' . $this->search . '%')
                  ->orWhere('color', 'like', '%' . $this->search . '%');
            }))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);
    }

    public function products()
    {
        return ProductService::where('business_id', auth()->user()->business->id)
            ->whereIn('type', ['product', 'service'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
            }))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);
    }

    public function officeRents()
    {
        return ProductService::where('business_id', auth()->user()->business->id)
            ->where('type', 'office_rent')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            }))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function editFabric(Fabric $fabric): void
    {
        $this->fabricEditingId = $fabric->id;
        $this->fabric_roll_code = $fabric->roll_code;
        $this->fabric_name = $fabric->name;
        $this->fabric_color = $fabric->color ?? '';
        $this->fabric_supplier = $fabric->supplier ?? '';
        $this->fabric_date_received = $fabric->date_received?->format('Y-m-d');
        $this->fabric_claimed_meters = $fabric->claimed_meters !== null ? (string) $fabric->claimed_meters : null;
        $this->fabric_verified_meters = $fabric->verified_meters !== null ? (string) $fabric->verified_meters : null;
        $this->fabric_used_meters = $fabric->used_meters !== null ? (string) $fabric->used_meters : null;
        $this->fabric_buying_price = $fabric->buying_price !== null ? (string) $fabric->buying_price : null;
        $this->fabric_selling_price_per_meter = $fabric->selling_price_per_meter !== null ? (string) $fabric->selling_price_per_meter : null;
        $this->fabric_width = $fabric->width ?? '';
        $this->fabric_image = null;
        $this->fabric_remove_image = false;
        $this->showFabricModal = true;
    }

    public function saveFabric(): void
    {
        $this->validate([
            'fabric_roll_code' => ['required', 'string', 'max:255'],
            'fabric_name' => ['required', 'string', 'max:255'],
            'fabric_color' => ['nullable', 'string', 'max:255'],
            'fabric_supplier' => ['nullable', 'string', 'max:255'],
            'fabric_date_received' => ['nullable', 'date'],
            'fabric_claimed_meters' => ['nullable', 'numeric', 'min:0'],
            'fabric_verified_meters' => ['nullable', 'numeric', 'min:0'],
            'fabric_used_meters' => ['nullable', 'numeric', 'min:0'],
            'fabric_buying_price' => ['nullable', 'numeric', 'min:0'],
            'fabric_selling_price_per_meter' => ['nullable', 'numeric', 'min:0'],
            'fabric_width' => ['nullable', 'string', 'max:255'],
            'fabric_image' => ['nullable', 'image', 'max:2048'],
        ]);

        $data = [
            'roll_code' => $this->fabric_roll_code,
            'name' => $this->fabric_name,
            'color' => $this->fabric_color ?: null,
            'supplier' => $this->fabric_supplier ?: null,
            'date_received' => $this->fabric_date_received ?: null,
            'claimed_meters' => $this->fabric_claimed_meters !== null && $this->fabric_claimed_meters !== '' ? (float) $this->fabric_claimed_meters : null,
            'verified_meters' => $this->fabric_verified_meters !== null && $this->fabric_verified_meters !== '' ? (float) $this->fabric_verified_meters : null,
            'used_meters' => $this->fabric_used_meters !== null && $this->fabric_used_meters !== '' ? (float) $this->fabric_used_meters : 0,
            'buying_price' => $this->fabric_buying_price !== null && $this->fabric_buying_price !== '' ? (float) $this->fabric_buying_price : null,
            'selling_price_per_meter' => $this->fabric_selling_price_per_meter !== null && $this->fabric_selling_price_per_meter !== '' ? (float) $this->fabric_selling_price_per_meter : null,
            'width' => $this->fabric_width ?: null,
        ];

        if ($this->fabric_image) {
            $data['image'] = $this->fabric_image->store('fabrics', 'public');
        } elseif ($this->fabric_remove_image && $this->fabricEditingId) {
            $data['image'] = null;
        }

        if ($this->fabricEditingId) {
            Fabric::where('business_id', auth()->user()->business->id)
                ->findOrFail($this->fabricEditingId)
                ->update($data);
            Flux::toast(variant: 'success', text: __('Fabric updated.'));
        } else {
            $data['business_id'] = auth()->user()->business->id;
            Fabric::create($data);
            Flux::toast(variant: 'success', text: __('Fabric added.'));
        }

        $this->resetFabricForm();
        $this->showFabricModal = false;
    }

    public function deleteFabric(Fabric $fabric): void
    {
        $fabric->delete();
        Flux::toast(variant: 'success', text: __('Fabric deleted.'));
    }

    public function editProduct(ProductService $product): void
    {
        $this->productEditingId = $product->id;
        $this->ps_type = $product->type;
        $this->ps_name = $product->name;
        $this->ps_sku = $product->sku ?? '';
        $this->ps_description = $product->description ?? '';
        $this->ps_buying_price = $product->buying_price !== null ? (string) $product->buying_price : null;
        $this->ps_selling_price = $product->selling_price !== null ? (string) $product->selling_price : null;
        $this->ps_unit = $product->unit ?? '';
        $this->ps_image = null;
        $this->ps_remove_image = false;
        $this->showProductModal = true;
    }

    public function saveProduct(): void
    {
        $this->validate([
            'ps_type' => ['required', 'in:product,service'],
            'ps_name' => ['required', 'string', 'max:255'],
            'ps_sku' => ['nullable', 'string', 'max:255'],
            'ps_description' => ['nullable', 'string', 'max:2000'],
            'ps_buying_price' => ['nullable', 'numeric', 'min:0'],
            'ps_selling_price' => ['nullable', 'numeric', 'min:0'],
            'ps_unit' => ['nullable', 'string', 'max:255'],
            'ps_image' => ['nullable', 'image', 'max:2048'],
        ]);

        $data = [
            'type' => $this->ps_type,
            'name' => $this->ps_name,
            'sku' => $this->ps_sku ?: null,
            'description' => $this->ps_description ?: null,
            'buying_price' => $this->ps_buying_price !== null && $this->ps_buying_price !== '' ? (float) $this->ps_buying_price : null,
            'selling_price' => $this->ps_selling_price !== null && $this->ps_selling_price !== '' ? (float) $this->ps_selling_price : null,
            'unit' => $this->ps_unit ?: null,
        ];

        if ($this->ps_image) {
            $data['image'] = $this->ps_image->store('products', 'public');
        } elseif ($this->ps_remove_image && $this->productEditingId) {
            $data['image'] = null;
        }

        if ($this->productEditingId) {
            ProductService::where('business_id', auth()->user()->business->id)
                ->findOrFail($this->productEditingId)
                ->update($data);
            Flux::toast(variant: 'success', text: __('Product updated.'));
        } else {
            $data['business_id'] = auth()->user()->business->id;
            ProductService::create($data);
            Flux::toast(variant: 'success', text: __('Product added.'));
        }

        $this->resetProductForm();
        $this->showProductModal = false;
    }

    public function deleteProduct(ProductService $product): void
    {
        $product->delete();
        $label = $product->type === 'office_rent' ? __('Office rental deleted.') : __('Product deleted.');
        Flux::toast(variant: 'success', text: $label);
    }

    public function editOfficeRent(ProductService $rental): void
    {
        $this->officeRentEditingId = $rental->id;
        $this->or_name = $rental->name;
        $this->or_location = $rental->sku ?? '';
        $this->or_monthly_rent = $rental->selling_price !== null ? (string) $rental->selling_price : null;
        $this->or_description = $rental->description ?? '';
        $this->or_image = null;
        $this->or_remove_image = false;
        $this->showOfficeRentModal = true;
    }

    public function saveOfficeRent(): void
    {
        $this->validate([
            'or_name' => ['required', 'string', 'max:255'],
            'or_location' => ['nullable', 'string', 'max:255'],
            'or_monthly_rent' => ['nullable', 'numeric', 'min:0'],
            'or_description' => ['nullable', 'string', 'max:2000'],
            'or_image' => ['nullable', 'image', 'max:2048'],
        ]);

        $data = [
            'type' => 'office_rent',
            'name' => $this->or_name,
            'sku' => $this->or_location ?: null,
            'description' => $this->or_description ?: null,
            'selling_price' => $this->or_monthly_rent !== null && $this->or_monthly_rent !== '' ? (float) $this->or_monthly_rent : null,
        ];

        if ($this->or_image) {
            $data['image'] = $this->or_image->store('office-rentals', 'public');
        } elseif ($this->or_remove_image && $this->officeRentEditingId) {
            $data['image'] = null;
        }

        if ($this->officeRentEditingId) {
            ProductService::where('business_id', auth()->user()->business->id)
                ->findOrFail($this->officeRentEditingId)
                ->update($data);
            Flux::toast(variant: 'success', text: __('Office rental updated.'));
        } else {
            $data['business_id'] = auth()->user()->business->id;
            ProductService::create($data);
            Flux::toast(variant: 'success', text: __('Office rental added.'));
        }

        $this->resetOfficeRentForm();
        $this->showOfficeRentModal = false;
    }

    public function switchTab(string $tab): void
    {
        $this->tab = $tab;
        $this->search = '';
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    private function resetFabricForm(): void
    {
        $this->reset([
            'fabricEditingId', 'fabric_roll_code', 'fabric_name', 'fabric_color',
            'fabric_supplier', 'fabric_date_received', 'fabric_claimed_meters',
            'fabric_verified_meters', 'fabric_used_meters', 'fabric_buying_price',
            'fabric_selling_price_per_meter', 'fabric_width', 'fabric_image',
            'fabric_remove_image',
        ]);
    }

    private function resetProductForm(): void
    {
        $this->reset([
            'productEditingId', 'ps_type', 'ps_name', 'ps_sku', 'ps_description',
            'ps_buying_price', 'ps_selling_price', 'ps_unit', 'ps_image', 'ps_remove_image',
        ]);
    }

    private function resetOfficeRentForm(): void
    {
        $this->reset([
            'officeRentEditingId', 'or_name', 'or_location', 'or_monthly_rent',
            'or_description', 'or_image', 'or_remove_image',
        ]);
    }
}; ?>

<div class="mx-auto" style="width: 80%;">
    <flux:heading size="xl">{{ __('Inventory') }}</flux:heading>
    <flux:subheading class="mt-1">{{ __('Manage fabric rolls, products, services, and office rentals.') }}</flux:subheading>

    <div class="mt-6 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <flux:input wire:model.live.debounce.300ms="search" :placeholder="$tab === 'fabrics' ? __('Search fabrics...') : ($tab === 'office_rents' ? __('Search office rentals...') : __('Search products...'))" icon="magnifying-glass" clearable class="w-72" />
        </div>

        @if ($tab === 'fabrics')
            <flux:button variant="primary" wire:click="$set('showFabricModal', true)">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
                {{ __('Add Fabric Roll') }}
            </flux:button>
        @elseif ($tab === 'office_rents')
            <flux:button variant="primary" wire:click="$set('showOfficeRentModal', true)">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
                {{ __('Add Office Rental') }}
            </flux:button>
        @else
            <flux:button variant="primary" wire:click="$set('showProductModal', true)">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
                {{ $tab === 'fabrics' ? __('Add Fabric Roll') : __('Add Product / Service') }}
            </flux:button>
        @endif
    </div>

    <div class="mt-6 border-b border-neutral-200 dark:border-neutral-700">
        <div class="flex gap-6">
            <button type="button" wire:click="switchTab('fabrics')" class="cursor-pointer pb-3 text-sm font-medium transition-colors {{ $tab === 'fabrics' ? 'border-b-2 border-neutral-900 text-neutral-900 dark:border-white dark:text-white' : 'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white' }}">
                {{ __('Fabrics') }}
            </button>
            <button type="button" wire:click="switchTab('products')" class="cursor-pointer pb-3 text-sm font-medium transition-colors {{ $tab === 'products' ? 'border-b-2 border-neutral-900 text-neutral-900 dark:border-white dark:text-white' : 'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white' }}">
                {{ __('Products & Services') }}
            </button>
            <button type="button" wire:click="switchTab('office_rents')" class="cursor-pointer pb-3 text-sm font-medium transition-colors {{ $tab === 'office_rents' ? 'border-b-2 border-neutral-900 text-neutral-900 dark:border-white dark:text-white' : 'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white' }}">
                {{ __('Office Rentals') }}
            </button>
        </div>
    </div>

    @if ($tab === 'fabrics')
        <div class="mt-4">
            <flux:table :paginate="$this->fabrics()">
                <flux:table.columns>
                    <flux:table.column>{{ __('Image') }}</flux:table.column>
                    <flux:table.column :sortable="true" :sorted="$sortField === 'roll_code'" :direction="$sortField === 'roll_code' ? $sortDirection : null" wire:click="sortBy('roll_code')">{{ __('Roll Code') }}</flux:table.column>
                    <flux:table.column :sortable="true" :sorted="$sortField === 'name'" :direction="$sortField === 'name' ? $sortDirection : null" wire:click="sortBy('name')">{{ __('Name') }}</flux:table.column>
                    <flux:table.column :sortable="true" :sorted="$sortField === 'color'" :direction="$sortField === 'color' ? $sortDirection : null" wire:click="sortBy('color')">{{ __('Color') }}</flux:table.column>
                    <flux:table.column :sortable="true" :sorted="$sortField === 'supplier'" :direction="$sortField === 'supplier' ? $sortDirection : null" wire:click="sortBy('supplier')">{{ __('Supplier') }}</flux:table.column>
                    <flux:table.column align="center">{{ __('Stock (m)') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Price/m') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->fabrics() as $fabric)
                        <flux:table.row :key="$fabric->id">
                            <flux:table.cell>
                                @if ($fabric->image)
                                    <img src="{{ Storage::url($fabric->image) }}" alt="{{ $fabric->name }}" class="size-10 rounded-md object-cover">
                                @else
                                    <div class="flex size-10 items-center justify-center rounded-md bg-neutral-100 dark:bg-neutral-800">
                                        <svg class="size-5 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                    </div>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="font-mono text-xs font-medium">{{ $fabric->roll_code }}</flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $fabric->name }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($fabric->color)
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="inline-block size-3.5 rounded-full border border-neutral-300 dark:border-neutral-600" style="background-color: {{ $fabric->color }};"></span>
                                        {{ $fabric->color }}
                                    </span>
                                @else
                                    —
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $fabric->supplier ?? '—' }}</flux:table.cell>
                            <flux:table.cell align="center" class="text-xs">
                                <span class="text-zinc-500">{{ __('Verified') }}:</span> {{ $fabric->verified_meters ? number_format($fabric->verified_meters, 2) . 'm' : '—' }}
                                <br>
                                <span class="text-zinc-500">{{ __('Used') }}:</span> {{ number_format($fabric->used_meters, 2) . 'm' }}
                                <br>
                                <span class="font-medium {{ $fabric->remaining_meters !== null && $fabric->remaining_meters < 1 ? 'text-red-500' : '' }}">{{ __('Remaining') }}:</span> <span class="font-medium {{ $fabric->remaining_meters !== null && $fabric->remaining_meters < 1 ? 'text-red-500' : '' }}">{{ $fabric->remaining_meters !== null ? number_format($fabric->remaining_meters, 2) . 'm' : '—' }}</span>
                            </flux:table.cell>
                            <flux:table.cell align="end" class="font-medium">{{ $fabric->selling_price_per_meter ? 'UGX ' . number_format($fabric->selling_price_per_meter, 2) . '/m' : '—' }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button wire:click="editFabric({{ $fabric->id }})" variant="ghost" size="sm" icon="pencil-square" />
                                    <flux:button wire:click="deleteFabric({{ $fabric->id }})" variant="ghost" size="sm" icon="trash" class="text-red-500 hover:text-red-700!" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8">
                                <div class="flex flex-col items-center justify-center py-12 text-center">
                                    <flux:heading class="text-zinc-500 dark:text-zinc-400">{{ __('No fabric rolls yet') }}</flux:heading>
                                    <flux:subheading class="mt-1">{{ __('Add your first fabric roll to start tracking inventory.') }}</flux:subheading>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    @elseif ($tab === 'office_rents')
        <div class="mt-4">
            <flux:table :paginate="$this->officeRents()">
                <flux:table.columns>
                    <flux:table.column>{{ __('Image') }}</flux:table.column>
                    <flux:table.column :sortable="true" :sorted="$sortField === 'name'" :direction="$sortField === 'name' ? $sortDirection : null" wire:click="sortBy('name')">{{ __('Name') }}</flux:table.column>
                    <flux:table.column :sortable="true" :sorted="$sortField === 'sku'" :direction="$sortField === 'sku' ? $sortDirection : null" wire:click="sortBy('sku')">{{ __('Location') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Monthly Rent') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->officeRents() as $rental)
                        <flux:table.row :key="$rental->id">
                            <flux:table.cell>
                                @if ($rental->image)
                                    <img src="{{ Storage::url($rental->image) }}" alt="{{ $rental->name }}" class="size-10 rounded-md object-cover">
                                @else
                                    <div class="flex size-10 items-center justify-center rounded-md bg-neutral-100 dark:bg-neutral-800">
                                        <svg class="size-5 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                    </div>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $rental->name }}</flux:table.cell>
                            <flux:table.cell class="font-mono text-xs">{{ $rental->sku ?? '—' }}</flux:table.cell>
                            <flux:table.cell align="end" class="font-medium">{{ $rental->selling_price ? 'UGX ' . number_format($rental->selling_price, 2) : '—' }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button wire:click="editOfficeRent({{ $rental->id }})" variant="ghost" size="sm" icon="pencil-square" />
                                    <flux:button wire:click="deleteProduct({{ $rental->id }})" variant="ghost" size="sm" icon="trash" class="text-red-500 hover:text-red-700!" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5">
                                <div class="flex flex-col items-center justify-center py-12 text-center">
                                    <flux:heading class="text-zinc-500 dark:text-zinc-400">{{ __('No office rentals yet') }}</flux:heading>
                                    <flux:subheading class="mt-1">{{ __('Add your first office rental space.') }}</flux:subheading>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    @else
        <div class="mt-4">
            <flux:table :paginate="$this->products()">
                <flux:table.columns>
                    <flux:table.column>{{ __('Image') }}</flux:table.column>
                    <flux:table.column :sortable="true" :sorted="$sortField === 'name'" :direction="$sortField === 'name' ? $sortDirection : null" wire:click="sortBy('name')">{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Type') }}</flux:table.column>
                    <flux:table.column :sortable="true" :sorted="$sortField === 'sku'" :direction="$sortField === 'sku' ? $sortDirection : null" wire:click="sortBy('sku')">{{ __('SKU') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Buying Price') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Selling Price') }}</flux:table.column>
                    <flux:table.column>{{ __('Unit') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->products() as $product)
                        <flux:table.row :key="$product->id">
                            <flux:table.cell>
                                @if ($product->image)
                                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="size-10 rounded-md object-cover">
                                @else
                                    <div class="flex size-10 items-center justify-center rounded-md bg-neutral-100 dark:bg-neutral-800">
                                        <svg class="size-5 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                    </div>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $product->name }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge inset="top" :variant="$product->type === 'product' ? 'primary' : 'pill'" size="sm">
                                    {{ $product->type === 'product' ? __('Product') : __('Service') }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="font-mono text-xs">{{ $product->sku ?? '—' }}</flux:table.cell>
                            <flux:table.cell align="end" class="font-medium">{{ $product->buying_price ? 'UGX ' . number_format($product->buying_price, 2) : '—' }}</flux:table.cell>
                            <flux:table.cell align="end" class="font-medium">{{ $product->selling_price ? 'UGX ' . number_format($product->selling_price, 2) : '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $product->unit ?? '—' }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button wire:click="editProduct({{ $product->id }})" variant="ghost" size="sm" icon="pencil-square" />
                                    <flux:button wire:click="deleteProduct({{ $product->id }})" variant="ghost" size="sm" icon="trash" class="text-red-500 hover:text-red-700!" />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8">
                                <div class="flex flex-col items-center justify-center py-12 text-center">
                                    <flux:heading class="text-zinc-500 dark:text-zinc-400">{{ __('No products or services yet') }}</flux:heading>
                                    <flux:subheading class="mt-1">{{ __('Add your first product or service.') }}</flux:subheading>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    @endif

    {{-- Fabric Modal --}}
    <flux:modal wire:model="showFabricModal" class="max-w-2xl">
        <form wire:submit="saveFabric" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $fabricEditingId ? __('Edit Fabric Roll') : __('Add Fabric Roll') }}</flux:heading>
                <flux:subheading>{{ $fabricEditingId ? __('Update the fabric roll details.') : __('Register a new fabric roll in your inventory.') }}</flux:subheading>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>{{ __('Roll Code') }} <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="fabric_roll_code" type="text" required placeholder="e.g. FAB-001" />
                    <flux:error name="fabric_roll_code" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Fabric Name') }} <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="fabric_name" type="text" required placeholder="e.g. Cotton Satin" />
                    <flux:error name="fabric_name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Color') }}</flux:label>
                    <flux:input wire:model="fabric_color" type="text" placeholder="e.g. Navy Blue" />
                    <flux:error name="fabric_color" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Supplier') }}</flux:label>
                    <flux:input wire:model="fabric_supplier" type="text" placeholder="e.g. Textile Co." />
                    <flux:error name="fabric_supplier" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Date Received') }}</flux:label>
                    <flux:input wire:model="fabric_date_received" type="date" />
                    <flux:error name="fabric_date_received" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Fabric Width') }}</flux:label>
                    <flux:input wire:model="fabric_width" type="text" placeholder="e.g. 150cm" />
                    <flux:error name="fabric_width" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Claimed Meters') }}</flux:label>
                    <flux:input wire:model="fabric_claimed_meters" type="number" step="0.01" min="0" placeholder="e.g. 80" />
                    <flux:error name="fabric_claimed_meters" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Verified Meters') }}</flux:label>
                    <flux:input wire:model="fabric_verified_meters" type="number" step="0.01" min="0" placeholder="e.g. 78.5" />
                    <flux:error name="fabric_verified_meters" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Used Meters') }}</flux:label>
                    <flux:input wire:model="fabric_used_meters" type="number" step="0.01" min="0" placeholder="e.g. 10" />
                    <flux:error name="fabric_used_meters" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Buying Price') }}</flux:label>
                    <flux:input wire:model="fabric_buying_price" type="number" step="0.01" min="0" placeholder="e.g. 500" />
                    <flux:error name="fabric_buying_price" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Selling Price per Meter') }}</flux:label>
                    <flux:input wire:model="fabric_selling_price_per_meter" type="number" step="0.01" min="0" placeholder="e.g. 12.50" />
                    <flux:error name="fabric_selling_price_per_meter" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>{{ __('Image') }}</flux:label>
                @if ($fabricEditingId && !$fabric_image && !$fabric_remove_image)
                    @php $ef = Fabric::find($fabricEditingId); @endphp
                    @if ($ef && $ef->image)
                        <div class="mb-3 flex items-center gap-3">
                            <img src="{{ Storage::url($ef->image) }}" class="size-16 rounded-lg object-cover">
                            <flux:button type="button" variant="ghost" size="sm" wire:click="$set('fabric_remove_image', true)">{{ __('Remove image') }}</flux:button>
                        </div>
                    @endif
                @endif
                @if ($fabric_image)
                    <div class="mb-3 flex items-center gap-3">
                        <img src="{{ $fabric_image->temporaryUrl() }}" class="size-16 rounded-lg object-cover">
                        <flux:button type="button" variant="ghost" size="sm" wire:click="$set('fabric_image', null)">{{ __('Remove') }}</flux:button>
                    </div>
                @endif
                <label class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border-2 border-dashed border-neutral-300 px-4 py-3 text-sm text-neutral-500 transition-colors hover:border-neutral-400 hover:bg-neutral-50 dark:border-neutral-600 dark:hover:border-neutral-500 dark:hover:bg-neutral-800/50">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                    <span>{{ __('Upload image') }}</span>
                    <input type="file" accept="image/*" wire:model="fabric_image" class="hidden">
                </label>
                <flux:error name="fabric_image" />
            </flux:field>

            <div class="flex justify-end gap-2 border-t border-neutral-200 pt-6 dark:border-neutral-700">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ $fabricEditingId ? __('Update') : __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Product / Service Modal --}}
    <flux:modal wire:model="showProductModal" class="max-w-lg">
        <form wire:submit="saveProduct" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $productEditingId ? __('Edit Product / Service') : __('Add Product / Service') }}</flux:heading>
                <flux:subheading>{{ $productEditingId ? __('Update the item details.') : __('Add a new product or service to your inventory.') }}</flux:subheading>
            </div>

            <flux:field>
                <flux:label>{{ __('Type') }} <span class="text-red-500">*</span></flux:label>
                <flux:radio.group wire:model="ps_type" variant="segmented" class="w-full">
                    <flux:radio value="product" label="Product" />
                    <flux:radio value="service" label="Service" />
                </flux:radio.group>
                <flux:error name="ps_type" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>{{ __('Name') }} <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="ps_name" type="text" required :placeholder="$ps_type === 'product' ? __('e.g. Zipper #5') : __('e.g. Alteration Service')" />
                    <flux:error name="ps_name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('SKU') }}</flux:label>
                    <flux:input wire:model="ps_sku" type="text" placeholder="e.g. ZIP-005" />
                    <flux:error name="ps_sku" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>{{ __('Description') }}</flux:label>
                <flux:textarea wire:model="ps_description" rows="2" :placeholder="$ps_type === 'product' ? __('Describe the product...') : __('Describe the service...')" />
                <flux:error name="ps_description" />
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>{{ __('Buying Price') }}</flux:label>
                    <flux:input wire:model="ps_buying_price" type="number" step="0.01" min="0" placeholder="e.g. 300" />
                    <flux:error name="ps_buying_price" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Selling Price') }}</flux:label>
                    <flux:input wire:model="ps_selling_price" type="number" step="0.01" min="0" placeholder="e.g. 500" />
                    <flux:error name="ps_selling_price" />
                </flux:field>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>{{ __('Unit') }}</flux:label>
                    <flux:input wire:model="ps_unit" type="text" :placeholder="$ps_type === 'product' ? __('e.g. piece, meter') : __('e.g. hour, session')" />
                    <flux:error name="ps_unit" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>{{ __('Image') }}</flux:label>
                @if ($productEditingId && !$ps_image && !$ps_remove_image)
                    @php $ep = ProductService::find($productEditingId); @endphp
                    @if ($ep && $ep->image)
                        <div class="mb-3 flex items-center gap-3">
                            <img src="{{ Storage::url($ep->image) }}" class="size-16 rounded-lg object-cover">
                            <flux:button type="button" variant="ghost" size="sm" wire:click="$set('ps_remove_image', true)">{{ __('Remove image') }}</flux:button>
                        </div>
                    @endif
                @endif
                @if ($ps_image)
                    <div class="mb-3 flex items-center gap-3">
                        <img src="{{ $ps_image->temporaryUrl() }}" class="size-16 rounded-lg object-cover">
                        <flux:button type="button" variant="ghost" size="sm" wire:click="$set('ps_image', null)">{{ __('Remove') }}</flux:button>
                    </div>
                @endif
                <label class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border-2 border-dashed border-neutral-300 px-4 py-3 text-sm text-neutral-500 transition-colors hover:border-neutral-400 hover:bg-neutral-50 dark:border-neutral-600 dark:hover:border-neutral-500 dark:hover:bg-neutral-800/50">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                    <span>{{ __('Upload image') }}</span>
                    <input type="file" accept="image/*" wire:model="ps_image" class="hidden">
                </label>
                <flux:error name="ps_image" />
            </flux:field>

            <div class="flex justify-end gap-2 border-t border-neutral-200 pt-6 dark:border-neutral-700">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ $productEditingId ? __('Update') : __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Office Rental Modal --}}
    <flux:modal wire:model="showOfficeRentModal" class="max-w-lg">
        <form wire:submit="saveOfficeRent" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $officeRentEditingId ? __('Edit Office Rental') : __('Add Office Rental') }}</flux:heading>
                <flux:subheading>{{ $officeRentEditingId ? __('Update the office rental details.') : __('Add a new office rental space.') }}</flux:subheading>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>{{ __('Name') }} <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="or_name" type="text" required placeholder="e.g. Downtown Suite 101" />
                    <flux:error name="or_name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Location') }}</flux:label>
                    <flux:input wire:model="or_location" type="text" placeholder="e.g. Floor 3, Wing B" />
                    <flux:error name="or_location" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>{{ __('Description') }}</flux:label>
                <flux:textarea wire:model="or_description" rows="2" placeholder="{{ __('Describe the office space, size, amenities...') }}" />
                <flux:error name="or_description" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Monthly Rent (UGX)') }}</flux:label>
                <flux:input wire:model="or_monthly_rent" type="number" step="0.01" min="0" placeholder="e.g. 500000" />
                <flux:error name="or_monthly_rent" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Image') }}</flux:label>
                @if ($officeRentEditingId && !$or_image && !$or_remove_image)
                    @php $er = ProductService::find($officeRentEditingId); @endphp
                    @if ($er && $er->image)
                        <div class="mb-3 flex items-center gap-3">
                            <img src="{{ Storage::url($er->image) }}" class="size-16 rounded-lg object-cover">
                            <flux:button type="button" variant="ghost" size="sm" wire:click="$set('or_remove_image', true)">{{ __('Remove image') }}</flux:button>
                        </div>
                    @endif
                @endif
                @if ($or_image)
                    <div class="mb-3 flex items-center gap-3">
                        <img src="{{ $or_image->temporaryUrl() }}" class="size-16 rounded-lg object-cover">
                        <flux:button type="button" variant="ghost" size="sm" wire:click="$set('or_image', null)">{{ __('Remove') }}</flux:button>
                    </div>
                @endif
                <label class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border-2 border-dashed border-neutral-300 px-4 py-3 text-sm text-neutral-500 transition-colors hover:border-neutral-400 hover:bg-neutral-50 dark:border-neutral-600 dark:hover:border-neutral-500 dark:hover:bg-neutral-800/50">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                    <span>{{ __('Upload image') }}</span>
                    <input type="file" accept="image/*" wire:model="or_image" class="hidden">
                </label>
                <flux:error name="or_image" />
            </flux:field>

            <div class="flex justify-end gap-2 border-t border-neutral-200 pt-6 dark:border-neutral-700">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ $officeRentEditingId ? __('Update') : __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
