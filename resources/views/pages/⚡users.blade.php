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

    public function mount(): void
    {
        if (! auth()->user()->business) {
            $this->redirect(route('onboarding', absolute: false), navigate: true);
        }
    }

    public function users()
    {
        return User::where('business_id', auth()->user()->business->id)
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
                'business_id' => auth()->user()->business->id,
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
                        <flux:badge variant="pill" size="sm" :color="$user->isAdmin() ? 'indigo' : 'lime'">
                            {{ ucfirst($user->role) }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex items-center justify-end gap-1">
                            <flux:button variant="ghost" size="sm" icon="pencil" wire:click="edit({{ $user->id }})" class="cursor-pointer" />
                            @if ($user->id !== auth()->id())
                                <flux:button variant="ghost" size="sm" icon="trash"
                                    wire:click="delete({{ $user->id }})"
                                    wire:confirm="{{ __('Delete this user?') }}"
                                    class="cursor-pointer" />
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
                <flux:select wire:model="role">
                    <option value="admin">{{ __('Admin') }}</option>
                    <option value="employee">{{ __('Employee') }}</option>
                </flux:select>
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
