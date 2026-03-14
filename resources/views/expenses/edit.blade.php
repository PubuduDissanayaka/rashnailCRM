@extends('layouts.vertical', ['title' => 'Edit Expense'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Expenses', 'title' => 'Edit Expense'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if(in_array($expense->status, ['paid', 'rejected']))
                        <div class="alert alert-warning">
                            <i class="ti ti-alert-circle me-2"></i>
                            This expense cannot be edited because it is {{ $expense->status }}.
                        </div>
                    @endif

                    <form action="{{ route('expenses.update', $expense->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        @include('expenses.partials.form')

                        <!-- Hidden fields for currency -->
                        <input type="hidden" name="currency" value="{{ $expense->currency ?? 'USD' }}">
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const status = '{{ $expense->status }}';
            
            if (['paid', 'rejected'].includes(status)) {
                // Disable all form elements
                form.querySelectorAll('input, select, textarea, button').forEach(element => {
                    element.disabled = true;
                });
            }

            form.addEventListener('submit', function(e) {
                if (['paid', 'rejected'].includes(status)) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Cannot Edit',
                        text: 'This expense cannot be edited because it is ' + status + '.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                const amount = document.getElementById('amount').value;
                if (!amount || parseFloat(amount) <= 0) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Invalid Amount',
                        text: 'Please enter a valid amount greater than 0.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });
    </script>
@endsection