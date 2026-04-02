<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        /* DOMPDF-compatible receipt styles */
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #212529;
            padding: 0;
            margin: 0;
        }

        .receipt {
            width: 300px;
            margin: 0 auto;
            padding: 16px;
        }

        /* Header */
        .header {
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 2px dashed #ccc;
            margin-bottom: 10px;
        }

        .logo {
            max-width: 120px;
            max-height: 50px;
            margin-bottom: 6px;
        }

        .business-name {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 2px;
        }

        .tagline {
            font-size: 10px;
            color: #6c757d;
            font-style: italic;
            margin: 0 0 4px;
        }

        .contact {
            font-size: 9px;
            color: #6c757d;
            margin: 0;
        }

        /* Meta */
        .meta {
            margin-bottom: 10px;
            font-size: 10px;
        }

        .meta-row {
            overflow: hidden;
            margin-bottom: 2px;
        }

        .meta-left { float: left; font-weight: 600; }
        .meta-right { float: right; }

        /* Section */
        .section { margin-bottom: 10px; }

        .section-title {
            font-size: 12px;
            font-weight: 600;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }

        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table td {
            padding: 2px 0;
            font-size: 11px;
            vertical-align: top;
        }

        .items-table .item-price {
            text-align: right;
            white-space: nowrap;
            font-family: 'DejaVu Sans Mono', monospace;
        }

        .items-table .discount-row td {
            font-size: 10px;
            color: #198754;
            padding-left: 12px;
        }

        /* Totals */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            border-top: 1px dashed #ccc;
            padding-top: 6px;
        }

        .totals-table td {
            padding: 2px 0;
            font-size: 11px;
        }

        .totals-table .amount {
            text-align: right;
            font-family: 'DejaVu Sans Mono', monospace;
        }

        .totals-table .discount td { color: #198754; }

        .totals-table .grand-total td {
            font-weight: 700;
            font-size: 13px;
            padding-top: 6px;
            border-top: 2px solid #212529;
            color: #0d6efd;
        }

        /* Payment */
        .payment-table {
            width: 100%;
            border-collapse: collapse;
        }

        .payment-table td {
            padding: 2px 0;
            font-size: 11px;
        }

        .payment-table .amount {
            text-align: right;
            font-family: 'DejaVu Sans Mono', monospace;
        }

        .payment-table .change td {
            font-weight: 700;
            font-size: 12px;
            color: #198754;
            padding-top: 6px;
            border-top: 1px dashed #ccc;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 14px;
            padding-top: 10px;
            border-top: 2px dashed #ccc;
            font-size: 10px;
            color: #6c757d;
        }

        .footer p { margin: 2px 0; }

        .notes {
            font-style: italic;
            margin-top: 6px;
            color: #212529;
        }
    </style>
</head>
<body>
    <div class="receipt">

        <!-- Header -->
        <div class="header">
            @if($businessLogo)
                <img src="{{ public_path('storage/' . $businessLogo) }}" alt="{{ $businessName }}" class="logo">
            @endif
            <h1 class="business-name">{{ $businessName }}</h1>
            @if($businessTagline)
                <p class="tagline">{{ $businessTagline }}</p>
            @endif
            <p class="contact">
                @if($businessAddress){{ $businessAddress }}@endif
                @if($businessAddress && $businessPhone) &bull; @endif
                @if($businessPhone){{ $businessPhone }}@endif
                @if($businessPhone && $businessEmail) &bull; @endif
                @if($businessEmail){{ $businessEmail }}@endif
            </p>
        </div>

        <!-- Sale Meta -->
        <div class="meta">
            <div class="meta-row">
                <span class="meta-left">#{{ $sale->sale_number }}</span>
                <span class="meta-right">{{ $sale->sale_date->format('m/d/Y g:i A') }}</span>
            </div>
            <div class="meta-row" style="clear:both;">
                <span>Cashier: {{ $sale->user->name }}</span>
            </div>
            @if($sale->customer)
            <div class="meta-row">
                <span>Customer: {{ $sale->customer->first_name }} {{ $sale->customer->last_name }}</span>
            </div>
            @endif
        </div>

        <!-- Items -->
        <div class="section">
            <h2 class="section-title">ITEMS</h2>
            <table class="items-table">
                @foreach($sale->items as $item)
                <tr>
                    <td>{{ $item->quantity }}x {{ $item->item_name }}</td>
                    <td class="item-price">{{ $currencySymbol }}{{ number_format($item->line_total, 2) }}</td>
                </tr>
                @if($item->discount_amount > 0)
                <tr class="discount-row">
                    <td>Discount</td>
                    <td class="item-price">-{{ $currencySymbol }}{{ number_format($item->discount_amount, 2) }}</td>
                </tr>
                @endif
                @endforeach
            </table>
        </div>

        <!-- Totals -->
        <div class="section">
            <table class="totals-table">
                <tr>
                    <td>Subtotal</td>
                    <td class="amount">{{ $currencySymbol }}{{ number_format($sale->subtotal, 2) }}</td>
                </tr>
                @if($sale->discount_amount > 0)
                <tr class="discount">
                    <td>Discount</td>
                    <td class="amount">-{{ $currencySymbol }}{{ number_format($sale->discount_amount, 2) }}</td>
                </tr>
                @endif
                @if($sale->coupon_discount_amount > 0)
                <tr class="discount">
                    <td>Coupon Discount</td>
                    <td class="amount">-{{ $currencySymbol }}{{ number_format($sale->coupon_discount_amount, 2) }}</td>
                </tr>
                @endif
                @if($sale->tax_amount > 0)
                <tr>
                    <td>Tax</td>
                    <td class="amount">{{ $currencySymbol }}{{ number_format($sale->tax_amount, 2) }}</td>
                </tr>
                @endif
                <tr class="grand-total">
                    <td>TOTAL</td>
                    <td class="amount">{{ $currencySymbol }}{{ number_format($sale->total_amount, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Payment -->
        <div class="section">
            <h2 class="section-title">PAYMENT</h2>
            <table class="payment-table">
                @foreach($sale->payments as $payment)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                    <td class="amount">{{ $currencySymbol }}{{ number_format($payment->amount, 2) }}</td>
                </tr>
                @endforeach
                @if($sale->change_amount > 0)
                <tr class="change">
                    <td>CHANGE</td>
                    <td class="amount">{{ $currencySymbol }}{{ number_format($sale->change_amount, 2) }}</td>
                </tr>
                @endif
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>Visit us again soon</p>
            @if($sale->notes)
            <p class="notes">{{ $sale->notes }}</p>
            @endif
        </div>

    </div>
</body>
</html>
