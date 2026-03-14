@extends('layouts.vertical', ['title' => 'Create Purchase Order'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Inventory', 'title' => 'Create Purchase Order'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('inventory.purchase-orders.store') }}" method="POST" id="purchase-order-form">
                        @csrf
                        
                        @include('inventory.purchase-orders.partials.form')
                        
                        @include('inventory.purchase-orders.partials.items-table')
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-1"></i> Create Purchase Order
                            </button>
                            <a href="{{ route('inventory.purchase-orders.index') }}" class="btn btn-secondary">
                                <i class="ti ti-x me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
@endsection

@section('scripts')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation
            document.getElementById('purchase-order-form').addEventListener('submit', function(e) {
                const items = document.querySelectorAll('.item-row');
                let hasValidItems = false;
                
                items.forEach(row => {
                    const supplySelect = row.querySelector('.supply-select');
                    const quantity = row.querySelector('.quantity').value;
                    
                    if (supplySelect.value && parseFloat(quantity) > 0) {
                        hasValidItems = true;
                    }
                });
                
                if (!hasValidItems) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Validation Error',
                        text: 'Please add at least one item with a valid quantity.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });
    </script>
    
    @stack('scripts')
@endsection