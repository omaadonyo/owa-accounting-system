<?php

use App\Models\Plan;
use App\Models\Subscription;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::auth.standalone')] #[Title('Set up your business')] class extends Component {
    use WithFileUploads;

    public int $step = 1;

    public string $businessName = '';

    public string $businessEmail = '';

    public string $address = '';

    public $logo = null;

    public string $invoiceNotes = '';

    public string $quotesNotes = '';

    public string $receiptNotes = '';

    public function mount(): void
    {
        if (auth()->user()->business) {
            $this->redirect(route('dashboard', absolute: false), navigate: true);
        }
    }

    public function nextStep(): void
    {
        $this->validateStep();

        $this->step++;
    }

    public function previousStep(): void
    {
        $this->step--;
    }

    public function save(): void
    {
        $this->validateStep();

        $logoPath = null;

        if ($this->logo) {
            $logoPath = $this->logo->store('logos', 'public');
        }

        $business = auth()->user()->ownedBusiness()->create([
            'name' => $this->businessName,
            'email' => $this->businessEmail ?: null,
            'address' => $this->address ?: null,
            'logo' => $logoPath,
            'invoice_notes' => $this->invoiceNotes ?: null,
            'quotes_notes' => $this->quotesNotes ?: null,
            'receipt_notes' => $this->receiptNotes ?: null,
        ]);

        auth()->user()->update(['business_id' => $business->id]);

        $freePlan = Plan::where('slug', 'free')->first();
        if ($freePlan) {
            Subscription::create([
                'business_id' => $business->id,
                'plan_id' => $freePlan->id,
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'amount' => 0,
                'starts_at' => now(),
            ]);
        }

        Flux::toast(variant: 'success', text: __('Business set up successfully!'));

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    public function removeLogo(): void
    {
        $this->logo = null;
    }

    private function validateStep(): void
    {
        match ($this->step) {
            1 => $this->validate([
                'businessName' => ['required', 'string', 'max:255'],
                'businessEmail' => ['nullable', 'email', 'max:255'],
            ]),
            3 => $this->validate([
                'logo' => ['nullable', 'image', 'max:2048'],
            ]),
            default => null,
        };
    }
}; ?>

