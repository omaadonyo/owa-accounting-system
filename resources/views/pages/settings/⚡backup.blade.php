<?php

use App\Console\Commands\DatabaseBackup as DatabaseBackupCommand;
use App\Mail\DatabaseBackup as DatabaseBackupMail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Backup')] class extends Component {

    public string $mailTo = 'owacomputer@gmail.com';

    public bool $running = false;

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
        $this->running = true;

        $exitCode = Artisan::call('backup:database', ['--mail-to' => $this->mailTo]);

        $this->running = false;

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
        <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
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

            <flux:button wire:click="runBackup" variant="primary" icon="arrow-path" :loading="$running" class="mt-4 w-full cursor-pointer">
                {{ __('Run Backup Now') }}
            </flux:button>
        </div>

        {{-- Settings Card --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
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
    <div class="mt-6 rounded-xl border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <flux:heading size="sm">{{ __('Backup History') }}</flux:heading>

        <div class="mt-4">
            @if (empty($history))
                <p class="py-6 text-center text-sm text-neutral-500">{{ __('No backups recorded yet.') }}</p>
            @else
                <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    @foreach ($history as $entry)
                        <div class="flex items-center justify-between py-2 text-sm">
                            <div>
                                <p class="font-medium">{{ $entry['name'] }}</p>
                                <p class="text-xs text-neutral-500">{{ date('Y-m-d H:i:s', $entry['date']) }}</p>
                            </div>
                            <span class="text-xs text-neutral-400">{{ round($entry['size'] / 1024 / 1024, 2) }} MB</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
