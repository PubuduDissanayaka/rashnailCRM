@extends('layouts.vertical', ['title' => 'Invoice Management'])

@section('css')
<style>
.invoice-number { font-size: 0.85rem; }
.customer-cell { min-width: 160px; }
</style>
@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => 'Invoices', 'title' => 'Invoice List'])

    <div class="row">
        <div class="col-12">
            <div class="card" data-table="" data-table-rows-per-page="15">
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search invoices..."
                                type="text" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                        <button class="btn btn-danger d-none" data-table-delete-selected="">Delete</button>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="me-2 fw-semibold">Filter By:</span>
                        <div class="app-search">
                            <select class="form-select form-control my-1 my-md-0" data-table-filter="status">
                                <option value="All">Status</option>
                                <option value="paid">Paid</option>
                                <option value="sent">Sent</option>
                                <option value="draft">Draft</option>
                                <option value="overdue">Overdue</option>
                            </select>
                            <i class="app-search-icon text-muted" data-lucide="check-circle"></i>
                        </div>
                        <div>
                            <select class="form-select form-control my-1 my-md-0" data-table-set-rows-per-page="">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="15" selected>15</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">
                                    <input class="form-check-input form-check-input-light fs-14 mt-0"
                                        data-table-select-all="" id="select-all-files" type="checkbox" value="option" />
                                </th>
                                <th data-table-sort="sort-number">Invoice #</th>
                                <th data-table-sort="sort-date">Date</th>
                                <th data-table-sort="sort-customer">Customer</th>
                                <th data-table-sort="sort-sale">Sale</th>
                                <th data-table-sort="sort-amount">Amount</th>
                                <th data-table-sort="sort-status">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                            <tr>
                                <td class="ps-3">
                                    <input class="form-check-input form-check-input-light fs-14 file-item-check mt-0"
                                        type="checkbox" value="option" />
                                </td>
                                <td>
                                    <h5 class="m-0 invoice-number">
                                        <span class="ti ti-file-invoice text-{{ $invoice->is_paid ? 'success' : ($invoice->is_overdue ? 'danger' : 'warning') }} fs-lg"></span>
                                        <a class="link-reset fw-semibold"
                                            href="{{ route('invoices.show', $invoice) }}">
                                            #{{ $invoice->invoice_number }}
                                        </a>
                                    </h5>
                                </td>
                                <td data-sort="sort-date">
                                    {{ $invoice->invoice_date?->format('M j, Y') ?? $invoice->created_at->format('M j, Y') }}
                                </td>
                                <td class="customer-cell">
                                    @if($invoice->customer)
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar avatar-xs">
                                            <span class="avatar-title rounded-circle bg-primary-subtle text-primary fw-bold fs-xs">
                                                {{ substr($invoice->customer->first_name, 0, 1) }}{{ substr($invoice->customer->last_name, 0, 1) }}
                                            </span>
                                        </div>
                                        <div>
                                            <h5 class="text-nowrap fs-xs mb-0 lh-base">
                                                <a class="link-reset" data-sort="sort-customer"
                                                    href="{{ route('customers.show', $invoice->customer) }}">
                                                    {{ $invoice->customer->full_name }}
                                                </a>
                                            </h5>
                                        </div>
                                    </div>
                                    @else
                                    <span class="text-muted fs-xs">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($invoice->sale)
                                    <a href="{{ route('pos.receipt', $invoice->sale) }}" class="text-muted fs-xs">
                                        #{{ $invoice->sale->sale_number }}
                                    </a>
                                    @else
                                    <span class="text-muted fs-xs">—</span>
                                    @endif
                                </td>
                                <td data-sort="sort-amount">
                                    <span class="fw-semibold">{{ $currencySymbol }}{{ number_format($invoice->total_amount, 2) }}</span>
                                </td>
                                <td data-sort="sort-status">
                                    @if($invoice->is_paid)
                                    <span class="badge bg-success-subtle text-success badge-label">Paid</span>
                                    @elseif($invoice->is_overdue)
                                    <span class="badge bg-danger-subtle text-danger badge-label">Overdue</span>
                                    @elseif($invoice->is_sent)
                                    <span class="badge bg-info-subtle text-info badge-label">Sent</span>
                                    @else
                                    <span class="badge bg-secondary-subtle text-secondary badge-label">Draft</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <a class="btn btn-default btn-icon btn-sm"
                                            href="{{ route('invoices.show', $invoice) }}"
                                            title="View Invoice"><i class="ti ti-eye fs-lg"></i></a>
                                        <a class="btn btn-default btn-icon btn-sm"
                                            href="javascript:void(0);"
                                            onclick="if(confirm('Delete invoice #{{ $invoice->invoice_number }}?')) { document.getElementById('delete-invoice-{{ $invoice->id }}').submit(); }"
                                            title="Delete"><i class="ti ti-trash fs-lg"></i></a>
                                        <form id="delete-invoice-{{ $invoice->id }}"
                                            action="{{ route('invoices.destroy', $invoice) }}"
                                            method="POST" style="display:none;">
                                            @csrf @method('DELETE')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="d-flex flex-column align-items-center justify-content-center py-5">
                                        <div class="avatar avatar-lg bg-light rounded-circle mb-3">
                                            <i class="ti ti-file-invoice text-muted fs-24"></i>
                                        </div>
                                        <h5 class="text-muted">No invoices found</h5>
                                        <p class="text-muted">Generate invoices from completed POS sales.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div data-table-pagination-info="invoices"></div>
                        <div data-table-pagination="">{{ $invoices->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/custom-table.js'])
@endsection
