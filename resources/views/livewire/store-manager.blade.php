<?php

use App\Models\Business;
use App\Models\Product;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Store')] class extends Component
{
    use WithFileUploads;

    public Business $business;
    public $products;

    // Store settings
    public bool $store_active = false;
    public string $store_font = 'Inter';
    public string $store_primary_color = '#4f46e5';
    public string $store_accent_color = '#f59e0b';
    public ?string $store_headline = null;
    public ?string $store_subheadline = null;
    public ?string $store_about_text = null;
    public $store_hero_image = null;
    public $store_hero_image_preview = null;
    public bool $store_show_products = true;
    public bool $store_show_about = true;
    public bool $store_show_contact = true;
    public ?string $store_contact_email = null;
    public ?string $store_contact_phone = null;
    public ?string $slug = null;

    // Product form
    public bool $showProductForm = false;
    public ?int $editingProductId = null;
    public string $productName = '';
    public ?string $productDescription = null;
    public float $productPrice = 0;
    public ?float $productComparePrice = null;
    public $productImage = null;
    public ?string $productImagePreview = null;

    public function mount(): void
    {
        $this->business = currentBusiness();
        $this->loadStore();
    }

    public function loadStore(): void
    {
        $this->store_active = $this->business->store_active;
        $this->store_font = $this->business->store_font ?? 'Inter';
        $this->store_primary_color = $this->business->store_primary_color ?? '#4f46e5';
        $this->store_accent_color = $this->business->store_accent_color ?? '#f59e0b';
        $this->store_headline = $this->business->store_headline;
        $this->store_subheadline = $this->business->store_subheadline;
        $this->store_about_text = $this->business->store_about_text;
        $this->store_hero_image_preview = $this->business->store_hero_image ? asset('storage/' . $this->business->store_hero_image) : null;
        $this->store_show_products = $this->business->store_show_products;
        $this->store_show_about = $this->business->store_show_about;
        $this->store_show_contact = $this->business->store_show_contact;
        $this->store_contact_email = $this->business->store_contact_email;
        $this->store_contact_phone = $this->business->store_contact_phone;
        $this->slug = $this->business->slug;
        $this->products = $this->business->products()->orderBy('sort_order')->get();
    }

    public function saveStore(): void
    {
        $this->validate([
            'slug' => ['required', 'alpha_dash', 'max:50', 'unique:businesses,slug,' . $this->business->id],
            'store_font' => ['required', 'string', 'max:100'],
            'store_primary_color' => ['required', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'store_accent_color' => ['required', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'store_headline' => ['nullable', 'string', 'max:255'],
            'store_subheadline' => ['nullable', 'string', 'max:500'],
            'store_about_text' => ['nullable', 'string', 'max:5000'],
            'store_hero_image' => ['nullable', 'image', 'max:2048'],
            'store_contact_email' => ['nullable', 'email', 'max:255'],
            'store_contact_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $data = [
            'slug' => $this->slug,
            'store_active' => $this->store_active,
            'store_font' => $this->store_font,
            'store_primary_color' => $this->store_primary_color,
            'store_accent_color' => $this->store_accent_color,
            'store_headline' => $this->store_headline,
            'store_subheadline' => $this->store_subheadline,
            'store_about_text' => $this->store_about_text,
            'store_show_products' => $this->store_show_products,
            'store_show_about' => $this->store_show_about,
            'store_show_contact' => $this->store_show_contact,
            'store_contact_email' => $this->store_contact_email,
            'store_contact_phone' => $this->store_contact_phone,
        ];

        if ($this->store_hero_image) {
            $data['store_hero_image'] = $this->store_hero_image->store('store-heroes', 'public');
            $this->store_hero_image_preview = asset('storage/' . $data['store_hero_image']);
            $this->store_hero_image = null;
        }

        $this->business->update($data);
        $this->business->refresh();
        $this->loadStore();

        Flux::toast(text: __('Store settings saved.'), variant: 'success');
    }

    public function addProduct(): void
    {
        $this->resetProductForm();
        $this->showProductForm = true;
        $this->editingProductId = null;
    }

    public function editProduct(int $id): void
    {
        $product = Product::where('business_id', $this->business->id)->findOrFail($id);
        $this->editingProductId = $product->id;
        $this->productName = $product->name;
        $this->productDescription = $product->description;
        $this->productPrice = (float) $product->price;
        $this->productComparePrice = $product->compare_price ? (float) $product->compare_price : null;
        $this->productImagePreview = $product->image ? asset('storage/' . $product->image) : null;
        $this->productImage = null;
        $this->showProductForm = true;
    }

    public function saveProduct(): void
    {
        $this->validate([
            'productName' => ['required', 'string', 'max:255'],
            'productDescription' => ['nullable', 'string', 'max:5000'],
            'productPrice' => ['required', 'numeric', 'min:0'],
            'productComparePrice' => ['nullable', 'numeric', 'min:0', 'gt:productPrice'],
            'productImage' => ['nullable', 'image', 'max:2048'],
        ]);

        $data = [
            'name' => $this->productName,
            'description' => $this->productDescription,
            'price' => $this->productPrice,
            'compare_price' => $this->productComparePrice,
            'updated_by' => auth()->id(),
        ];

        if ($this->productImage) {
            $data['image'] = $this->productImage->store('products', 'public');
        }

        if ($this->editingProductId) {
            $product = Product::where('business_id', $this->business->id)->findOrFail($this->editingProductId);
            $product->update($data);
        } else {
            $data['business_id'] = $this->business->id;
            $data['created_by'] = auth()->id();
            $product = Product::create($data);
        }

        $this->resetProductForm();
        $this->showProductForm = false;
        $this->products = $this->business->products()->orderBy('sort_order')->get();

        Flux::toast(text: __('Product saved.'), variant: 'success');
    }

    public function deleteProduct(int $id): void
    {
        $product = Product::where('business_id', $this->business->id)->findOrFail($id);
        $product->delete();
        $this->products = $this->business->products()->orderBy('sort_order')->get();
        Flux::toast(text: __('Product deleted.'), variant: 'success');
    }

    public function moveProductUp(int $id): void
    {
        $products = $this->business->products()->orderBy('sort_order')->get();
        foreach ($products as $i => $p) {
            if ($p->id === $id && $i > 0) {
                $temp = $products[$i - 1]->sort_order;
                $products[$i - 1]->update(['sort_order' => $p->sort_order]);
                $p->update(['sort_order' => $temp]);
                break;
            }
        }
        $this->products = $this->business->products()->orderBy('sort_order')->get();
    }

    public function moveProductDown(int $id): void
    {
        $products = $this->business->products()->orderBy('sort_order')->get();
        foreach ($products as $i => $p) {
            if ($p->id === $id && $i < count($products) - 1) {
                $temp = $products[$i + 1]->sort_order;
                $products[$i + 1]->update(['sort_order' => $p->sort_order]);
                $p->update(['sort_order' => $temp]);
                break;
            }
        }
        $this->products = $this->business->products()->orderBy('sort_order')->get();
    }

    public function toggleProduct(int $id): void
    {
        $product = Product::where('business_id', $this->business->id)->findOrFail($id);
        $product->update(['is_active' => !$product->is_active]);
        $this->products = $this->business->products()->orderBy('sort_order')->get();
    }

    private function resetProductForm(): void
    {
        $this->editingProductId = null;
        $this->productName = '';
        $this->productDescription = null;
        $this->productPrice = 0;
        $this->productComparePrice = null;
        $this->productImage = null;
        $this->productImagePreview = null;
    }
};

?>

<div class="space-y-8" x-data="{ tab: 'settings' }">
    {{-- Tab navigation --}}
    <div class="flex items-center gap-1 border-b border-neutral-200 dark:border-neutral-700">
        <button type="button" @click="tab = 'settings'" :class="tab === 'settings' ? 'border-accent text-accent' : 'border-transparent text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300'" class="cursor-pointer border-b-2 px-4 py-3 text-sm font-medium transition">{{ __('Store Settings') }}</button>
        <button type="button" @click="tab = 'products'" :class="tab === 'products' ? 'border-accent text-accent' : 'border-transparent text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300'" class="cursor-pointer border-b-2 px-4 py-3 text-sm font-medium transition">{{ __('Products') }}</button>
        <button type="button" @click="tab = 'preview'" :class="tab === 'preview' ? 'border-accent text-accent' : 'border-transparent text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300'" class="cursor-pointer border-b-2 px-4 py-3 text-sm font-medium transition">{{ __('Preview') }}</button>
    </div>

    {{-- Settings Tab --}}
    <div x-show="tab === 'settings'" class="space-y-6">
        <flux:card class="!p-6">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">{{ __('Store Settings') }}</flux:heading>
                <flux:switch wire:model="store_active" wire:change="saveStore" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>{{ __('Store Slug (subdomain)') }}</flux:label>
                    <flux:input wire:model="slug" type="text" placeholder="my-store" />
                    <p class="mt-1 text-xs text-neutral-400">{{ __('Your store URL:') }} https://<span class="font-mono font-semibold text-accent">{{ $slug ?: 'slug' }}</span>.{{ request()->getHost() }}</p>
                    <flux:error name="slug" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Font') }}</flux:label>
                    <div class="custom-select relative">
                        <button type="button" data-cs-trigger class="flex w-full items-center justify-between rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm transition focus:border-accent focus:ring-2 focus:ring-accent/20 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white">
                            <span wire:ignore data-cs-display>{{ $store_font }}</span>
                            <svg class="size-4 shrink-0 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div data-cs-dropdown class="absolute left-0 right-0 top-full z-50 mt-1 hidden overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-lg dark:border-neutral-700 dark:bg-neutral-800">
                            <div class="max-h-48 overflow-y-auto py-1">
                                @foreach(['Inter', 'Playfair Display', 'Poppins', 'DM Serif Display', 'Space Grotesk', 'Plus Jakarta Sans', 'Clash Display', 'Cabinet Grotesk', 'Satoshi', 'Instrument Sans'] as $font)
                                    <button type="button" data-cs-option data-cs-value="{{ $font }}" data-cs-label="{{ $font }}" class="flex w-full items-center px-3 py-2 text-left text-sm transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ $font }}</button>
                                @endforeach
                            </div>
                        </div>
                        <select wire:model="store_font" wire:change="saveStore" class="sr-only">
                            @foreach(['Inter', 'Playfair Display', 'Poppins', 'DM Serif Display', 'Space Grotesk', 'Plus Jakarta Sans', 'Clash Display', 'Cabinet Grotesk', 'Satoshi', 'Instrument Sans'] as $font)
                                <option value="{{ $font }}">{{ $font }}</option>
                            @endforeach
                        </select>
                    </div>
                    <flux:error name="store_font" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Primary Color') }}</flux:label>
                    <div class="flex gap-2">
                        <flux:input wire:model="store_primary_color" type="color" class="w-12 p-1!" />
                        <flux:input wire:model="store_primary_color" type="text" placeholder="#4f46e5" class="flex-1" />
                    </div>
                    <flux:error name="store_primary_color" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Accent Color') }}</flux:label>
                    <div class="flex gap-2">
                        <flux:input wire:model="store_accent_color" type="color" class="w-12 p-1!" />
                        <flux:input wire:model="store_accent_color" type="text" placeholder="#f59e0b" class="flex-1" />
                    </div>
                    <flux:error name="store_accent_color" />
                </flux:field>

                <flux:field class="col-span-2">
                    <flux:label>{{ __('Hero Image') }}</flux:label>
                    @if ($store_hero_image_preview)
                        <div class="relative mb-2 inline-block">
                            <img src="{{ $store_hero_image_preview }}" class="h-32 rounded-lg object-cover shadow-sm">
                            <button type="button" wire:click="$set('store_hero_image_preview', null)" class="absolute -right-2 -top-2 flex size-5 cursor-pointer items-center justify-center rounded-full bg-red-500 text-white text-xs">×</button>
                        </div>
                    @endif
                    <flux:input wire:model="store_hero_image" type="file" accept="image/*" />
                    <flux:error name="store_hero_image" />
                </flux:field>

                <flux:field class="col-span-2">
                    <flux:label>{{ __('Headline') }}</flux:label>
                    <flux:input wire:model="store_headline" type="text" placeholder="Elevate Your Style" />
                    <flux:error name="store_headline" />
                </flux:field>

                <flux:field class="col-span-2">
                    <flux:label>{{ __('Subheadline') }}</flux:label>
                    <flux:textarea wire:model="store_subheadline" rows="2" placeholder="Discover premium fabrics and accessories tailored for you." />
                    <flux:error name="store_subheadline" />
                </flux:field>

                <flux:field class="col-span-2">
                    <flux:label>{{ __('About Text') }}</flux:label>
                    <flux:textarea wire:model="store_about_text" rows="4" placeholder="Tell your brand story..." />
                    <flux:error name="store_about_text" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Contact Email') }}</flux:label>
                    <flux:input wire:model="store_contact_email" type="email" />
                    <flux:error name="store_contact_email" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Contact Phone') }}</flux:label>
                    <flux:input wire:model="store_contact_phone" type="text" />
                    <flux:error name="store_contact_phone" />
                </flux:field>

                <div class="col-span-2 flex items-center gap-4">
                    <flux:checkbox wire:model="store_show_products" :label="__('Show Products section')" />
                    <flux:checkbox wire:model="store_show_about" :label="__('Show About section')" />
                    <flux:checkbox wire:model="store_show_contact" :label="__('Show Contact section')" />
                </div>
            </div>

            <div class="mt-6">
                <flux:button variant="primary" wire:click="saveStore" class="cursor-pointer">{{ __('Save Settings') }}</flux:button>
            </div>
        </flux:card>
    </div>

    {{-- Products Tab --}}
    <div x-show="tab === 'products'" class="space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">{{ __('Products') }}</flux:heading>
            <flux:button variant="primary" wire:click="addProduct" class="cursor-pointer">{{ __('Add Product') }}</flux:button>
        </div>

        {{-- Product Form --}}
        @if ($showProductForm)
            <flux:card class="!p-6">
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="sm">{{ $editingProductId ? __('Edit Product') : __('New Product') }}</flux:heading>
                    <button type="button" wire:click="$set('showProductForm', false)" class="cursor-pointer text-sm text-neutral-400 hover:text-neutral-600">✕</button>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <flux:field class="col-span-2">
                        <flux:label>{{ __('Name') }}</flux:label>
                        <flux:input wire:model="productName" type="text" placeholder="Product name" />
                        <flux:error name="productName" />
                    </flux:field>
                    <flux:field class="col-span-2">
                        <flux:label>{{ __('Description') }}</flux:label>
                        <flux:textarea wire:model="productDescription" rows="3" placeholder="Describe your product..." />
                        <flux:error name="productDescription" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Price') }}</flux:label>
                        <flux:input wire:model="productPrice" type="number" step="0.01" min="0" />
                        <flux:error name="productPrice" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Compare Price (optional)') }}</flux:label>
                        <flux:input wire:model="productComparePrice" type="number" step="0.01" min="0" />
                        <flux:error name="productComparePrice" />
                    </flux:field>
                    <flux:field class="col-span-2">
                        <flux:label>{{ __('Image') }}</flux:label>
                        @if ($productImagePreview)
                            <img src="{{ $productImagePreview }}" class="mb-2 h-24 rounded-lg object-cover shadow-sm">
                        @endif
                        <flux:input wire:model="productImage" type="file" accept="image/*" />
                        <flux:error name="productImage" />
                    </flux:field>
                </div>
                <div class="mt-4 flex gap-2">
                    <flux:button variant="primary" wire:click="saveProduct" class="cursor-pointer">{{ __('Save') }}</flux:button>
                    <flux:button wire:click="$set('showProductForm', false)" class="cursor-pointer">{{ __('Cancel') }}</flux:button>
                </div>
            </flux:card>
        @endif

        {{-- Product List --}}
        <flux:card class="!p-0">
            <div class="divide-y divide-neutral-200 dark:divide-neutral-700">
                @forelse ($products as $product)
                    <div class="flex items-center gap-4 px-6 py-4 {{ !$product->is_active ? 'opacity-50' : '' }}">
                        <div class="flex flex-col gap-0.5">
                            <button type="button" wire:click="moveProductUp({{ $product->id }})" class="cursor-pointer text-xs text-neutral-400 hover:text-neutral-600">▲</button>
                            <button type="button" wire:click="moveProductDown({{ $product->id }})" class="cursor-pointer text-xs text-neutral-400 hover:text-neutral-600">▼</button>
                        </div>
                        <div class="size-12 shrink-0 overflow-hidden rounded-lg bg-neutral-100 dark:bg-neutral-800">
                            @if ($product->image)
                                <img src="{{ asset('storage/' . $product->image) }}" class="size-full object-cover">
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-sm">{{ $product->name }}</p>
                            <p class="text-xs text-neutral-500">{{ formatCurrency($product->price) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:switch wire:click="toggleProduct({{ $product->id }})" :checked="$product->is_active" />
                            <flux:button wire:click="editProduct({{ $product->id }})" variant="ghost" size="xs" icon="pencil" class="cursor-pointer" />
                            <flux:button wire:click="deleteProduct({{ $product->id }})" variant="ghost" size="xs" icon="trash" wire:confirm="{{ __('Delete this product?') }}" class="cursor-pointer text-red-500 hover:text-red-700!" />
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-12 text-center text-sm text-neutral-400">
                        {{ __('No products yet. Click "Add Product" to get started.') }}
                    </div>
                @endforelse
            </div>
        </flux:card>
    </div>

    {{-- Preview Tab --}}
    <div x-show="tab === 'preview'" class="space-y-4">
        <flux:card class="!p-6">
            <div class="flex items-center justify-between">
                <flux:heading size="sm">{{ __('Store Preview') }}</flux:heading>
                @if ($slug && $store_active)
                    <flux:button variant="primary" size="sm" onclick="window.open('https://{{ $slug }}.{{ request()->getHost() }}', '_blank')" class="cursor-pointer">
                        {{ __('Open Store') }} →
                    </flux:button>
                @endif
            </div>
            <p class="mt-2 text-sm text-neutral-500">
                @if ($store_active)
                    {{ __('Your store is live at') }} <a href="https://{{ $slug }}.{{ request()->getHost() }}" target="_blank" class="text-accent underline">https://{{ $slug }}.{{ request()->getHost() }}</a>
                @else
                    {{ __('Enable your store above to make it public.') }}
                @endif
            </p>
        </flux:card>
    </div>
</div>
