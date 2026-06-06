<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DatabaseBackup extends Mailable
{
    use Queueable, SerializesModels;

    public string $backupPath;

    public int $fileSize;

    public string $dbName;

    public string $date;

    public string $size;

    public function __construct(string $backupPath, int $fileSize, string $dbName)
    {
        $this->backupPath = $backupPath;
        $this->fileSize = $fileSize;
        $this->dbName = $dbName;
        $this->date = now()->format('Y-m-d H:i:s');
        $this->size = round($fileSize / 1024 / 1024, 2) . ' MB';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Database Backup — ' . now()->format('Y-m-d H:i'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.database-backup',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->backupPath)
                ->as('backup-' . now()->format('Y-m-d_H-i-s') . '.sql')
                ->withMime('application/sql'),
        ];
    }
}
