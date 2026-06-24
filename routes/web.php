<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::prefix('site')->name('site.')->group(function () {
    Route::get('/', [\App\Http\Controllers\PublicSiteController::class, 'index'])->name('index');
    Route::get('/pricing', [\App\Http\Controllers\PublicSiteController::class, 'pricing'])->name('pricing');
    Route::get('/{type}/{id}/quote', [\App\Http\Controllers\PublicSiteController::class, 'quote'])->name('quote');
    Route::post('/quote', [\App\Http\Controllers\PublicSiteController::class, 'submit'])->name('submit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        if (! currentBusiness()) {
            return redirect()->route('onboarding');
        }

        return view('dashboard');
    })->name('dashboard');

    Route::livewire('customers', 'pages::customers')->name('customers')
        ->middleware('can:manage-customers');

    Route::livewire('inventory', 'pages::inventory')->name('inventory')
        ->middleware('can:manage-inventory');

    Route::livewire('quotations', 'pages::quotations')->name('quotations');
    Route::livewire('quotations/create', 'pages::quotation-create')->name('quotations.create');
    Route::livewire('quotations/{id}/edit', 'pages::quotation-create')->name('quotations.edit');

    Route::livewire('invoices', 'pages::invoices')->name('invoices');
    Route::livewire('invoices/create', 'pages::invoice-create')->name('invoices.create');
    Route::livewire('invoices/{id}/edit', 'pages::invoice-create')->name('invoices.edit');

    Route::livewire('payments', 'pages::payments')->name('payments');
    Route::get('/payments/export/csv', function () {
        $payments = App\Models\Payment::with('invoice', 'creator')
            ->whereHas('invoice', fn($q) => $q->where('business_id', currentBusinessId()))
            ->latest()
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payments.csv"',
        ];

        $callback = function () use ($payments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Receipt', 'Invoice', 'Amount', 'Payment Date', 'Method', 'Reference', 'Notes', 'Recorded By']);

            foreach ($payments as $p) {
                fputcsv($handle, [
                    $p->receipt_number ?? '—',
                    $p->invoice?->invoice_number ?? '—',
                    number_format($p->amount, 2),
                    $p->payment_date->format('Y-m-d'),
                    str_replace('_', ' ', $p->payment_method),
                    $p->reference ?? '',
                    $p->notes ?? '',
                    $p->creator?->name ?? '—',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    })->name('payments.export');

    Route::livewire('users', 'pages::users')->name('users')
        ->middleware('can:manage-users');

    Route::livewire('reports', 'pages::reports')->name('reports');

    Route::livewire('store', 'store-manager')->name('store')
        ->middleware('can:manage-business');

    Route::livewire('customer-quotations', 'pages::customer-quotations')->name('customer-quotations');

    Route::post('/switch-business/{business}', function (\App\Models\Business $business) {
        if (! auth()->user()->businesses->contains($business->id) && ! auth()->user()->isSuperadmin()) {
            abort(403);
        }
        session(['active_business_id' => $business->id]);
        return redirect()->back();
    })->name('business.switch');

    Route::livewire('billing', 'pages::billing')->name('billing');

    Route::livewire('superadmin', 'pages::superadmin')->name('superadmin')
        ->middleware('can:superadmin');

    Route::get('/backups/{filename}', function (string $filename) {
        $path = storage_path('app/backups/' . basename($filename));
        if (! file_exists($path)) {
            abort(404);
        }
        $bizId = currentBusinessId();
        if (! $bizId || ! str_starts_with($filename, "backup-{$bizId}-")) {
            abort(403);
        }
        return response()->download($path);
    })->name('backups.download')->middleware('can:manage-business');
});

Route::middleware(['auth'])->group(function () {
    Route::livewire('onboarding', 'pages::onboarding')->name('onboarding');
});

require __DIR__.'/settings.php';

Route::middleware(['store-subdomain'])->group(function () {
    Route::get('/', function (\Illuminate\Http\Request $request) {
        $business = $request->get('store_business');
        if (! $business) {
            return redirect()->route('home');
        }
        return view('store-landing', compact('business'));
    })->name('store.landing');
});

Route::get('/store/{slug}', function (string $slug) {
    $business = \App\Models\Business::where('slug', $slug)->where('store_active', true)->firstOrFail();
    return view('store-landing', compact('business'));
})->name('store.landing.fallback');
