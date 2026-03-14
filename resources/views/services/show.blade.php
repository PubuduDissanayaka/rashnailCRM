@extends('layouts.vertical', ['title' => $service->name . ' Details'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => $service->name . ' Details'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <div class="avatar-lg rounded-circle bg-soft-primary border">
                            <span class="avatar-title rounded-circle text-uppercase">
                                {{ substr($service->name, 0, 1) }}
                            </span>
                        </div>
                        <div class="ms-3">
                            <h4 class="mb-1">{{ $service->name }}</h4>
                            <p class="text-muted mb-0">{{ $service->description }}</p>
                        </div>
                    </div>

                    <div class="mt-4 pt-2">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Service Name</label>
                                    <input type="text" class="form-control" value="{{ $service->name }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Price</label>
                                    <input type="text" class="form-control" value="{{ $currencySymbol }}{{ number_format($service->price, 2) }}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Duration (minutes)</label>
                                    <input type="text" class="form-control" value="{{ $service->duration }} min" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <input type="text" class="form-control" value="{{ $service->is_active ? 'Active' : 'Inactive' }}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3" readonly>{{ $service->description }}</textarea>
                        </div>

                        <!-- Linked Supplies Section -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Linked Supplies</h5>
                                <p class="text-muted mb-0">Supplies required for this service (automatically deducted when appointments are completed)</p>
                            </div>
                            <div class="card-body">
                                @if($service->supplies->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Supply Name</th>
                                                    <th>SKU</th>
                                                    <th>Quantity Required</th>
                                                    <th>Type</th>
                                                    <th>Current Stock</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($service->supplies as $supply)
                                                    <tr>
                                                        <td>
                                                            <a href="{{ route('inventory.supplies.show', $supply->id) }}" class="text-primary">
                                                                {{ $supply->name }}
                                                            </a>
                                                        </td>
                                                        <td>{{ $supply->sku }}</td>
                                                        <td>{{ $supply->pivot->quantity_required ?? 1 }}</td>
                                                        <td>
                                                            @if($supply->pivot->is_optional ?? false)
                                                                <span class="badge bg-warning">Optional</span>
                                                            @else
                                                                <span class="badge bg-success">Required</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="badge {{ $supply->current_stock > 0 ? 'bg-success' : 'bg-danger' }}">
                                                                {{ $supply->current_stock }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info mb-0">
                                        <i class="ri-information-line me-2"></i> No supplies linked to this service.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Created At</label>
                                    <input type="text" class="form-control" value="{{ $service->created_at->format('M d, Y h:i A') }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Updated At</label>
                                    <input type="text" class="form-control" value="{{ $service->updated_at->format('M d, Y h:i A') }}" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <a href="{{ route('services.index') }}" class="btn btn-light">Back to Services</a>
                            @can('edit services')
                                <a href="{{ route('services.edit', $service->slug) }}" class="btn btn-primary">Edit Service</a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        // Check if there are success messages to display
        @if(session('success'))
            Swal.fire({
                title: 'Success!',
                text: '{{ session('success') }}',
                icon: 'success',
                confirmButtonClass: 'btn btn-primary'
            });
        @endif
        
        // Check if there are error messages to display
        @if(session('error'))
            Swal.fire({
                title: 'Error!',
                text: '{{ session('error') }}',
                icon: 'error',
                confirmButtonClass: 'btn btn-primary'
            });
        @endif
    </script>
@endsection