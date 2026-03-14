@extends('layouts.vertical', ['title' => 'Supply Categories'])

@section('css')
    @vite(['node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Inventory', 'title' => 'Supply Categories'])

    <div class="row">
        <div class="col-12">
            <div class="card" data-table="" data-table-rows-per-page="10">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">Category List</h4>
                        <p class="text-muted mb-0">Organize supplies into categories and subcategories</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search categories..." type="search" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                        @can('inventory.manage')
                        <a href="{{ route('inventory.categories.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Add Category
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @if($rootCategories->count() > 0)
                        <div class="row">
                            @foreach($rootCategories as $category)
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card border">
                                        <div class="card-header bg-light bg-opacity-25">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="mb-0">
                                                        @if($category->icon)
                                                            <i class="{{ $category->icon }} me-2"></i>
                                                        @endif
                                                        {{ $category->name }}
                                                    </h5>
                                                    <small class="text-muted">{{ $category->supplies->count() }} supplies</small>
                                                </div>
                                                <div class="d-flex gap-1">
                                                    @can('inventory.manage')
                                                    <a href="{{ route('inventory.categories.edit', $category->id) }}" class="btn btn-light btn-sm" title="Edit">
                                                        <i class="ti ti-edit fs-sm"></i>
                                                    </a>
                                                    <form id="delete-form-{{ $category->id }}" action="{{ route('inventory.categories.destroy', $category->id) }}" method="POST" style="display: none;">
                                                        @csrf
                                                        @method('DELETE')
                                                    </form>
                                                    <button type="button" class="btn btn-danger btn-sm"
                                                            onclick="confirmDelete('{{ $category->id }}', '{{ addslashes($category->name) }}', '{{ $category->id }}')"
                                                            title="Delete">
                                                        <i class="ti ti-trash fs-sm"></i>
                                                    </button>
                                                    @endcan
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            @if($category->description)
                                                <p class="text-muted mb-3">{{ $category->description }}</p>
                                            @endif
                                            
                                            @if($category->children->count() > 0)
                                                <h6 class="mb-2">Subcategories:</h6>
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach($category->children as $child)
                                                        <span class="badge bg-info-subtle text-info">
                                                            {{ $child->name }} ({{ $child->supplies->count() }})
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-muted mb-0">No subcategories</p>
                                            @endif
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-{{ $category->is_active ? 'success' : 'secondary' }}-subtle text-{{ $category->is_active ? 'success' : 'secondary' }}">
                                                    {{ $category->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                                <small class="text-muted">Sort: {{ $category->sort_order }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="text-muted">
                                <i class="ti ti-category-off fs-24 mb-2 d-block"></i>
                                No categories found. <a href="{{ route('inventory.categories.create') }}">Create the first category</a>.
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/custom-table.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        function confirmDelete(categoryId, categoryName, categoryId) {
            Swal.fire({
                title: 'Confirm Deletion',
                text: `Are you sure you want to delete the category "${categoryName}"? This will also delete all subcategories and unassign supplies from this category.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-primary me-2',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`delete-form-${categoryId}`).submit();
                }
            });
        }
    </script>
@endsection