@extends('layouts.vertical', ['title' => 'Service Package Details'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
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

@section('content')
    @include('layouts.partials.page-title', ['title' => $servicePackage->name . ' Details'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <div class="avatar-lg rounded-circle bg-soft-primary border">
                            <span class="avatar-title rounded-circle text-uppercase">
                                {{ substr($servicePackage->name, 0, 1) }}
                            </span>
                        </div>
                        <div class="ms-3">
                            <h4 class="mb-1">{{ $servicePackage->name }}</h4>
                            <p class="text-muted mb-0">{{ $servicePackage->description ?: 'No description provided' }}</p>
                        </div>
                    </div>

                    <div class="mt-4 pt-2">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Package Name</label>
                                    <input type="text" class="form-control" value="{{ $servicePackage->name }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <input type="text" class="form-control" value="{{ $servicePackage->is_active ? 'Active' : 'Inactive' }}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Base Price</label>
                                    <input type="text" class="form-control" value="{{ $currencySymbol }}{{ number_format($servicePackage->base_price, 2) }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Discounted Price</label>
                                    <input type="text" class="form-control" value="{{ $currencySymbol }}{{ number_format($servicePackage->discounted_price, 2) }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Savings</label>
                                    <input type="text" class="form-control" value="{{ $currencySymbol }}{{ number_format($servicePackage->base_price - $servicePackage->discounted_price, 2) }}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Total Duration</label>
                                    <input type="text" class="form-control" value="{{ $servicePackage->total_duration }} minutes" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Created At</label>
                                    <input type="text" class="form-control" value="{{ $servicePackage->created_at->format('M d, Y h:i A') }}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3" readonly>{{ $servicePackage->description }}</textarea>
                        </div>

                        <h5 class="mt-4 mb-3">Services in Package</h5>
                        <div class="table-responsive">
                            <table class="table table-centered table-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%;">#</th>
                                        <th>Service</th>
                                        <th>Price</th>
                                        <th>Duration (min)</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($servicePackage->services as $index => $service)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $service->name }}</td>
                                        <td>{{ $currencySymbol }}{{ number_format($service->price, 2) }}</td>
                                        <td>{{ $service->duration }} min</td>
                                        <td>{{ $service->pivot->quantity }}</td>
                                        <td>{{ $currencySymbol }}{{ number_format($service->price * $service->pivot->quantity, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('service-packages.index') }}" class="btn btn-light">Back to Packages</a>
                            @can('edit service packages')
                                <a href="{{ route('service-packages.edit', $servicePackage->slug) }}" class="btn btn-primary">Edit Package</a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection