<?php

use App\Services\BackupService;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Backup & Restore')] class extends Component {
    use WithFileUploads;

    public ?string $lastBackup = null;

    public ?string $lastBackupSize = null;

    public array $history = [];

    public bool $showRestoreModal = false;

    public ?string $restoreFile = null;

    public $uploadedBackup = null;

    public bool $showUploadModal = false;

    public ?array $uploadInfo = null;

    public function mount(): void
    {
        $this->refreshHistory();
    }

    public function refreshHistory(): void
    {
        $bizId = currentBusinessId();
        if (! $bizId) {
            $this->history = [];
            $this->lastBackup = null;
            $this->lastBackupSize = null;
            return;
        }

        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            $this->history = [];
            $this->lastBackup = null;
            $this->lastBackupSize = null;
            return;
        }

        $prefix = "backup-{$bizId}-";

        $files = collect(scandir($dir))
            ->filter(fn ($f) => str_starts_with($f, $prefix) && str_ends_with($f, '.json'))
            ->map(fn ($f) => [
                'name' => $f,
                'path' => $dir . '/' . $f,
                'size' => filesize($dir . '/' . $f),
                'date' => filemtime($dir . '/' . $f),
            ])
            ->sortByDesc('date')
            ->values();

        $this->history = $files->toArray();

        $latest = $files->first();
        if ($latest) {
            $this->lastBackup = date('Y-m-d H:i:s', $latest['date']);
            $this->lastBackupSize = round($latest['size'] / 1024 / 1024, 2) . ' MB';
        } else {
            $this->lastBackup = null;
            $this->lastBackupSize = null;
        }
    }

    public function generateBackup(): void
    {
        $bizId = currentBusinessId();
        if (! $bizId) {
            Flux::toast(variant: 'error', text: __('No active business.'));
            return;
        }

        try {
            app(BackupService::class)->export($bizId);
            $this->refreshHistory();
            Flux::toast(variant: 'success', text: __('Backup created successfully.'));
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: __('Backup failed: :error', ['error' => $e->getMessage()]));
        }
    }

    public function confirmRestore(string $filename): void
    {
        $this->restoreFile = $filename;
        $this->showRestoreModal = true;
    }

    public function restore(): void
    {
        $bizId = currentBusinessId();
        if (! $bizId || ! $this->restoreFile) {
            return;
        }

        $path = storage_path('app/backups/' . basename($this->restoreFile));
        if (! file_exists($path)) {
            Flux::toast(variant: 'danger', text: __('Backup file not found.'));
            $this->showRestoreModal = false;
            $this->restoreFile = null;
            return;
        }

        try {
            app(BackupService::class)->import($bizId, $path);
            $this->showRestoreModal = false;
            $this->restoreFile = null;
            Flux::toast(variant: 'success', text: __('Data restored successfully from :file', ['file' => basename($path)]));
            $this->redirect(route('dashboard', absolute: false), navigate: true);
        } catch (\Throwable $e) {
            $this->showRestoreModal = false;
            $this->restoreFile = null;
            Flux::toast(variant: 'danger', text: __('Restore failed: :error', ['error' => $e->getMessage()]));
        }
    }

    public function updatedUploadedBackup(): void
    {
        $this->validate([
            'uploadedBackup' => ['required', 'file', 'mimes:json', 'max:51200'],
        ]);

        $bizId = currentBusinessId();
        if (! $bizId) {
            $this->uploadedBackup = null;
            return;
        }

        $contents = $this->uploadedBackup->get();
        $payload = json_decode($contents, true);

        if (! isset($payload['data']) || ! isset($payload['business_id'])) {
            $this->uploadedBackup = null;
            Flux::toast(variant: 'error', text: __('Invalid backup file format.'));
            return;
        }

        $this->uploadInfo = [
            'business_id' => $payload['business_id'],
            'backup_date' => $payload['backup_date'] ?? __('Unknown'),
            'record_count' => collect($payload['data'])->flatten(1)->count(),
        ];

        $this->showUploadModal = true;
    }

    public function confirmUpload(): void
    {
        $bizId = currentBusinessId();
        if (! $bizId || ! $this->uploadedBackup) {
            return;
        }

        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'backup-' . $bizId . '-uploaded-' . now()->format('Y-m-d_H-i-s') . '.json';
        $path = $dir . '/' . $filename;

        $this->uploadedBackup->storeAs('backups', $filename);

        try {
            app(BackupService::class)->import($bizId, $path);
            $this->showUploadModal = false;
            $this->uploadedBackup = null;
            $this->uploadInfo = null;
            Flux::toast(variant: 'success', text: __('Data restored successfully from uploaded file.'));
            $this->redirect(route('dashboard', absolute: false), navigate: true);
        } catch (\Throwable $e) {
            $this->showUploadModal = false;
            $this->uploadedBackup = null;
            $this->uploadInfo = null;
            if (file_exists($path)) {
                unlink($path);
            }
            Flux::toast(variant: 'danger', text: __('Restore failed: :error', ['error' => $e->getMessage()]));
        }
    }

    public function deleteBackup(string $filename): void
    {
        $path = storage_path('app/backups/' . basename($filename));
        if (file_exists($path)) {
            unlink($path);
        }
        $this->refreshHistory();
        Flux::toast(variant: 'success', text: __('Backup deleted.'));
    }
}; ?>

