<?php

namespace App\Console\Commands;

use App\Mail\DatabaseBackup as DatabaseBackupMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class DatabaseBackup extends Command
{
    protected $signature = 'backup:database {--mail-to=owacomputer@gmail.com : Email address to send the backup}';

    protected $description = 'Dump the database and email the SQL file';

    public function handle(): int
    {
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host');

        $dumpPath = env('DB_DUMP_PATH', 'mysqldump');

        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'backup-' . now()->format('Y-m-d_H-i-s') . '.sql';
        $path = $backupDir . '/' . $filename;

        $this->components->task('Dumping database', function () use ($dumpPath, $dbName, $dbUser, $dbPass, $dbHost, $path) {
            $command = sprintf(
                '%s --host=%s --user=%s %s %s > %s',
                escapeshellarg($dumpPath),
                escapeshellarg($dbHost),
                escapeshellarg($dbUser),
                $dbPass ? '--password=' . escapeshellarg($dbPass) : '',
                escapeshellarg($dbName),
                escapeshellarg($path),
            );

            $output = null;
            $exitCode = null;
            exec($command, $output, $exitCode);

            return $exitCode === 0;
        });

        if (! file_exists($path)) {
            $this->components->error('Backup file was not created.');
            return Command::FAILURE;
        }

        $fileSize = filesize($path);
        $mailTo = $this->option('mail-to');

        $this->components->task('Sending backup to ' . $mailTo, function () use ($path, $fileSize, $dbName, $mailTo) {
            Mail::to($mailTo)->send(new DatabaseBackupMail($path, $fileSize, $dbName));
        });

        $this->components->info('Backup completed: ' . $filename . ' (' . round($fileSize / 1024 / 1024, 2) . ' MB)');

        return Command::SUCCESS;
    }
}
