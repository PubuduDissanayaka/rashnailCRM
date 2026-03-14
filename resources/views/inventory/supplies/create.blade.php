@extends('layouts.vertical', ['title' => 'Add New Supply'])

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Inventory', 'title' => 'Add New Supply'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Supply Information</h4>
                    <p class="text-muted mb-0">Fill in the details below to add a new supply item.</p>
                </div>
                <div class="card-body">
                    <form action="{{ route('inventory.supplies.store') }}" method="POST">
                        @include('inventory.supplies.partials.form')
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Create Supply</button>
                            <a href="{{ route('inventory.supplies.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection