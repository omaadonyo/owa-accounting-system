<?php

namespace App\Console\Commands;

use App\Models\PageVisit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ResolveVisitCountries extends Command
{
    protected $signature = 'analytics:resolve-countries {--limit=100}';
    protected $description = 'Resolve country from IP addresses for page visits without country';

    public function handle(): void
    {
        $visits = PageVisit::whereNull('country')
            ->whereNotNull('ip_address')
            ->limit((int) $this->option('limit'))
            ->get();

        $this->info("Processing {$visits->count()} visits...");

        foreach ($visits as $visit) {
            $ip = $visit->ip_address;

            // Skip private/local IPs
            if (str_starts_with($ip, '127.') || str_starts_with($ip, '10.') || str_starts_with($ip, '192.168.') || str_starts_with($ip, '172.') || $ip === '::1') {
                $visit->updateQuietly(['country' => 'Local']);
                $this->line("  {$ip} → Local (skipped)");
                continue;
            }

            try {
                $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}?fields=status,country,city,regionName");

                if ($response->successful() && $response->json('status') === 'success') {
                    $visit->updateQuietly([
                        'country' => $response->json('country'),
                        'city' => $response->json('city'),
                        'region' => $response->json('regionName'),
                    ]);
                    $this->line("  {$ip} → {$visit->country}");
                } else {
                    $visit->updateQuietly(['country' => 'Unknown']);
                    $this->line("  {$ip} → Unknown (API response: {$response->body()})");
                }
            } catch (\Throwable $e) {
                $this->warn("  {$ip} → Error: {$e->getMessage()}");
            }

            // Rate limit: 45 req/min for free ip-api
            usleep(1_500_000); // 1.5 seconds between requests
        }

        $this->info('Done!');
    }
}