<div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-white p-6 dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900 md:p-10">
    <div class="flex w-full max-w-lg flex-col gap-2">
        <div class="flex flex-col gap-6">
            <div class="w-full rounded-xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                <div class="p-6 sm:p-8">
                    <div class="mb-8">
                        <div class="flex items-center justify-between">
                            @php
                                $steps = [
                                    1 => ['label' => __('Info'), 'icon' => 'building'],
                                    2 => ['label' => __('Address'), 'icon' => 'map-pin'],
                                    3 => ['label' => __('Logo'), 'icon' => 'image'],
                                    4 => ['label' => __('Notes'), 'icon' => 'file-text'],
                                ];
                            @endphp

                            <div class="flex w-full items-center">
                                @foreach ($steps as $num => $data)
                                    <div class="flex flex-1 flex-col items-center {{ $loop->first ? '' : '' }}">
                                        <div class="flex items-center w-full">
                                            @if (!$loop->first)
                                                <div class="h-px flex-1 {{ $num <= $step ? 'bg-neutral-900 dark:bg-white' : 'bg-neutral-200 dark:bg-neutral-700' }}"></div>
                                            @endif

                                            <div class="flex flex-col items-center gap-1.5">
                                                <div class="flex size-8 items-center justify-center rounded-full text-xs font-semibold {{ $num < $step ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900' : ($num === $step ? 'border-2 border-neutral-900 bg-white text-neutral-900 dark:border-white dark:bg-neutral-900 dark:text-white' : 'border-2 border-neutral-200 bg-white text-neutral-400 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-500') }}">
                                                    @if ($num < $step)
                                                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                    @else
                                                        {{ $num }}
                                                    @endif
                                                </div>
                                                <span class="hidden text-xs font-medium sm:inline {{ $num <= $step ? 'text-neutral-900 dark:text-white' : 'text-neutral-400 dark:text-neutral-500' }}">{{ $data['label'] }}</span>
                                            </div>

                                            @if (!$loop->last)
                                                <div class="h-px flex-1 {{ ($num + 1) <= $step ? 'bg-neutral-900 dark:bg-white' : 'bg-neutral-200 dark:bg-neutral-700' }}"></div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mx-auto mt-4 h-1.5 w-full max-w-xs overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                            <div class="h-full rounded-full bg-neutral-900 transition-all duration-500 ease-out dark:bg-white" style="width: {{ (($step - 1) / 3) * 100 }}%"></div>
                        </div>
                    </div>

                    <form wire:submit="{{ $step < 4 ? 'nextStep' : 'save' }}">
                        <div class="min-h-[280px]">
                            @if ($step === 1)
                                <div class="space-y-6" wire:key="step-1">
                                    <div class="text-center">
                                        <flux:heading size="xl">{{ __("What's your business called?") }}</flux:heading>
                                        <flux:subheading class="mt-1">{{ __('This is how your clients will see you on invoices, quotes, and receipts.') }}</flux:subheading>
                                    </div>

                                    <flux:field>
                                        <flux:label>{{ __('Business name') }}</flux:label>
                                        <flux:input wire:model="businessName" type="text" required autofocus placeholder="e.g. Acme Inc." />
                                        <flux:error name="businessName" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('Business email (optional)') }}</flux:label>
                                        <flux:input wire:model="businessEmail" type="email" placeholder="hello@acme.com" />
                                        <flux:error name="businessEmail" />
                                    </flux:field>
                                </div>
                            @elseif ($step === 2)
                                <div class="space-y-6" wire:key="step-2">
                                    <div class="text-center">
                                        <flux:heading size="xl">{{ __("Where are you based?") }}</flux:heading>
                                        <flux:subheading class="mt-1">{{ __('Your business address will appear on all professional documents.') }}</flux:subheading>
                                    </div>

                                    <flux:field>
                                        <flux:label>{{ __('Address') }}</flux:label>
                                        <flux:textarea wire:model="address" rows="4" placeholder="123 Main Street&#10;Suite 100&#10;New York, NY 10001" />
                                        <flux:error name="address" />
                                    </flux:field>
                                </div>
                            @elseif ($step === 3)
                                <div class="space-y-6" wire:key="step-3">
                                    <div class="text-center">
                                        <flux:heading size="xl">{{ __('Add your logo') }}</flux:heading>
                                        <flux:subheading class="mt-1">{{ __('Upload your brand logo to appear on invoices and quotes.') }}</flux:subheading>
                                    </div>

                                    <flux:field>
                                        @if ($logo)
                                            <div class="flex flex-col items-center gap-4">
                                                <div class="relative size-32 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                                                    <img src="{{ $logo->temporaryUrl() }}" alt="Logo preview" class="size-full object-contain">
                                                </div>
                                                <div class="flex gap-2">
                                                    <flux:button type="button" variant="ghost" size="sm" wire:click="removeLogo">{{ __('Remove') }}</flux:button>
                                                    <flux:button type="button" variant="primary" size="sm" wire:click="$set('logo', null)" onclick="event.preventDefault(); document.getElementById('logo-upload').click()">{{ __('Change') }}</flux:button>
                                                </div>
                                                <input id="logo-upload" type="file" accept="image/*" wire:model="logo" class="hidden">
                                            </div>
                                        @else
                                            <label class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed border-neutral-300 bg-neutral-50 px-6 py-10 transition-colors hover:border-neutral-400 hover:bg-neutral-100 dark:border-neutral-600 dark:bg-neutral-800/50 dark:hover:border-neutral-500 dark:hover:bg-neutral-800">
                                                <div class="flex size-12 items-center justify-center rounded-full bg-neutral-200 dark:bg-neutral-700">
                                                    <svg class="size-6 text-neutral-500 dark:text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                                </div>
                                                <div class="text-center">
                                                    <flux:text class="font-medium">{{ __('Click to upload') }}</flux:text>
                                                    <flux:text class="text-sm">{{ __('PNG, JPG or SVG — up to 2MB') }}</flux:text>
                                                </div>
                                                <input id="logo-upload" type="file" accept="image/*" wire:model="logo" class="hidden">
                                            </label>
                                        @endif
                                        <flux:error name="logo" />
                                    </flux:field>
                                </div>
                            @elseif ($step === 4)
                                <div class="space-y-6" wire:key="step-4">
                                    <div class="text-center">
                                        <flux:heading size="xl">{{ __('Set default notes') }}</flux:heading>
                                        <flux:subheading class="mt-1">{{ __('These will appear on every document — you can edit them later.') }}</flux:subheading>
                                    </div>

                                    <flux:field>
                                        <flux:label>{{ __('Invoice notes') }}</flux:label>
                                        <flux:textarea wire:model="invoiceNotes" rows="3" placeholder="Thank you for your business! Payment is due within 30 days." />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('Quotes notes') }}</flux:label>
                                        <flux:textarea wire:model="quotesNotes" rows="3" placeholder="This quote is valid for 14 days." />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>{{ __('Receipt notes') }}</flux:label>
                                        <flux:textarea wire:model="receiptNotes" rows="3" placeholder="This receipt confirms your payment." />
                                    </flux:field>
                                </div>
                            @endif
                        </div>

                        @if ($errors->any())
                            <div class="mt-4">
                                <flux:callout variant="danger" icon="exclamation-triangle" class="text-sm">
                                    @foreach ($errors->all() as $error)
                                        <p>{{ $error }}</p>
                                    @endforeach
                                </flux:callout>
                            </div>
                        @endif

                        <div class="mt-8 flex items-center justify-between border-t border-neutral-200 pt-6 dark:border-neutral-700">
                            @if ($step > 1)
                                <flux:button type="button" variant="ghost" wire:click="previousStep">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                                        {{ __('Back') }}
                                    </div>
                                </flux:button>
                            @else
                                <div></div>
                            @endif

                            <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                {{ __('Step :current of :total', ['current' => $step, 'total' => 4]) }}
                            </div>

                            <flux:button type="submit" variant="primary">
                                @if ($step < 4)
                                    <div class="flex items-center gap-1.5">
                                        {{ __('Continue') }}
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </div>
                                @else
                                    {{ __('Complete setup') }}
                                @endif
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
