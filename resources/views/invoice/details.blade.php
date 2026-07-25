@extends('layouts.vertical', ['title' => 'Invoice #' . $invoice->invoice_number])

@section('css')
<style>
@media print {
    .app-header, .app-footer, .sidebar, .d-print-none { display: none !important; }
    .card { box-shadow: none !important; border: none !important; }
}
</style>
@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => 'Invoices', 'title' => 'Invoice #' . $invoice->invoice_number])

    <div class="row justify-content-center">
        <div class="col-xxl-12">
            <div class="row">
                <div class="col-xl-9">
                    <div class="card">
                        <div class="card-body px-4">
                            <!-- Header -->
                            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                                <div class="auth-brand mb-0">
                                    @if($businessLogo)
                                    <img alt="logo" height="28" src="{{ Storage::url($businessLogo) }}" />
                                    @else
                                    <a class="logo-dark" href="{{ url('/') }}">
                                        <img alt="logo" height="24" src="/images/logo-black.png" />
                                    </a>
                                    @endif
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-{{ $invoice->is_paid ? 'success' : ($invoice->is_overdue ? 'danger' : 'info') }}-subtle text-{{ $invoice->is_paid ? 'success' : ($invoice->is_overdue ? 'danger' : 'info') }} mb-2 fs-xs px-2 py-1">
                                        {{ $invoice->is_paid ? 'Paid' : ($invoice->is_overdue ? 'Overdue' : ($invoice->is_sent ? 'Sent' : 'Draft')) }}
                                    </span>
                                    <h4 class="fw-bold text-dark m-0">Invoice #{{ $invoice->invoice_number }}</h4>
                                </div>
                            </div>

                            <!-- Invoice Info -->
                            <div class="row">
                                <div class="col-6">
                                    <h6 class="text-uppercase text-muted mb-2">From</h6>
                                    <p class="mb-1 fw-semibold">{{ $businessName }}</p>
                                    @if($businessAddress)<p class="text-muted mb-1">{{ $businessAddress }}</p>@endif
                                    @if($businessPhone)<p class="text-muted mb-0">Phone: {{ $businessPhone }}</p>@endif
                                    @if($businessEmail)<p class="text-muted mb-0">Email: {{ $businessEmail }}</p>@endif
                                    <div class="mt-4">
                                        <h6 class="text-uppercase text-muted">Invoice Date</h6>
                                        <p class="mb-0 fw-medium">{{ $invoice->invoice_date?->format('d M Y') ?? $invoice->created_at->format('d M Y') }}</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h6 class="text-uppercase text-muted mb-2">To</h6>
                                    @if($invoice->customer)
                                    <p class="mb-1 fw-semibold">{{ $invoice->customer->full_name }}</p>
                                    <p class="text-muted mb-1">{{ $invoice->customer->address ?? 'Address not provided' }}</p>
                                    <p class="text-muted mb-0">Phone: {{ $invoice->customer->phone ?? '—' }}</p>
                                    @else
                                    <p class="text-muted mb-0">Walk-in Customer</p>
                                    @endif
                                    <div class="mt-4">
                                        <h6 class="text-uppercase text-muted">Due Date</h6>
                                        <p class="mb-0 fw-medium">{{ $invoice->due_date?->format('d M Y') ?? '—' }}</p>
                                    </div>
                                    @if($invoice->sale)
                                    <div class="mt-2">
                                        <h6 class="text-uppercase text-muted">Sale Reference</h6>
                                        <a href="{{ route('pos.receipt', $invoice->sale) }}" class="fw-medium">#{{ $invoice->sale->sale_number }}</a>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Items Table -->
                            <div class="table-responsive mt-4">
                                <table class="table table-bordered table-nowrap text-center align-middle">
                                    <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                        <tr class="text-uppercase fs-xxs">
                                            <th style="width: 50px;">#</th>
                                            <th class="text-start">Item</th>
                                            <th>Qty</th>
                                            <th>Unit Price</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse(($invoice->sale->items ?? collect()) as $idx => $item)
                                        <tr>
                                            <td>{{ str_pad($idx + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                            <td class="text-start">
                                                <strong>{{ $item->item_name }}</strong>
                                                @if($item->sellable && method_exists($item->sellable, 'description') && $item->sellable->description)
                                                <div class="text-muted small">{{ \Illuminate\Support\Str::limit($item->sellable->description, 60) }}</div>
                                                @endif
                                            </td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>{{ $currencySymbol }}{{ number_format($item->unit_price, 2) }}</td>
                                            <td class="text-end">{{ $currencySymbol }}{{ number_format($item->line_total, 2) }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-muted py-3">No item details available.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Totals -->
                            <div class="d-flex justify-content-end">
                                <table class="table w-auto table-borderless text-end">
                                    <tbody>
                                        <tr>
                                            <td class="fw-medium">Subtotal</td>
                                            <td>{{ $currencySymbol }}{{ number_format($invoice->subtotal, 2) }}</td>
                                        </tr>
                                        @if($invoice->discount_amount > 0)
                                        <tr>
                                            <td class="fw-medium">Discount</td>
                                            <td class="text-danger">- {{ $currencySymbol }}{{ number_format($invoice->discount_amount, 2) }}</td>
                                        </tr>
                                        @endif
                                        @if($invoice->tax_amount > 0)
                                        <tr>
                                            <td class="fw-medium">Tax</td>
                                            <td>{{ $currencySymbol }}{{ number_format($invoice->tax_amount, 2) }}</td>
                                        </tr>
                                        @endif
                                        <tr class="border-top pt-2 fs-5 fw-bold">
                                            <td>Total</td>
                                            <td>{{ $currencySymbol }}{{ number_format($invoice->total_amount, 2) }}</td>
                                        </tr>
                                        @if($invoice->amount_paid > 0 && $invoice->balance_due > 0)
                                        <tr>
                                            <td class="fw-medium text-success">Paid</td>
                                            <td class="text-success">{{ $currencySymbol }}{{ number_format($invoice->amount_paid, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium text-danger">Balance Due</td>
                                            <td class="text-danger">{{ $currencySymbol }}{{ number_format($invoice->balance_due, 2) }}</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            <!-- Notes -->
                            @if($invoice->notes)
                            <div class="mt-lg-4 mt-2 bg-light bg-opacity-50 rounded px-3 py-2">
                                <p class="mb-0 text-muted">
                                    <strong>Note:</strong> {{ $invoice->notes }}
                                </p>
                            </div>
                            @endif
                            @if($invoice->terms)
                            <div class="mt-lg-2 mt-2 bg-light bg-opacity-50 rounded px-3 py-2">
                                <p class="mb-0 text-muted">
                                    <strong>Terms:</strong> {{ $invoice->terms }}
                                </p>
                            </div>
                            @endif

                            <!-- Payments -->
                            @if($invoice->sale && $invoice->sale->payments->count() > 0)
                            <div class="mt-4">
                                <h6 class="fw-semibold mb-2">Payment History</h6>
                                <table class="table table-sm table-bordered w-auto">
                                    <thead class="table-light">
                                        <tr class="fs-xs text-uppercase">
                                            <th>Method</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                            <th>Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($invoice->sale->payments as $pmt)
                                        <tr class="fs-sm">
                                            <td class="text-capitalize">{{ $pmt->payment_method }}</td>
                                            <td>{{ $currencySymbol }}{{ number_format($pmt->amount, 2) }}</td>
                                            <td>{{ $pmt->payment_date?->format('M d, Y H:i') ?? '—' }}</td>
                                            <td>{{ $pmt->reference_number ?? '—' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif

                            <div class="mt-4">
                                <p class="fw-semibold mb-0">Thank you for your business!</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Actions -->
                <div class="col-xl-3 d-print-none">
                    <div class="card card-top-sticky">
                        <div class="card-body">
                            <div class="justify-content-center d-flex flex-column gap-2">
                                <a class="btn btn-primary" href="javascript:window.print()">
                                    <i class="ti ti-printer me-1"></i> Print
                                </a>
                                @can('edit invoices')
                                @if(!$invoice->is_paid)
                                <form action="{{ route('invoices.mark-paid', $invoice) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-success w-100" type="submit">
                                        <i class="ti ti-check me-1"></i> Mark as Paid
                                    </button>
                                </form>
                                @endif
                                @if(!$invoice->is_sent)
                                <form action="{{ route('invoices.mark-sent', $invoice) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-info w-100" type="submit">
                                        <i class="ti ti-send me-1"></i> Mark as Sent
                                    </button>
                                </form>
                                @endif
                                @endcan
                                @can('delete invoices')
                                <form action="{{ route('invoices.destroy', $invoice) }}" method="POST"
                                    onsubmit="return confirm('Delete invoice #{{ $invoice->invoice_number }}?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger w-100" type="submit">
                                        <i class="ti ti-trash me-1"></i> Delete
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
@endsection
