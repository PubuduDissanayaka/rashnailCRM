@extends('layouts.vertical', ['title' => 'Create Expense'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Expenses', 'title' => 'Create New Expense'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        @include('expenses.partials.form')

                        <!-- Hidden fields for currency -->
                        <input type="hidden" name="currency" value="{{ $currency_code ?? 'USD' }}">
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
            form.addEventListener('submit', function(e) {
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