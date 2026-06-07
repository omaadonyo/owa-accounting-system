<?php

namespace App\Traits;

use App\Models\Business;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;

trait ChecksSubscriptionLimits
{
    public function getActiveSubscription(?Business $business): ?Subscription
    {
        if (!$business) {
            return null;
        }
        return $business->activeSubscription;
    }

    public function getPlan(?Business $business): ?Plan
    {
        $subscription = $this->getActiveSubscription($business);
        return $subscription?->plan;
    }

    public function getUsage(Business $business, string $feature): int
    {
        $subscription = $this->getActiveSubscription($business);
        if (!$subscription || !$subscription->starts_at) {
            return 0;
        }

        $periodStart = $subscription->starts_at;

        return match ($feature) {
            'quotations' => $business->quotations()
                ->where('created_at', '>=', $periodStart)
                ->count(),
            'invoices' => $business->invoices()
                ->where('created_at', '>=', $periodStart)
                ->count(),
            'receipts' => Payment::whereHas('invoice', fn($q) => $q->where('business_id', $business->id))
                ->whereNotNull('receipt_number')
                ->where('created_at', '>=', $periodStart)
                ->count(),
            default => 0,
        };
    }

    public function checkLimit(Business $business, string $feature): array
    {
        $plan = $this->getPlan($business);
        if (!$plan) {
            return ['allowed' => false, 'reason' => 'No active subscription.'];
        }

        if ($plan->isUnlimited($feature)) {
            return ['allowed' => true];
        }

        $limit = $plan->limit($feature);
        $usage = $this->getUsage($business, $feature);

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
