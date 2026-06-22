<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Users')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    public bool $showUserModal = false;

    public ?int $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'employee';

    public $viewingUser = null;

    public bool $showViewUserModal = false;

    public function viewUser(User $user): void
    {
        $this->viewingUser = $user;
        $this->showViewUserModal = true;
    }

    public function mount(): void
    {
        if (! currentBusiness() && ! auth()->user()->isSuperadmin()) {
            $this->redirect(route('onboarding', absolute: false), navigate: true);
        }
    }

    public function users()
    {
        return User::where('business_id', currentBusiness()->id)
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            }))
            ->latest()
            ->paginate(10);
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showUserModal = true;
    }

    public function edit(User $user): void
    {
        $this->resetForm();
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->showUserModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->editingUserId)],
            'password' => $this->editingUserId ? 'nullable|min:8' : 'required|min:8',
            'role' => 'required|in:admin,employee',
        ]);

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
            ];
            if ($this->password) {
                $data['password'] = bcrypt($this->password);
            }
            $user->update($data);
            Flux::toast(variant: 'success', text: __('User updated.'));
        } else {
            User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => bcrypt($this->password),
                'role' => $this->role,
                'business_id' => currentBusiness()->id,
            ]);
            Flux::toast(variant: 'success', text: __('User created.'));
        }

        $this->showUserModal = false;
        $this->resetForm();
    }

    public function delete(User $user): void
    {
        if ($user->id === auth()->id()) {
            Flux::toast(variant: 'danger', text: __('You cannot delete yourself.'));
            return;
        }
        $user->delete();
        Flux::toast(variant: 'success', text: __('User deleted.'));
    }

    private function resetForm(): void
    {
        $this->editingUserId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = 'employee';
    }
}; ?>

<div style="width: 80%; margin: 0 auto;">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Users') }}</flux:heading>
        <div class="flex items-center gap-3">
            <flux:input wire:model.live="search" placeholder="{{ __('Search users...') }}" class="w-64" />
            <flux:button wire:click="create" variant="primary" icon="plus">
                {{ __('Add User') }}
            </flux:button>
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Email') }}</flux:table.column>
            <flux:table.column>{{ __('Role') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->users() as $user)
                <flux:table.row>
                    <flux:table.cell class="font-medium">{{ $user->name }}</flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge variant="pill" size="sm" :color="$user->role === 'admin' ? 'indigo' : ($user->role === 'superadmin' ? 'red' : 'lime')" :icon="$user->role === 'superadmin' ? 'shield-check' : 'user'">
                            {{ ucfirst($user->role) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-1">
                            <flux:button variant="ghost" size="sm" icon="eye" wire:click="viewUser({{ $user->id }})" class="cursor-pointer text-indigo-600! hover:text-indigo-800! dark:text-indigo-400! dark:hover:text-indigo-300!" />
                            <flux:button variant="ghost" size="sm" icon="pencil" wire:click="edit({{ $user->id }})" class="cursor-pointer text-sky-600! hover:text-sky-800! dark:text-sky-400! dark:hover:text-sky-300!" />
                            @if ($user->id !== auth()->id())
                                <flux:button variant="ghost" size="sm" icon="trash"
                                    wire:click="delete({{ $user->id }})"
                                    wire:confirm="{{ __('Delete this user?') }}"
                                    class="cursor-pointer text-red-500! hover:text-red-700!" />
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        <div class="flex flex-col items-center py-12 text-center">
                            <flux:heading class="text-neutral-400">{{ __('No users yet') }}</flux:heading>
                            <flux:subheading class="mt-1 text-neutral-400">{{ __('Add team members to help manage your business.') }}</flux:subheading>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $this->users()->links() }}
    </div>

    <flux:modal wire:model="showViewUserModal" class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $viewingUser?->name }}</flux:heading>
                <flux:subheading>{{ __('User details') }}</flux:subheading>
            </div>
            <div class="grid gap-4">
                <div><flux:label>{{ __('Name') }}</flux:label><p class="mt-1 text-sm font-medium text-neutral-900 dark:text-white">{{ $viewingUser?->name }}</p></div>
                <div><flux:label>{{ __('Email') }}</flux:label><p class="mt-1 text-sm font-medium text-neutral-900 dark:text-white">{{ $viewingUser?->email }}</p></div>
                <div><flux:label>{{ __('Role') }}</flux:label><p class="mt-1 text-sm font-medium text-neutral-900 dark:text-white">{{ ucfirst($viewingUser?->role ?? '') }}</p></div>
                <div><flux:label>{{ __('Joined') }}</flux:label><p class="mt-1 text-sm font-medium text-neutral-900 dark:text-white">{{ $viewingUser?->created_at?->format('d M Y, H:i') }}</p></div>
            </div>
            <div class="flex justify-end"><flux:modal.close><flux:button variant="filled">{{ __('Close') }}</flux:button></flux:modal.close></div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showUserModal" class="w-full max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingUserId ? __('Edit User') : __('Add User') }}</flux:heading>
                <flux:subheading>{{ $editingUserId ? __('Update user details.') : __('Add a new team member.') }}</flux:subheading>
            </div>

            <flux:field>
                <flux:label>{{ __('Name') }}</flux:label>
                <flux:input wire:model="name" type="text" required />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Email') }}</flux:label>
                <flux:input wire:model="email" type="email" required />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Password') }}</flux:label>
                <flux:input wire:model="password" type="password" :placeholder="$editingUserId ? __('Leave blank to keep current') : ''" />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Role') }}</flux:label>
                <div class="custom-select relative">
                    <button type="button" data-cs-trigger class="flex w-full items-center justify-between rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 shadow-sm outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white dark:focus:border-accent">
                        <span wire:ignore data-cs-display>{{ __('Admin') }}</span>
                        <svg class="size-4 shrink-0 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div data-cs-dropdown class="absolute left-0 right-0 top-full z-50 mt-1 hidden overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-lg dark:border-neutral-700 dark:bg-neutral-800">
                        <div class="border-b border-neutral-200 p-2 dark:border-neutral-700">
                            <input type="text" data-cs-search placeholder="Search..." class="w-full rounded-md border border-neutral-200 bg-neutral-50 px-2.5 py-1.5 text-xs text-neutral-900 outline-none placeholder:text-neutral-400 focus:border-accent focus:ring-1 focus:ring-accent/30 dark:border-neutral-600 dark:bg-neutral-700 dark:text-white dark:placeholder:text-neutral-500">
                        </div>
                        <div data-cs-options class="max-h-48 overflow-y-auto py-1">
                            <button type="button" data-cs-option data-cs-value="admin" data-cs-label="Admin" class="cs-selected flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('Admin') }}</button>
                            <button type="button" data-cs-option data-cs-value="employee" data-cs-label="Employee" class="flex w-full items-center px-3 py-2 text-left text-sm text-neutral-700 transition hover:bg-accent/10 hover:text-accent-600 dark:text-neutral-300 dark:hover:text-accent-300">{{ __('Employee') }}</button>
                        </div>
                    </div>
                    <select wire:model="role" class="sr-only">
                        <option value="admin">{{ __('Admin') }}</option>
                        <option value="employee">{{ __('Employee') }}</option>
                    </select>
                </div>
                <flux:error name="role" />
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showUserModal', false)">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="save" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:toast on="user-saved" variant="success" message="{{ __('User saved.') }}" />
</div>
