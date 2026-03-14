@extends('layouts.vertical', ['title' => 'Edit Supply'])

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Inventory', 'title' => 'Edit Supply'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Edit Supply Information</h4>
                    <p class="text-muted mb-0">Update the details of {{ $supply->name }}.</p>
                </div>
                <div class="card-body">
                    <form action="{{ route('inventory.supplies.update', $supply->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('inventory.supplies.partials.form')
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Update Supply</button>
                            <a href="{{ route('inventory.supplies.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection