<?php

use App\Models\Customer;
use Barryvdh\DomPDF\Facade\Pdf;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Customers')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $sortField = 'name';

    #[Url]
    public string $sortDirection = 'asc';

    public bool $showCustomerModal = false;

    public ?int $editingCustomerId = null;

    public string $name = '';

    public string $email = '';

    public string $address = '';

    public function mount(): void
    {
        if (! auth()->user()->business) {
            $this->redirect(route('onboarding', absolute: false), navigate: true);
        }
    }

    public function customers()
    {
        return Customer::where('business_id', auth()->user()->business->id)
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
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

    public function edit(Customer $customer): void
    {
        $this->editingCustomerId = $customer->id;
        $this->name = $customer->name;
        $this->email = $customer->email ?? '';
        $this->address = $customer->address ?? '';

        $this->showCustomerModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($this->editingCustomerId) {
            $customer = Customer::where('business_id', auth()->user()->business->id)
                ->findOrFail($this->editingCustomerId);

            $customer->update([
                'name' => $this->name,
                'email' => $this->email ?: null,
                'address' => $this->address ?: null,
            ]);

            Flux::toast(variant: 'success', text: __('Customer updated.'));
        } else {
            auth()->user()->business->customers()->create([
                'name' => $this->name,
                'email' => $this->email ?: null,
                'address' => $this->address ?: null,
            ]);

            Flux::toast(variant: 'success', text: __('Customer added.'));
        }

        $this->resetForm();
        $this->showCustomerModal = false;
    }

    public function delete(Customer $customer): void
    {
        $customer->delete();

        Flux::toast(variant: 'success', text: __('Customer deleted.'));
    }

    public function exportPdf()
    {
        $business = auth()->user()->business;

        $customers = Customer::where('business_id', $business->id)
            ->orderBy('name')
            ->get();

        $pdf = Pdf::loadView('pdf.customers', [
            'customers' => $customers,
            'business' => $business,
        ]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            __('customers') . '.pdf'
        );
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'email', 'address', 'editingCustomerId']);
    }
}; ?>

<div class="mx-auto" style="width: 80%;">
    <flux:heading size="xl">{{ __('Customers') }}</flux:heading>
    <flux:subheading class="mt-1">{{ __('Manage your customer directory.') }}</flux:subheading>

    <div class="mt-6 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search customers...')" icon="magnifying-glass" clearable class="w-72" />

            <flux:button wire:click="exportPdf" variant="ghost">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                {{ __('PDF') }}
            </flux:button>
        </div>

        <flux:button variant="primary" wire:click="$set('showCustomerModal', true)">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                {{ __('Add Customer') }}
            </flux:button>
    </div>

    <div class="mt-4">
        <flux:table :paginate="$this->customers()">
            <flux:table.columns>
                <flux:table.column :sortable="true" :sorted="$sortField === 'name'" :direction="$sortField === 'name' ? $sortDirection : null" wire:click="sortBy('name')">{{ __('Name') }}</flux:table.column>
                <flux:table.column :sortable="true" :sorted="$sortField === 'email'" :direction="$sortField === 'email' ? $sortDirection : null" wire:click="sortBy('email')">{{ __('Email') }}</flux:table.column>
                <flux:table.column>{{ __('Address') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->customers() as $customer)
                    <flux:table.row :key="$customer->id">
                        <flux:table.cell class="font-medium">{{ $customer->name }}</flux:table.cell>
                        <flux:table.cell>{{ $customer->email ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="max-w-[200px] truncate">{{ $customer->address ?? '—' }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button wire:click="edit({{ $customer->id }})" variant="ghost" size="sm" icon="pencil-square" class="text-sky-600! hover:text-sky-800! dark:text-sky-400! dark:hover:text-sky-300!" />
                                <flux:button wire:click="delete({{ $customer->id }})" variant="ghost" size="sm" icon="trash" class="text-red-500! hover:text-red-700!" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <flux:heading class="text-zinc-500 dark:text-zinc-400">{{ __('No customers yet') }}</flux:heading>
                                <flux:subheading class="mt-1">{{ __('Add your first customer to get started.') }}</flux:subheading>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showCustomerModal" class="max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingCustomerId ? __('Edit Customer') : __('Add Customer') }}</flux:heading>
                <flux:subheading>{{ $editingCustomerId ? __('Update the customer details.') : __('Add a new customer to your directory.') }}</flux:subheading>
            </div>

            <flux:field>
                <flux:label>{{ __('Name') }}</flux:label>
                <flux:input wire:model="name" type="text" required autofocus placeholder="e.g. Jane Doe" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Email (optional)') }}</flux:label>
                <flux:input wire:model="email" type="email" placeholder="jane@example.com" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Address (optional)') }}</flux:label>
                <flux:textarea wire:model="address" rows="3" placeholder="123 Main Street, New York, NY" />
                <flux:error name="address" />
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ $editingCustomerId ? __('Update') : __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
