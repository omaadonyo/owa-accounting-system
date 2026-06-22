<?php

namespace App\Traits;

use App\Models\ActivityLog;
use App\Notifications\CustomerCreated;
use App\Notifications\InvoiceCreated;
use App\Notifications\PaymentReceived;
use App\Notifications\QuotationConverted;
use App\Notifications\QuotationCreated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    public static function log(string $action, ?string $description = null, ?Model $subject = null, ?array $properties = null): void
    {
        $user = auth()->user();

        ActivityLog::create([
            'user_id' => $user?->id,
            'business_id' => currentBusinessId(),
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'description' => $description,
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);

        if ($user) {
            self::dispatchNotification($user, $action, $subject, $properties);
        }
    }

    private static function dispatchNotification($user, string $action, ?Model $subject, ?array $properties): void
    {
        $notification = match ($action) {
            'invoice_created' => $subject ? new InvoiceCreated(
                invoiceNumber: $subject->invoice_number,
                customerName: $subject->customer?->name ?? 'Walk-in',
                total: (float) $subject->total,
                invoiceId: (int) $subject->id,
            ) : null,

            'quotation_created' => $subject ? new QuotationCreated(
                quotationNumber: $subject->quotation_number,
                customerName: $subject->customer?->name ?? 'Walk-in',
                total: (float) $subject->total,
                quotationId: (int) $subject->id,
            ) : null,

            'payment_recorded' => new PaymentReceived(
                receiptNumber: $properties['receipt'] ?? 'N/A',
                customerName: $subject?->customer?->name ?? 'Walk-in',
                amount: (float) ($properties['amount'] ?? 0),
                paymentId: (int) ($subject?->id ?? 0),
            ),

            'customer_created' => $subject ? new CustomerCreated(
                customerName: $subject->name,
                customerEmail: $subject->email,
                customerId: (int) $subject->id,
            ) : null,

            'quotation_converted' => $subject ? new QuotationConverted(
                quotationNumber: $subject->quotation_number,
                customerName: $subject->customer?->name ?? 'Walk-in',
                quotationId: (int) $subject->id,
            ) : null,

            default => null,
        };

        if ($notification) {
            $user->notify($notification);
        }
    }
}
