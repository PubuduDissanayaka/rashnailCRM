@extends('layouts.vertical', ['title' => 'Create Coupon'])

@section('css')
    <style>
        .conditional-field { display: none; }
    </style>
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Create Coupon', 'subtitle' => 'Coupons'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('coupons.store') }}">
                        @csrf
                        @include('coupons.partials.form')
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/form-choice.js'])
    @include('coupons.partials.form-scripts')
@endsection
