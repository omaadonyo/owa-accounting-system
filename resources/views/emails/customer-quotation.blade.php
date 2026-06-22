<x-mail::message>
# New Quotation Request

**{{ $quotation->customer_name }}** has requested a quotation for **{{ $quotation->item->name }}**.

---

### Customer Details
- **Name:** {{ $quotation->customer_name }}
- **Email:** {{ $quotation->customer_email }}
@if($quotation->customer_phone)
- **Phone:** {{ $quotation->customer_phone }}
@endif

### Quotation Details
- **Item:** {{ $quotation->item->name }}
- **Type:** {{ ucfirst($quotation->item_type) }}
- **Quantity:** {{ number_format($quotation->length_meters ?: 1, 2) }}{{ $quotation->item_type === 'fabric' ? 'm' : ' ' . ($quotation->item->unit ?? 'units') }}
- **Price per {{ $quotation->item_type === 'fabric' ? 'meter' : ($quotation->item->unit ?? 'unit') }}:** {{ formatCurrency($quotation->item_type === 'fabric' ? $quotation->item->selling_price_per_meter : $quotation->item->selling_price, 2) }}
- **Estimated Total:** **{{ formatCurrency($quotation->total_price, 2) }}**

@if($quotation->customer_message)
### Message
{{ $quotation->customer_message }}
@endif

<x-mail::button :url="route('login')">
View in Dashboard
</x-mail::button>
</x-mail::message>