<div class="mx-auto max-w-4xl">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Backup & Restore') }}</flux:heading>
        <flux:subheading class="mt-1">{{ __('Generate a snapshot of your business data and restore a previous version when needed.') }}</flux:subheading>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Generate Backup Card --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
            <flux:heading size="sm">{{ __('Generate Backup') }}</flux:heading>
            <div class="mt-4 space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-neutral-500">{{ __('Last Backup') }}</span>
                    <span class="font-medium">{{ $lastBackup ?? __('Never') }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-neutral-500">{{ __('Size') }}</span>
                    <span class="font-medium">{{ $lastBackupSize ?? '—' }}</span>
                </div>
            </div>
            <flux:button wire:click="generateBackup" variant="primary" icon="arrow-path" class="mt-4 w-full cursor-pointer">
                {{ __('Generate Backup') }}
            </flux:button>
        </div>

        {{-- Upload & Restore Card --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
            <flux:heading size="sm">{{ __('Upload & Restore') }}</flux:heading>
            <p class="mt-1 text-xs text-neutral-500">{{ __('Upload a previously downloaded backup JSON file to restore your data.') }}</p>

            <div class="mt-4">
                <input type="file" wire:model="uploadedBackup" accept=".json"
                       class="block w-full cursor-pointer rounded-lg border border-neutral-300 bg-white text-sm text-neutral-900 file:me-3 file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100 dark:border-neutral-600 dark:bg-neutral-800 dark:text-white dark:file:bg-indigo-500/10 dark:file:text-indigo-300 dark:hover:file:bg-indigo-500/20">
                @error('uploadedBackup')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Backup History --}}
    <div class="mt-6 rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
        <flux:heading size="sm">{{ __('Backup History') }}</flux:heading>

        <div class="mt-4">
            @if (empty($history))
                <p class="py-6 text-center text-sm text-neutral-500">{{ __('No backups recorded yet. Generate your first backup above.') }}</p>
            @else
                <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    @foreach ($history as $entry)
                        <div class="flex items-center justify-between py-2 text-sm">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ $entry['name'] }}</p>
                                <p class="text-xs text-neutral-500">{{ date('Y-m-d H:i:s', $entry['date']) }}</p>
                            </div>
                            <div class="flex items-center gap-2 ps-3">
                                <span class="shrink-0 text-xs text-neutral-400">{{ round($entry['size'] / 1024, 1) }} KB</span>
                                <a href="{{ route('backups.download', ['filename' => $entry['name']]) }}"
                                   class="inline-flex size-7 items-center justify-center rounded-md text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700 dark:hover:bg-neutral-800 dark:hover:text-neutral-300"
                                   title="{{ __('Download') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                </a>
                                <flux:button size="sm" variant="primary" wire:click="confirmRestore('{{ $entry['name'] }}')" class="cursor-pointer whitespace-nowrap">
                                    {{ __('Restore') }}
                                </flux:button>
                                <button wire:click="deleteBackup('{{ $entry['name'] }}')"
                                        class="inline-flex size-7 items-center justify-center rounded-md text-neutral-400 hover:bg-neutral-100 hover:text-red-500 dark:hover:bg-neutral-800 dark:hover:text-red-400"
                                        title="{{ __('Delete') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Restore Confirmation Modal (history) --}}
    <flux:modal wire:model="showRestoreModal" class="w-full max-w-md">
        <div class="p-1">
            <flux:heading size="lg">{{ __('Restore Backup') }}</flux:heading>
            <flux:subheading class="mt-1">
                {{ __('Are you sure you want to restore :file?', ['file' => $restoreFile ?? '']) }}
            </flux:subheading>

            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-700 dark:border-amber-800/40 dark:bg-amber-900/20 dark:text-amber-300">
                <p class="font-medium">{{ __('This will replace all current data for this business with the data from the backup.') }}</p>
                <p class="mt-1">{{ __('Customers, invoices, quotations, inventory, and all other records will be overwritten. This action cannot be undone.') }}</p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showRestoreModal', false)" class="cursor-pointer">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="restore" class="cursor-pointer">
                    {{ __('Restore') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Upload Confirmation Modal --}}
    <flux:modal wire:model="showUploadModal" class="w-full max-w-md">
        <div class="p-1">
            <flux:heading size="lg">{{ __('Restore from Upload') }}</flux:heading>
            @if ($uploadInfo)
                <flux:subheading class="mt-1">
                    {{ __('Found a backup with :count records from :date.', ['count' => $uploadInfo['record_count'], 'date' => $uploadInfo['backup_date']]) }}
                </flux:subheading>
            @endif

            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-700 dark:border-amber-800/40 dark:bg-amber-900/20 dark:text-amber-300">
                <p class="font-medium">{{ __('This will replace all current data for this business with the data from the uploaded file.') }}</p>
                <p class="mt-1">{{ __('Customers, invoices, quotations, inventory, and all other records will be overwritten. This action cannot be undone.') }}</p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <flux:button variant="ghost" wire:click="$set('showUploadModal', false)" class="cursor-pointer">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="confirmUpload" class="cursor-pointer">
                    {{ __('Restore') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
