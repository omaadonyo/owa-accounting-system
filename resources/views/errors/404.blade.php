<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ __('Not Found') }} — {{ config('app.name', 'Akatabo') }}</title>
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{color-scheme:dark;--zinc-50:#fafafa;--zinc-100:#f4f4f5;--zinc-200:#e4e4e7;--zinc-300:#d4d4d8;--zinc-400:#a1a1aa;--zinc-500:#71717a;--zinc-600:#52525b;--zinc-700:#3f3f46;--zinc-800:#27272a;--zinc-900:#18181b;--zinc-950:#09090b}
body{font-family:'Instrument Sans',system-ui,-apple-system,sans-serif;background:var(--zinc-950);color:var(--zinc-100);min-height:100vh;display:flex;align-items:center;justify-content:center}
.wrapper{text-align:center;padding:2rem;max-width:480px}
.code{font-size:7rem;font-weight:600;line-height:1;letter-spacing:-.04em;background:linear-gradient(135deg,#f59e0b,#d97706);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.icon{display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:16px;background:rgba(245,158,11,.12);margin-bottom:1.5rem}
.icon svg{width:28px;height:28px;color:#f59e0b}
h1{font-size:1.25rem;font-weight:600;margin-top:1.5rem;letter-spacing:-.01em}
p{color:var(--zinc-400);margin-top:.5rem;font-size:.9rem;line-height:1.6}
.actions{display:flex;gap:.75rem;justify-content:center;margin-top:2rem}
.btn{display:inline-flex;align-items:center;gap:.5rem;padding:.6rem 1.25rem;border-radius:8px;font-size:.85rem;font-weight:500;text-decoration:none;transition:all .15s}
.btn-primary{background:#6366f1;color:#fff}
.btn-primary:hover{background:#4f46e5}
.btn-ghost{color:var(--zinc-300);border:1px solid var(--zinc-800)}
.btn-ghost:hover{background:var(--zinc-800);color:var(--zinc-100)}
</style>
</head>
<body>
<div class="wrapper">
    <div class="icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
    </div>
    <div class="code">404</div>
    <h1>{{ __('Page Not Found') }}</h1>
    <p>{{ __('The page you are looking for does not exist or has been moved. Check the URL or head back to the dashboard.') }}</p>
    <div class="actions">
        <a href="{{ url('/dashboard') }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            {{ __('Dashboard') }}
        </a>
        <a href="javascript:history.back()" class="btn btn-ghost">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5m7-7-7 7 7 7"/></svg>
            {{ __('Go Back') }}
        </a>
    </div>
</div>
</body>
</html>
