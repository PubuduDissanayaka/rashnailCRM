<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #333; text-decoration: none; }
        .receipt-info { margin-bottom: 20px; background: #f9f9f9; padding: 15px; border-radius: 5px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { text-align: left; padding: 10px; border-bottom: 1px solid #ddd; background: #f5f5f5; }
        .items-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .totals { width: 100%; margin-top: 20px; }
        .totals td { padding: 5px 10px; }
        .totals .final-total { font-weight: bold; font-size: 1.2em; border-top: 2px solid #333; }
        .footer { text-align: center; margin-top: 40px; font-size: 12px; color: #999; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="{{ config('app.url') }}" class="logo">{{ config('app.name') }}</a>
            <p>Thank you for your business!</p>
        </div>

        <div class="receipt-info">
            <p><strong>Receipt #:</strong> {{ $sale->invoice_number }}</p>
            <p><strong>Date:</strong> {{ $sale->created_at->format('F j, Y g:i A') }}</p>
            @if($sale->customer)
                <p><strong>Customer:</strong> {{ $sale->customer->first_name }} {{ $sale->customer->last_name }}</p>
            @endif
            <p><strong>Served by:</strong> {{ $sale->creator->name ?? 'Staff' }}</p>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="text-align: right;">Qty</th>
                    <th style="text-align: right;">Price</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr>
                    <td>
                        {{ $item->name }}
                        @if($item->discount_amount > 0)
                            <br><small style="color: #666;">(Disc: {{ number_format($item->discount_amount, 2) }})</small>
                        @endif
                    </td>
                    <td style="text-align: right;">{{ $item->quantity }}</td>
                    <td style="text-align: right;">{{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals" align="right" style="width: 250px;">
            <tr>
                <td>Subtotal:</td>
                <td style="text-align: right;">{{ number_format($sale->subtotal, 2) }}</td>
            </tr>
            @if($sale->discount_amount > 0)
            <tr>
                <td>Discount:</td>
                <td style="text-align: right;">-{{ number_format($sale->discount_amount, 2) }}</td>
            </tr>
            @endif
            @if($sale->tax_amount > 0)
            <tr>
                <td>Tax:</td>
                <td style="text-align: right;">{{ number_format($sale->tax_amount, 2) }}</td>
            </tr>
            @endif
            <tr class="final-total">
                <td>Total:</td>
                <td style="text-align: right;">{{ number_format($sale->total_amount, 2) }}</td>
            </tr>
        </table>
        <div style="clear: both;"></div>

        <div class="footer">
            <p>If you have any questions about this receipt, please contact us.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
