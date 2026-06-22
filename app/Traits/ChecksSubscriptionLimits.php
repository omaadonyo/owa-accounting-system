<?php

namespace App\Traits;

use App\Models\Business;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Quotation;
use App\Models\Subscription;

trait ChecksSubscriptionLimits
{
    public function getActiveSubscription(): ?Subscription
    {
        return auth()->user()?->subscription;
    }

    public function getPlan(): ?Plan
    {
        return $this->getActiveSubscription()?->plan;
    }

    public function getUsage(string $feature, ?Business $business = null): int
    {
        $sub = $this->getActiveSubscription();
        if (!$sub || !$sub->starts_at) {
            return 0;
        }

        $periodStart = $sub->starts_at;

        if ($business) {
            $businessIds = [$business->id];
        } else {
            $businessIds = auth()->user()->ownedBusinesses()->pluck('id')->toArray();
        }

        if (empty($businessIds)) {
            return 0;
        }

        return match ($feature) {
            'quotations' => Quotation::whereIn('business_id', $businessIds)
                ->where('created_at', '>=', $periodStart)
                ->count(),
            'invoices' => Invoice::whereIn('business_id', $businessIds)
                ->where('created_at', '>=', $periodStart)
                ->count(),
            'receipts' => Payment::whereHas('invoice', fn($q) => $q->whereIn('business_id', $businessIds))
                ->whereNotNull('receipt_number')
                ->where('created_at', '>=', $periodStart)
                ->count(),
            default => 0,
        };
    }

    public function checkLimit(string $feature, ?Business $business = null): array
    {
        $plan = $this->getPlan();
        if (!$plan) {
            return ['allowed' => false, 'reason' => 'No active subscription.'];
        }

        if ($plan->isUnlimited($feature)) {
            return ['allowed' => true];
        }

        $limit = $plan->limit($feature);
        $usage = $this->getUsage($feature, $business);

        if ($usage >= $limit) {
            return [
                'allowed' => false,
                'reason' => "You've reached your {$feature} limit of {$limit} on the {$plan->name} plan. Upgrade to continue.",
                'usage' => $usage,
                'limit' => $limit,
            ];
        }

        return ['allowed' => true, 'usage' => $usage, 'limit' => $limit];
    }
}
