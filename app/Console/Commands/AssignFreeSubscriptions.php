<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;

class AssignFreeSubscriptions extends Command
{
    protected $signature = 'subscriptions:assign-free';

    protected $description = 'Assign the Free plan to all users without a subscription';

    public function handle(): void
    {
        $freePlan = Plan::where('slug', 'free')->first();

        if (!$freePlan) {
            $this->error('Free plan not found. Run db:seed --class=PlanSeeder first.');
            return;
        }

        $users = User::whereDoesntHave('subscriptions')->get();

        foreach ($users as $user) {
            Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $freePlan->id,
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'amount' => 0,
                'starts_at' => now(),
            ]);
            $this->info("Assigned Free plan to user: {$user->name}");
        }

        $this->info("Done. {$users->count()} users updated.");
    }
}
