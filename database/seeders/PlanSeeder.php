<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::upsert([
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Perfect for getting started with basic business needs.',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'quotations_limit' => 5,
                'invoices_limit' => 2,
                'receipts_limit' => 1,
                'businesses_limit' => 2,
                'features' => json_encode([
                    '5 Quotations per month',
                    '2 Invoices per month',
                    '1 Receipt per month',
                    'Basic customer management',
                    'Fabric & inventory tracking',
                ]),
                'sort_order' => 1,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'For growing businesses that need more capacity.',
                'price_monthly' => 17000,
                'price_yearly' => 170000,
                'quotations_limit' => 100,
                'invoices_limit' => 100,
                'receipts_limit' => 120,
                'businesses_limit' => -1,
                'features' => json_encode([
                    '100 Quotations per month',
                    '100 Invoices per month',
                    '120 Receipts per month',
                    'Full customer management',
                    'Fabric & inventory tracking',
                    'PDF export',
                    'Email notifications',
                    'Priority support',
                ]),
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unlimited access for large-scale operations.',
                'price_monthly' => 26000,
                'price_yearly' => 260000,
                'quotations_limit' => -1,
                'invoices_limit' => -1,
                'receipts_limit' => -1,
                'businesses_limit' => -1,
                'features' => json_encode([
                    'Unlimited quotations',
                    'Unlimited invoices',
                    'Unlimited receipts',
                    'Full customer management',
                    'Fabric & inventory tracking',
                    'PDF export',
                    'Email notifications',
                    'Multiple users & roles',
                    'Priority support',
                    'Advanced reporting',
                    'Backup & restore',
                ]),
                'sort_order' => 3,
            ],
        ], ['slug'], ['name', 'description', 'price_monthly', 'price_yearly', 'quotations_limit', 'invoices_limit', 'receipts_limit', 'businesses_limit', 'features', 'sort_order']);
    }
}
