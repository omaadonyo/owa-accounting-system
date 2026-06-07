<?php

use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Backup')] class extends Component {

    public string $mailTo = 'owacomputer@gmail.com';

    public ?string $lastBackup = null;

    public ?string $lastBackupSize = null;

    public array $history = [];

    public function mount(): void
    {
        $this->mailTo = config('backup.mail_to', 'owacomputer@gmail.com');
        $this->refreshHistory();
    }

    public function refreshHistory(): void
    {
        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            $this->history = [];
            $this->lastBackup = null;
            $this->lastBackupSize = null;
            return;
        }

        $files = collect(scandir($dir))
            ->filter(fn ($f) => str_ends_with($f, '.sql'))
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

    public function runBackup(): void
    {
        $exitCode = Artisan::call('backup:database', ['--mail-to' => $this->mailTo]);

        if ($exitCode === 0) {
            $this->refreshHistory();
            Flux::toast(variant: 'success', text: __('Backup completed and emailed.'));
        } else {
            Flux::toast(variant: 'danger', text: Artisan::output());
        }
    }

    public function saveSettings(): void
    {
        $this->validate(['mailTo' => 'required|email']);
        Flux::toast(variant: 'success', text: __('Settings saved.'));
    }
}; ?>

<div class="mx-auto" style="width: 80%;">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Backup') }}</flux:heading>
        <flux:subheading class="mt-1">{{ __('Manage automatic database backups.') }}</flux:subheading>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Status Card --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
            <flux:heading size="sm">{{ __('Backup Status') }}</flux:heading>
            <div class="mt-4 space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-neutral-500">{{ __('Last Backup') }}</span>
                    <span class="font-medium">{{ $lastBackup ?? __('Never') }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-neutral-500">{{ __('Size') }}</span>
                    <span class="font-medium">{{ $lastBackupSize ?? '—' }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-neutral-500">{{ __('Schedule') }}</span>
                    <span class="font-medium">{{ __('Daily at midnight') }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-neutral-500">{{ __('Recipient') }}</span>
                    <span class="font-medium">{{ $mailTo }}</span>
                </div>
            </div>

            <flux:button wire:click="runBackup" variant="primary" icon="arrow-path" class="mt-4 w-full cursor-pointer">
                {{ __('Run Backup Now') }}
            </flux:button>
        </div>

        {{-- Settings Card --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
            <flux:heading size="sm">{{ __('Email Settings') }}</flux:heading>
            <p class="mt-1 text-xs text-neutral-500">{{ __('The SQL dump will be sent to this address after each backup.') }}</p>

            <div class="mt-4 space-y-4">
                <flux:field>
                    <flux:label>{{ __('Send to') }}</flux:label>
                    <flux:input wire:model="mailTo" type="email" placeholder="admin@example.com" />
                    <flux:error name="mailTo" />
                </flux:field>

                <flux:button wire:click="saveSettings" variant="primary" class="cursor-pointer">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Backup History --}}
    <div class="mt-6 rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-[oklch(0.21_0.02_320.19)]">
        <flux:heading size="sm">{{ __('Backup History') }}</flux:heading>

        <div class="mt-4">
            @if (empty($history))
                <p class="py-6 text-center text-sm text-neutral-500">{{ __('No backups recorded yet.') }}</p>
            @else
                <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    @foreach ($history as $entry)
                        <div class="flex items-center justify-between py-2 text-sm">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ $entry['name'] }}</p>
                                <p class="text-xs text-neutral-500">{{ date('Y-m-d H:i:s', $entry['date']) }}</p>
                            </div>
                            <div class="flex items-center gap-3 ps-3">
                                <span class="shrink-0 text-xs text-neutral-400">{{ round($entry['size'] / 1024, 1) }} KB</span>
                                <a href="{{ route('backups.download', ['filename' => $entry['name']]) }}"
                                   class="inline-flex size-7 items-center justify-center rounded-md text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700 dark:hover:bg-neutral-800 dark:hover:text-neutral-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
