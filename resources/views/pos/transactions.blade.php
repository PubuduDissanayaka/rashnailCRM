@extends('layouts.vertical', ['title' => 'POS Transactions'])

@section('css')
@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => 'POS', 'title' => 'Transactions History'])

    <div class="row">
        <div class="col-12">
            <div class="card" data-table="" data-table-rows-per-page="10">
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search transactions..."
                                type="search" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                        <button class="btn btn-danger d-none" data-table-delete-selected="">Delete</button>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="me-2 fw-semibold">Filter By:</span>
                        <!-- Transaction Status Filter -->
                        <div class="app-search">
                            <select class="form-select form-control my-1 my-md-0" data-table-filter="status">
                                <option value="All">Status</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                                <option value="Refunded">Refunded</option>
                            </select>
                            <i class="app-search-icon text-muted" data-lucide="check-circle"></i>
                        </div>
                        <!-- Records Per Page -->
                        <div>
                            <select class="form-select form-control my-1 my-md-0" data-table-set-rows-per-page="">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
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
                                <th data-table-sort="sort-transaction">Transaction ID</th>
                                <th data-table-sort="sort-date">Date & Time</th>
                                <th data-table-sort="sort-customer">Customer Name</th>
                                <th>Items</th>
                                <th data-table-sort="sort-amount">Amount</th>
                                <th data-column="status" data-table-sort="sort-status">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead><!-- end table-head -->
                        <tbody>
                            @forelse($sales as $sale)
                            <tr>
                                <td class="ps-3">
                                    <input class="form-check-input form-check-input-light fs-14 file-item-check mt-0"
                                        type="checkbox" value="option" />
                                </td>
                                <td>
                                    <h5 class="m-0">
                                        <span class="ti ti-shopping-cart text-success fs-lg"></span>
                                        <a class="link-reset fw-semibold" data-sort="sort-transaction"
                                            href="{{ route('pos.receipt', $sale) }}">#{{ $sale->sale_number }}</a>
                                    </h5>
                                </td>
                                <td><span data-sort="sort-date">{{ $sale->sale_date->format('M j - M j, Y') }}</span></td>
                                <td>
                                    <div class="d-flex justify-content-start align-items-center gap-2">
                                        @if($sale->customer)
                                        <div class="avatar avatar-sm">
                                            <img alt="customer-avatar" class="img-fluid rounded-circle"
                                                src="/images/users/user-10.jpg" />
                                        </div>
                                        <div>
                                            <h5 class="text-nowrap fs-base mb-0 lh-base">
                                                <a class="link-reset" data-sort="sort-customer"
                                                    href="{{ route('customers.show', $sale->customer) }}">
                                                    {{ $sale->customer->first_name }} {{ $sale->customer->last_name }}
                                                </a>
                                            </h5>
                                            <p class="text-muted fs-xs mb-0">{{ $sale->customer->email }}</p>
                                        </div>
                                        @else
                                        <div class="avatar-sm flex-shrink-0">
                                            <span class="avatar-title text-bg-info fw-bold rounded-circle">
                                                WK
                                            </span>
                                        </div>
                                        <div>
                                            <h5 class="text-nowrap fs-base mb-0 lh-base">
                                                <a class="link-reset" data-sort="sort-customer" href="javascript:void(0);">
                                                    Walk-in Customer
                                                </a>
                                            </h5>
                                            <p class="text-muted fs-xs mb-0">No customer</p>
                                        </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        @foreach($sale->items->take(2) as $item)
                                        <span class="text-nowrap">{{ $item->quantity }}x {{ $item->item_name }}</span>
                                        @endforeach
                                        @if($sale->items->count() > 2)
                                        <span class="text-muted">+{{ $sale->items->count() - 2 }} more</span>
                                        @endif
                                    </div>
                                </td>
                                <td><span data-sort="sort-amount">{{ \App\Models\Setting::get('payment.currency_symbol', '$') }}{{ number_format($sale->total_amount, 2) }}</span></td>
                                <td>
                                    @if($sale->status == 'completed')
                                    <span data-sort="sort-status" class="badge bg-success-subtle text-success badge-label">Completed</span>
                                    @elseif($sale->status == 'cancelled')
                                    <span data-sort="sort-status" class="badge bg-danger-subtle text-danger badge-label">Cancelled</span>
                                    @elseif($sale->status == 'refunded')
                                    <span data-sort="sort-status" class="badge bg-warning-subtle text-warning badge-label">Refunded</span>
                                    @else
                                    <span data-sort="sort-status" class="badge bg-secondary-subtle text-secondary badge-label">{{ ucfirst($sale->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <a class="btn btn-default btn-icon btn-sm"
                                            href="{{ route('pos.receipt', $sale) }}"><i
                                                class="ti ti-eye fs-lg"></i></a>
                                        <a class="btn btn-default btn-icon btn-sm" data-table-delete-row=""
                                            href="javascript:void(0);"> <i class="ti ti-trash fs-lg"></i></a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="d-flex flex-column align-items-center justify-content-center py-5">
                                        <div class="avatar avatar-lg bg-light rounded-circle mb-3">
                                            <i class="ti ti-shopping-cart text-muted fs-24"></i>
                                        </div>
                                        <h5 class="text-muted">No transactions found</h5>
                                        <p class="text-muted">Try adjusting your search or filter to find what you're looking for</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody><!-- end table-body -->
                    </table><!-- end table -->
                </div>
                <div class="card-footer border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div data-table-pagination-info="transactions"></div>
                        <div data-table-pagination=""></div>
                    </div>
                </div>
            </div>
        </div><!-- end col -->
    </div><!-- end row -->
@endsection

@section('scripts')
    @vite(['resources/js/pages/custom-table.js'])
@endsection