@extends('layouts.vertical', ['title' => 'Generate Invoice from Sale'])

@section('css')
@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => 'Invoices', 'title' => 'Generate Invoice'])

    <div class="row justify-content-center">
        <div class="col-xxl-12">
            <div class="row">
                <div class="col-xl-9">
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div>
                                    <h5 class="mb-1">Sale #{{ $sale->sale_number }}</h5>
                                    <p class="text-muted mb-0 fs-sm">
                                        {{ $sale->created_at->format('M d, Y H:i') }}
                                        —
                                        @if($sale->customer)
                                        Customer: <strong>{{ $sale->customer->full_name }}</strong>
                                        @else
                                        <em>Walk-in Customer</em>
                                        @endif
                                        @if($sale->user) | Staff: {{ $sale->user->name }} @endif
                                    </p>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success-subtle text-success fs-sm">{{ ucfirst($sale->status) }}</span>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('invoices.store-from-sale', $sale) }}">
                                @csrf

                                <!-- Items Table -->
                                <div class="table-responsive">
                                    <table class="table table-bordered table-nowrap text-center align-middle">
                                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                            <tr class="text-uppercase fs-xxs">
                                                <th>#</th>
                                                <th class="text-start">Item</th>
                                                <th>Qty</th>
                                                <th>Unit Price</th>
                                                <th class="text-end">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($sale->items as $idx => $item)
                                            <tr>
                                                <td>{{ $idx + 1 }}</td>
                                                <td class="text-start">
                                                    <strong>{{ $item->item_name }}</strong>
                                                </td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>{{ $currencySymbol }}{{ number_format($item->unit_price, 2) }}</td>
                                                <td class="text-end">{{ $currencySymbol }}{{ number_format($item->line_total, 2) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Totals -->
                                <div class="d-flex justify-content-end mt-3">
                                    <table class="table w-auto table-borderless text-end mb-0">
                                        <tr>
                                            <td class="fw-medium">Invoice #</td>
                                            <td><strong>{{ $nextInvoiceNumber }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium">Subtotal</td>
                                            <td>{{ $currencySymbol }}{{ number_format($sale->subtotal, 2) }}</td>
                                        </tr>
                                        @if(($sale->discount_amount + ($sale->coupon_discount_amount ?? 0)) > 0)
                                        <tr>
                                            <td class="fw-medium">Discount</td>
                                            <td class="text-danger">- {{ $currencySymbol }}{{ number_format($sale->discount_amount + ($sale->coupon_discount_amount ?? 0), 2) }}</td>
                                        </tr>
                                        @endif
                                        @if($sale->tax_amount > 0)
                                        <tr>
                                            <td class="fw-medium">Tax</td>
                                            <td>{{ $currencySymbol }}{{ number_format($sale->tax_amount, 2) }}</td>
                                        </tr>
                                        @endif
                                        <tr class="border-top fs-5 fw-bold">
                                            <td>Total</td>
                                            <td>{{ $currencySymbol }}{{ number_format($sale->total_amount, 2) }}</td>
                                        </tr>
                                    </table>
                                </div>

                                <!-- Form Fields -->
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" for="due_date">Due Date</label>
                                        <input type="date" class="form-control" id="due_date" name="due_date"
                                            value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}">
                                        <div class="form-text">Leave blank for 30 days from today</div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" for="terms">Payment Terms</label>
                                        <input type="text" class="form-control" id="terms" name="terms"
                                            value="{{ old('terms', 'Payment due within 30 days') }}"
                                            placeholder="e.g. Net 30">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" for="notes">Additional Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="1"
                                            placeholder="Thank you for your business!">{{ old('notes') }}</textarea>
                                    </div>
                                </div>

                                <div class="mt-4 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-file-invoice me-1"></i> Generate Invoice
                                    </button>
                                    <a href="{{ route('pos.receipt', $sale) }}" class="btn btn-light">
                                        <i class="ti ti-arrow-left me-1"></i> Back to Receipt
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 d-print-none">
                    <div class="card card-top-sticky">
                        <div class="card-body">
                            <h6 class="fw-semibold mb-2">Sale Summary</h6>
                            <div class="d-flex justify-content-between fs-sm mb-1">
                                <span class="text-muted">Sale #</span>
                                <span>{{ $sale->sale_number }}</span>
                            </div>
                            <div class="d-flex justify-content-between fs-sm mb-1">
                                <span class="text-muted">Date</span>
                                <span>{{ $sale->created_at->format('M d, Y') }}</span>
                            </div>
                            <div class="d-flex justify-content-between fs-sm mb-1">
                                <span class="text-muted">Items</span>
                                <span>{{ $sale->items->count() }}</span>
                            </div>
                            <div class="d-flex justify-content-between fs-sm fw-bold border-top pt-1 mt-1">
                                <span>Total</span>
                                <span>{{ $currencySymbol }}{{ number_format($sale->total_amount, 2) }}</span>
                            </div>
                            <hr>
                            <h6 class="fw-semibold mb-2">Payments</h6>
                            @foreach($sale->payments as $pmt)
                            <div class="d-flex justify-content-between fs-sm mb-1">
                                <span class="text-capitalize text-muted">{{ $pmt->payment_method }}</span>
                                <span>{{ $currencySymbol }}{{ number_format($pmt->amount, 2) }}</span>
                            </div>
                            @endforeach
                            @if($sale->payments->isEmpty())
                            <p class="text-muted fs-sm mb-0">No payments recorded</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
@endsection
