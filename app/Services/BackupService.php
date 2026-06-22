<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerQuotation;
use App\Models\Fabric;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\ProductService;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackupService
{
    protected array $tableMap = [
        'customers' => 'customers',
        'fabrics' => 'fabrics',
        'product_services' => 'products_services',
        'subscriptions' => 'subscriptions',
        'quotations' => 'quotations',
        'quotation_items' => 'quotation_items',
        'invoices' => 'invoices',
        'invoice_items' => 'invoice_items',
        'payments' => 'payments',
        'customer_quotations' => 'customer_quotations',
        'activity_logs' => 'activity_logs',
    ];

    protected array $insertOrder = [
        'customers',
        'fabrics',
        'product_services',
        'subscriptions',
        'quotations',
        'quotation_items',
        'invoices',
        'invoice_items',
        'payments',
        'customer_quotations',
        'activity_logs',
    ];

    public function export(int $businessId): string
    {
        $data = [
            'customers' => Customer::where('business_id', $businessId)->get()->toArray(),
            'fabrics' => Fabric::where('business_id', $businessId)->get()->toArray(),
            'product_services' => ProductService::where('business_id', $businessId)->get()->toArray(),
            'subscriptions' => Subscription::where('business_id', $businessId)->get()->toArray(),
            'quotations' => Quotation::where('business_id', $businessId)->get()->toArray(),
            'quotation_items' => QuotationItem::whereHas('quotation', fn($q) => $q->where('business_id', $businessId))->get()->toArray(),
            'invoices' => Invoice::where('business_id', $businessId)->get()->toArray(),
            'invoice_items' => InvoiceItem::whereHas('invoice', fn($q) => $q->where('business_id', $businessId))->get()->toArray(),
            'payments' => Payment::whereHas('invoice', fn($q) => $q->where('business_id', $businessId))->get()->toArray(),
            'customer_quotations' => CustomerQuotation::where('business_id', $businessId)->get()->toArray(),
            'activity_logs' => ActivityLog::where('business_id', $businessId)->get()->toArray(),
        ];

        $payload = [
            'version' => 1,
            'backup_date' => now()->toIso8601String(),
            'business_id' => $businessId,
            'data' => $data,
        ];

        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $business = Business::find($businessId);
        $slug = $business ? Str::slug($business->name) : 'unknown';
        $filename = "backup-{$businessId}-{$slug}-" . now()->format('Y-m-d_H-i-s') . '.json';
        $path = $dir . '/' . $filename;

        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }

    public function import(int $businessId, string $filePath): void
    {
        $contents = file_get_contents($filePath);
        if ($contents === false) {
            throw new \RuntimeException('Cannot read backup file.');
        }

        $payload = json_decode($contents, true);
        if (! isset($payload['data']) || ($payload['business_id'] ?? null) != $businessId) {
            throw new \RuntimeException('Invalid backup file or business mismatch.');
        }

        $data = $payload['data'];

        DB::transaction(function () use ($businessId, $data) {
            $this->deleteBusinessData($businessId);
            $this->insertBusinessData($data);
            $this->resetAutoIncrement();
        });
    }

    protected function deleteBusinessData(int $businessId): void
    {
        ActivityLog::where('business_id', $businessId)->delete();
        CustomerQuotation::where('business_id', $businessId)->delete();
        Payment::whereHas('invoice', fn($q) => $q->where('business_id', $businessId))->delete();
        InvoiceItem::whereHas('invoice', fn($q) => $q->where('business_id', $businessId))->delete();
        Invoice::where('business_id', $businessId)->delete();
        QuotationItem::whereHas('quotation', fn($q) => $q->where('business_id', $businessId))->delete();
        Quotation::where('business_id', $businessId)->delete();
        Subscription::where('business_id', $businessId)->delete();
        ProductService::where('business_id', $businessId)->delete();
        Fabric::where('business_id', $businessId)->delete();
        Customer::where('business_id', $businessId)->delete();
    }

    protected function insertBusinessData(array $data): void
    {
        foreach ($this->insertOrder as $key) {
            $rows = $data[$key] ?? [];
            if (empty($rows)) {
                continue;
            }

            $table = $this->tableMap[$key];

            foreach (array_chunk($rows, 100) as $chunk) {
                $normalized = array_map(fn ($row) => $this->normalizeDatetimes($row), $chunk);
                DB::table($table)->insert($normalized);
            }
        }
    }

    protected function normalizeDatetimes(array $row): array
    {
        foreach ($row as $col => $val) {
            if (is_array($val)) {
                $row[$col] = json_encode($val, JSON_UNESCAPED_UNICODE);
            } elseif (is_string($val) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $val)) {
                $row[$col] = str_replace('T', ' ', substr($val, 0, 19));
            } elseif ($val === '' && str_ends_with($col, '_at')) {
                $row[$col] = null;
            }
        }
        return $row;
    }

    protected function resetAutoIncrement(): void
    {
        $driver = DB::connection()->getDriverName();
        $dbName = DB::connection()->getDatabaseName();

        $tables = [
            'customers', 'fabrics', 'products_services', 'subscriptions',
            'quotations', 'quotation_items', 'invoices', 'invoice_items',
            'payments', 'customer_quotations', 'activity_logs',
        ];

        foreach ($tables as $table) {
            $maxId = DB::table($table)->max('id');
            if ($maxId !== null) {
                $nextId = $maxId + 1;
                if ($driver === 'mysql') {
                    DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = {$nextId}");
                }
            }
        }
    }
}
