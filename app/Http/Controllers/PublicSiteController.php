<?php

namespace App\Http\Controllers;

use App\Mail\CustomerQuotationMail;
use App\Models\Business;
use App\Models\CustomerQuotation;
use App\Models\Fabric;
use App\Models\Plan;
use App\Models\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class PublicSiteController extends Controller
{
    public function index()
    {
        $businesses = Business::whereHas('fabrics', fn($q) => $q->where('selling_price_per_meter', '>', 0))
            ->orWhereHas('productsServices', fn($q) => $q->where('selling_price', '>', 0)->where('type', 'product'))
            ->with(['fabrics' => fn($q) => $q->where('selling_price_per_meter', '>', 0)->latest(),
                   'productsServices' => fn($q) => $q->where('selling_price', '>', 0)->where('type', 'product')->latest()])
            ->get();

        return view('site.index', compact('businesses'));
    }

    public function pricing()
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        return view('site.pricing', compact('plans'));
    }

    public function quote(string $type, int $id)
    {
        $item = match ($type) {
            'fabric' => Fabric::with('business')->findOrFail($id),
            'product' => ProductService::with('business')->findOrFail($id),
            default => abort(404),
        };

        if (match ($type) {
            'fabric' => !$item->selling_price_per_meter || $item->selling_price_per_meter <= 0,
            'product' => !$item->selling_price || $item->selling_price <= 0,
        }) {
            abort(404);
        }

        return view('site.quote', compact('item', 'type'));
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'item_type' => ['required', 'in:fabric,product'],
            'item_id' => ['required', 'integer', 'exists:' . ($request->item_type === 'fabric' ? 'fabrics' : 'products_services') . ',id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:255'],
            'customer_message' => ['nullable', 'string', 'max:2000'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ]);

        $item = $validated['item_type'] === 'fabric'
            ? Fabric::with('business')->findOrFail($validated['item_id'])
            : ProductService::with('business')->findOrFail($validated['item_id']);

        $unitPrice = $validated['item_type'] === 'fabric'
            ? $item->selling_price_per_meter
            : $item->selling_price;

        $totalPrice = $unitPrice * $validated['quantity'];

        $quotation = CustomerQuotation::create([
            'item_id' => $item->id,
            'item_type' => $validated['item_type'],
            'business_id' => $item->business_id,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'customer_message' => $validated['customer_message'] ?? null,
            'length_meters' => $validated['item_type'] === 'fabric' ? $validated['quantity'] : 0,
            'width_meters' => null,
            'total_price' => $totalPrice,
            'status' => 'pending',
        ]);

        $adminEmail = $item->business->email ?? $item->business->user?->email;

        if ($adminEmail) {
            Mail::to($adminEmail)->send(new CustomerQuotationMail($quotation));
        }

        return redirect()
            ->route('site.quote', ['type' => $validated['item_type'], 'id' => $item->id])
            ->with('success', 'Your quotation request has been submitted successfully! We will get back to you shortly.');
    }
}
