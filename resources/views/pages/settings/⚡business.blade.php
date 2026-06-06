<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new #[Title('Business settings')] class extends Component {
    use WithFileUploads;

    public string $businessName = '';

    public string $businessEmail = '';

    public string $address = '';

    public $logo = null;

    public ?string $existingLogo = null;

    public string $invoiceNotes = '';

    public string $quotesNotes = '';

    public string $receiptNotes = '';

    public function mount(): void
    {
        $business = Auth::user()->business;

        if (! $business) {
            $this->redirect(route('onboarding', absolute: false), navigate: true);
            return;
        }

        $this->businessName = $business->name;
        $this->businessEmail = $business->email ?? '';
        $this->address = $business->address ?? '';
        $this->existingLogo = $business->logo;
        $this->invoiceNotes = $business->invoice_notes ?? '';
        $this->quotesNotes = $business->quotes_notes ?? '';
        $this->receiptNotes = $business->receipt_notes ?? '';
    }

    public function update(): void
    {
        $this->validate([
            'businessName' => ['required', 'string', 'max:255'],
            'businessEmail' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'invoiceNotes' => ['nullable', 'string', 'max:1000'],
            'quotesNotes' => ['nullable', 'string', 'max:1000'],
            'receiptNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $business = Auth::user()->business;

        $logoPath = $business->logo;

        if ($this->logo) {
            if ($business->logo) {
                Storage::disk('public')->delete($business->logo);
            }
            $logoPath = $this->logo->store('logos', 'public');
        }

        $business->update([
            'name' => $this->businessName,
            'email' => $this->businessEmail ?: null,
            'address' => $this->address ?: null,
            'logo' => $logoPath,
            'invoice_notes' => $this->invoiceNotes ?: null,
            'quotes_notes' => $this->quotesNotes ?: null,
            'receipt_notes' => $this->receiptNotes ?: null,
        ]);

        Flux::toast(variant: 'success', text: __('Business settings updated.'));
    }

    public function removeLogo(): void
    {
        $business = Auth::user()->business;

        if ($business->logo) {
            Storage::disk('public')->delete($business->logo);
        }

        $business->update(['logo' => null]);
        $this->existingLogo = null;
        $this->logo = null;

        Flux::toast(variant: 'success', text: __('Logo removed.'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Business settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Business')" :subheading="__('Update your business information and default document notes')">
        <form wire:submit="update" class="my-6 w-full space-y-6">
            <flux:input wire:model="businessName" :label="__('Business name')" type="text" required autofocus />

            <flux:input wire:model="businessEmail" :label="__('Business email')" type="email" />

            <flux:field>
                <flux:label>{{ __('Address') }}</flux:label>
                <flux:textarea wire:model="address" rows="3" />
                <flux:error name="address" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Logo') }}</flux:label>

                @if ($logo || $existingLogo)
                    <div class="flex items-center gap-4">
                        <div class="relative size-20 overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-700">
                            @if ($logo)
                                <img src="{{ $logo->temporaryUrl() }}" alt="Logo" class="size-full object-contain">
                            @else
                                <img src="{{ Storage::url($existingLogo) }}" alt="Logo" class="size-full object-contain">
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <flux:button type="button" variant="ghost" size="sm" wire:click="removeLogo">{{ __('Remove') }}</flux:button>
                            <flux:button type="button" variant="primary" size="sm" onclick="event.preventDefault(); document.getElementById('business-logo-upload').click()">{{ __('Change') }}</flux:button>
                        </div>
                    </div>
                    <input id="business-logo-upload" type="file" accept="image/*" wire:model="logo" class="hidden">
                @else
                    <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-neutral-300 bg-neutral-50 px-4 py-6 transition-colors hover:border-neutral-400 hover:bg-neutral-100 dark:border-neutral-600 dark:bg-neutral-800/50 dark:hover:border-neutral-500 dark:hover:bg-neutral-800">
                        <div class="flex size-10 items-center justify-center rounded-full bg-neutral-200 dark:bg-neutral-700">
                            <svg class="size-5 text-neutral-500 dark:text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                        </div>
                        <flux:text class="text-sm font-medium">{{ __('Click to upload logo') }}</flux:text>
                        <input id="business-logo-upload" type="file" accept="image/*" wire:model="logo" class="hidden">
                    </label>
                @endif
                <flux:error name="logo" />
            </flux:field>

            <flux:separator />

            <flux:heading size="lg">{{ __('Default document notes') }}</flux:heading>
            <flux:subheading>{{ __('These will appear on every new document you create.') }}</flux:subheading>

            <flux:field>
                <flux:label>{{ __('Invoice notes') }}</flux:label>
                <flux:textarea wire:model="invoiceNotes" rows="3" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Quotes notes') }}</flux:label>
                <flux:textarea wire:model="quotesNotes" rows="3" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Receipt notes') }}</flux:label>
                <flux:textarea wire:model="receiptNotes" rows="3" />
            </flux:field>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </div>
        </form>
    </x-pages::settings.layout>
</section>
