@extends('layouts.vertical', ['title' => 'Edit Category'])

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Inventory', 'title' => 'Edit Category'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Edit Category Information</h4>
                    <p class="text-muted mb-0">Update the details of {{ $category->name }}.</p>
                </div>
                <div class="card-body">
                    <form action="{{ route('inventory.categories.update', $category->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('inventory.categories.partials.form')
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Update Category</button>
                            <a href="{{ route('inventory.categories.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection