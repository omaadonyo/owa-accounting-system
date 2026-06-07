<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Console\Command;

class AssignFreeSubscriptions extends Command
{
    protected $signature = 'subscriptions:assign-free';

    protected $description = 'Assign the Free plan to all businesses without a subscription';

    public function handle(): void
    {
        $freePlan = Plan::where('slug', 'free')->first();

        if (!$freePlan) {
            $this->error('Free plan not found. Run db:seed --class=PlanSeeder first.');
            return;
        }

        $businesses = Business::whereDoesntHave('subscriptions')->get();

        foreach ($businesses as $business) {
            Subscription::create([
                'business_id' => $business->id,
                'plan_id' => $freePlan->id,
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'amount' => 0,
                'starts_at' => now(),
            ]);
            $this->info("Assigned Free plan to business: {$business->name}");
        }

        $this->info("Done. {$businesses->count()} businesses updated.");
    }
}
