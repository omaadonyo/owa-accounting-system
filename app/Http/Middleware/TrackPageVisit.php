<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPageVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldTrack($request)) {
            try {
                PageVisit::create([
                    'business_id' => $this->resolveBusinessId(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'visited_url' => $request->fullUrl(),
                    'referrer_url' => $request->header('referer'),
                    'visited_at' => now(),
                ]);
            } catch (\Throwable) {
                // silently fail — tracking should never break the app
            }
        }

        return $next($request);
    }

    private function shouldTrack(Request $request): bool
    {
        // Skip non-GET requests, Livewire updates, and common bots
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->header('X-Livewire')) {
            return false;
        }

        $ua = mb_strtolower($request->userAgent() ?? '');
        $bots = ['bot', 'crawl', 'spider', 'scrape', 'curl', 'wget', 'go-http-client', 'php'];
        foreach ($bots as $bot) {
            if (str_contains($ua, $bot)) {
                return false;
            }
        }

        // Skip common non-page assets
        $skipPatterns = ['/_debugbar', '/build/', '/fonts/', '/livewire/', '/vendor/', '.css', '.js', '.ico', '.png', '.jpg', '.svg', '.woff', '.woff2', '.ttf'];
        $path = $request->path();
        foreach ($skipPatterns as $pattern) {
            if (str_contains($path, $pattern)) {
                return false;
            }
        }

        return true;
    }

    private function resolveBusinessId(): ?int
    {
        try {
            return session('active_business_id');
        } catch (\Throwable) {
            return null;
        }
    }
}
