<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        if (! auth()->user()->business) {
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
            ->whereHas('invoice', fn($q) => $q->where('business_id', auth()->user()->business->id))
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

    Route::get('/backups/{filename}', function (string $filename) {
        $path = storage_path('app/backups/' . basename($filename));
        if (! file_exists($path)) {
            abort(404);
        }
        return response()->download($path);
    })->name('backups.download')->middleware('can:manage-business');
});

Route::middleware(['auth'])->group(function () {
    Route::livewire('onboarding', 'pages::onboarding')->name('onboarding');
});

require __DIR__.'/settings.php';
