<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Receipt #{{ $sale->sale_number }}</title>

    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    <style>
        /* ========================================
           ENTERPRISE POS RECEIPT - THERMAL OPTIMIZED
           Paper Savings: ~30-35% reduction
           Compatible: 80mm thermal printers
           ======================================== */

        /* CSS Variables */
        :root {
            --receipt-width: 280px;
            --spacing-xs: 4px;
            --spacing-sm: 8px;
            --spacing-md: 12px;
            --spacing-lg: 16px;
            --font-xs: 9px;
            --font-sm: 10px;
            --font-base: 11px;
            --font-lg: 12px;
            --font-xl: 14px;
            --font-xxl: 18px;
            --color-text: #212529;
            --color-muted: #6c757d;
            --color-border: #dee2e6;
            --color-success: #198754;
            --color-primary: #0d6efd;
            --color-whatsapp: #25d366;
        }

        /* Base Reset */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        /* Body */
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            font-size: var(--font-base);
            line-height: 1.5;
            color: var(--color-text);
            background: #f8f9fa;
            padding: 20px;
            margin: 0;
        }

        /* Container */
        .receipt-container {
            max-width: 500px;
            margin: 0 auto;
        }

        /* Receipt Paper (Screen View) */
        .receipt-paper {
            background: white;
            width: var(--receipt-width);
            margin: 0 auto 20px;
            padding: var(--spacing-lg);
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--color-border);
        }

        /* Header */
        .receipt-header {
            text-align: center;
            padding-bottom: var(--spacing-md);
            border-bottom: 2px dashed var(--color-border);
            margin-bottom: var(--spacing-md);
        }

        .business-name {
            font-size: var(--font-xxl);
            font-weight: 700;
            margin: 0 0 4px 0;
            color: var(--color-text);
        }

        .business-contact {
            font-size: var(--font-xs);
            color: var(--color-muted);
            margin: 0;
            line-height: 1.4;
        }

        /* Receipt Meta */
        .receipt-meta {
            margin-bottom: var(--spacing-md);
            font-size: var(--font-sm);
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .receipt-number {
            font-weight: 600;
        }

        /* Sections */
        .receipt-section {
            margin-bottom: var(--spacing-md);
        }

        .section-title {
            font-size: var(--font-lg);
            font-weight: 600;
            margin-bottom: var(--spacing-sm);
            color: var(--color-text);
            border-bottom: 1px solid var(--color-border);
            padding-bottom: 4px;
        }

        /* Items List */
        .items-list {
            margin-top: var(--spacing-sm);
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: var(--font-base);
        }

        .item-name {
            flex: 1;
            padding-right: var(--spacing-sm);
        }

        .item-price {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-weight: 500;
            white-space: nowrap;
        }

        .item-discount {
            padding-left: var(--spacing-md);
            font-size: var(--font-sm);
            color: var(--color-success);
        }

        /* Totals */
        .totals-list {
            border-top: 1px dashed var(--color-border);
            padding-top: var(--spacing-sm);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: var(--font-base);
        }

        .total-row span:last-child {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
        }

        .total-discount {
            color: var(--color-success);
        }

        .grand-total {
            font-weight: 700;
            font-size: var(--font-lg);
            padding-top: var(--spacing-sm);
            margin-top: var(--spacing-sm);
            border-top: 2px solid var(--color-text);
            color: var(--color-primary);
        }

        /* Payment */
        .payment-list {
            margin-top: var(--spacing-sm);
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: var(--font-base);
        }

        .payment-row span:last-child {
            font-family: 'JetBrains Mono', monospace;
        }

        .change-row {
            font-weight: 700;
            color: var(--color-success);
            font-size: var(--font-lg);
            margin-top: var(--spacing-sm);
            padding-top: var(--spacing-sm);
            border-top: 1px dashed var(--color-border);
        }

        /* Footer */
        .receipt-footer {
            text-align: center;
            margin-top: var(--spacing-lg);
            padding-top: var(--spacing-md);
            border-top: 2px dashed var(--color-border);
            font-size: var(--font-sm);
            color: var(--color-muted);
        }

        .receipt-footer p {
            margin: 2px 0;
        }

        .receipt-notes {
            font-style: italic;
            margin-top: var(--spacing-sm);
            color: var(--color-text);
        }

        /* Action Buttons */
        .receipt-actions {
            margin-top: 24px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .actions-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: 1fr;
        }

        /* Responsive Button Grid */
        @media (min-width: 640px) {
            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .receipt-container {
                max-width: 800px;
            }

            .actions-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Action Button Base */
        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 48px;
            padding: 0 16px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .action-btn:active {
            transform: translateY(0);
        }

        .action-btn i {
            font-size: 20px;
        }

        /* Button Variants */
        .btn-primary {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0b5ed7, #0950b8);
        }

        .btn-success {
            background: linear-gradient(135deg, #198754, #157347);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #157347, #146c43);
        }

        .btn-whatsapp {
            background: linear-gradient(135deg, var(--color-whatsapp), #1ebe57);
        }

        .btn-whatsapp:hover {
            background: linear-gradient(135deg, #1ebe57, #128c3f);
        }

        .btn-outline {
            background: white;
            color: #6c757d;
            border: 2px solid var(--color-border);
        }

        .btn-outline:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
            color: #495057;
        }

        /* Loading State */
        .action-btn.loading {
            pointer-events: none;
            opacity: 0.7;
            position: relative;
        }

        .action-btn.loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Notification System */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            font-weight: 500;
            color: white;
            animation: slideIn 0.3s ease;
        }

        .notification-success {
            background: #198754;
        }

        .notification-error {
            background: #dc3545;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }

        /* Hide in print */
        .no-print {
            display: block;
        }

        /* Print Styles */
        @media print {
            @page {
                margin: 10mm;
            }

            body {
                background: white;
                padding: 0;
                margin: 0;
            }

            .receipt-container {
                max-width: none;
            }

            .receipt-paper {
                width: 280px;
                margin: 0;
                padding: 8px;
                box-shadow: none;
                border: none;
                border-radius: 0;
            }

            .receipt-header {
                padding-bottom: 6px;
                margin-bottom: 6px;
            }

            .business-name {
                font-size: 14px;
                margin-bottom: 2px;
            }

            .business-contact {
                font-size: 8px;
                line-height: 1.3;
            }

            .receipt-meta {
                margin-bottom: 8px;
            }

            .meta-row {
                font-size: 8px;
                margin-bottom: 1px;
            }

            .receipt-section {
                margin-bottom: 8px;
            }

            .section-title {
                font-size: 9px;
                margin-bottom: 4px;
                padding-bottom: 2px;
            }

            .item-row {
                font-size: 9px;
                margin-bottom: 2px;
            }

            .item-discount {
                font-size: 8px;
            }

            .total-row {
                font-size: 9px;
                margin-bottom: 2px;
            }

            .grand-total {
                font-size: 10px;
                padding-top: 4px;
                margin-top: 4px;
            }

            .payment-row {
                font-size: 9px;
                margin-bottom: 2px;
            }

            .change-row {
                font-size: 10px;
                margin-top: 4px;
                padding-top: 4px;
            }

            .receipt-footer {
                margin-top: 8px;
                padding-top: 6px;
                font-size: 8px;
            }

            .receipt-footer p {
                margin: 2px 0;
            }

            /* Hide buttons in print */
            .receipt-actions,
            .no-print {
                display: none !important;
            }

            /* Remove all shadows */
            * {
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- Receipt Container -->
    <div class="receipt-container">
        <!-- Receipt Paper (Print Area) -->
        <div class="receipt-paper">

            <!-- Header: Business Info (Compact) -->
            <header class="receipt-header">
                <h1 class="business-name">{{ $businessName }}</h1>
                <p class="business-contact">
                    @if($businessAddress){{ $businessAddress }}@endif
                    @if($businessAddress && $businessPhone) • @endif
                    @if($businessPhone){{ $businessPhone }}@endif
                    @if($businessPhone && $businessEmail) • @endif
                    @if($businessEmail){{ $businessEmail }}@endif
                </p>
            </header>

            <!-- Sale Meta Info (Compact Single Line) -->
            <section class="receipt-meta">
                <div class="meta-row">
                    <span class="receipt-number">#{{ $sale->sale_number }}</span>
                    <span class="receipt-date">{{ $sale->sale_date->format('m/d/Y g:i A') }}</span>
                </div>
                <div class="meta-row">
                    <span>Cashier: {{ $sale->user->name }}</span>
                </div>
                @if($sale->customer)
                <div class="meta-row">
                    <span>Customer: {{ $sale->customer->first_name }} {{ $sale->customer->last_name }}</span>
                </div>
                @endif
            </section>

            <!-- Items Section -->
            <section class="receipt-section items-section">
                <h2 class="section-title">ITEMS</h2>
                <div class="items-list">
                    @foreach($sale->items as $item)
                    <div class="item-row">
                        <span class="item-name">{{ $item->quantity }}x {{ $item->item_name }}</span>
                        <span class="item-price">{{ $currencySymbol }}{{ number_format($item->line_total, 2) }}</span>
                    </div>
                    @if($item->discount_amount > 0)
                    <div class="item-row item-discount">
                        <span>Discount</span>
                        <span>-{{ $currencySymbol }}{{ number_format($item->discount_amount, 2) }}</span>
                    </div>
                    @endif
                    @endforeach
                </div>
            </section>

            <!-- Totals Section -->
            <section class="receipt-section totals-section">
                <div class="totals-list">
                    <div class="total-row">
                        <span>Subtotal</span>
                        <span>{{ $currencySymbol }}{{ number_format($sale->subtotal, 2) }}</span>
                    </div>
                    @if($sale->discount_amount > 0)
                    <div class="total-row total-discount">
                        <span>Discount</span>
                        <span>-{{ $currencySymbol }}{{ number_format($sale->discount_amount, 2) }}</span>
                    </div>
                    @endif
                    @if($sale->coupon_discount_amount > 0)
                    <div class="total-row total-coupon-discount">
                        <span>Coupon Discount</span>
                        <span>-{{ $currencySymbol }}{{ number_format($sale->coupon_discount_amount, 2) }}</span>
                    </div>
                    @endif
                    @if($sale->tax_amount > 0)
                    <div class="total-row">
                        <span>Tax</span>
                        <span>{{ $currencySymbol }}{{ number_format($sale->tax_amount, 2) }}</span>
                    </div>
                    @endif
                    <div class="total-row grand-total">
                        <strong>TOTAL</strong>
                        <strong>{{ $currencySymbol }}{{ number_format($sale->total_amount, 2) }}</strong>
                    </div>
                </div>
            </section>

            <!-- Payment Section -->
            <section class="receipt-section payment-section">
                <h2 class="section-title">PAYMENT</h2>
                <div class="payment-list">
                    @foreach($sale->payments as $payment)
                    <div class="payment-row">
                        <span>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                        <span>{{ $currencySymbol }}{{ number_format($payment->amount, 2) }}</span>
                    </div>
                    @endforeach
                    @if($sale->change_amount > 0)
                    <div class="payment-row change-row">
                        <strong>CHANGE</strong>
                        <strong>{{ $currencySymbol }}{{ number_format($sale->change_amount, 2) }}</strong>
                    </div>
                    @endif
                </div>
            </section>

            <!-- Footer (NO EMOJIS) -->
            <footer class="receipt-footer">
                <p>Thank you for your business!</p>
                <p>Visit us again soon</p>
                @if($sale->notes)
                <p class="receipt-notes">{{ $sale->notes }}</p>
                @endif
            </footer>

        </div><!-- End receipt-paper -->

        <!-- Action Buttons (Hidden in Print) -->
        <div class="receipt-actions no-print">
            <div class="actions-grid">

                <!-- Print Receipt Button -->
                <button
                    onclick="printReceipt()"
                    class="action-btn btn-primary"
                    id="print-btn"
                    aria-label="Print receipt">
                    <i class="ti ti-printer"></i>
                    <span>Print Receipt</span>
                </button>

                <!-- WhatsApp Button (Conditional) -->
                @if($sale->customer && $sale->customer->phone)
                <button
                    onclick="sendWhatsApp()"
                    class="action-btn btn-whatsapp"
                    id="whatsapp-btn"
                    aria-label="Send via WhatsApp">
                    <i class="ti ti-brand-whatsapp"></i>
                    <span>Send WhatsApp</span>
                </button>
                @endif

                <!-- Back to POS Button -->
                <a
                    href="{{ route('pos.index') }}"
                    class="action-btn btn-outline"
                    aria-label="Back to POS">
                    <i class="ti ti-arrow-left"></i>
                    <span>Back to POS</span>
                </a>

            </div>
        </div>

    </div><!-- End receipt-container -->

    <!-- JavaScript -->
    <script>
        /**
         * Print receipt (browser print dialog)
         */
        function printReceipt() {
            window.print();
        }

        /**
         * Send via WhatsApp (NO EMOJIS
         */
        function sendWhatsApp() {
            const phone = "{{ $whatsappPhone ?? '' }}";

            if (!phone || phone.length < 10) {
                showNotification('Customer phone number not available', 'error');
                return;
            }

            const message = buildWhatsAppMessage();
            const url = `https://web.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(message)}`;
            window.open(url, '_blank');
        }

        /**
         * Build WhatsApp message (NO EMOJIS)
         */
        function buildWhatsAppMessage() {
            const businessName = "{{ $businessName }}";
            const saleNumber = "{{ $sale->sale_number }}";
            const customerName = "{{ $sale->customer?->full_name ?? 'Customer' }}";
            const total = "{{ $currencySymbol }}{{ number_format($sale->total_amount, 2) }}";
            const date = "{{ $sale->sale_date->format('M d, Y g:i A') }}";

            // Build items list
            let itemsList = "";
            @foreach($sale->items as $item)
            itemsList += "- {{ $item->quantity }}x {{ $item->item_name }} - {{ $currencySymbol }}{{ number_format($item->line_total, 2) }}\n";
            @endforeach

            // Build message (NO EMOJIS)
            return `Receipt from ${businessName}\n\n` +
                   `Dear ${customerName},\n\n` +
                   `Thank you for your purchase!\n\n` +
                   `Receipt Number: ${saleNumber}\n` +
                   `Date: ${date}\n\n` +
                   `Items:\n${itemsList}\n` +
                   `Total Amount: ${total}\n\n` +
                   `Thank you for your business!`;
        }

        /**
         * Show notification toast
         */
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;

            document.body.appendChild(notification);

            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
