@extends('layouts.vertical', ['title' => 'Create Expense Category'])

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Expenses', 'title' => 'Create Expense Category'])

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Category Details</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('expenses.categories.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="parent_id" class="form-label">Parent Category</label>
                                <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
                                    <option value="">None (Root Category)</option>
                                    @foreach($parentCategories as $parent)
                                        <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                            {{ $parent->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('parent_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="icon" class="form-label">Icon</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ti ti-icons"></i></span>
                                    <input type="text" class="form-control @error('icon') is-invalid @enderror" 
                                           id="icon" name="icon" value="{{ old('icon', 'category') }}" placeholder="e.g., wallet, car, home">
                                </div>
                                <small class="text-muted">Enter a Tabler icon name (without ti- prefix)</small>
                                @error('icon')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="color" class="form-label">Color</label>
                                <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror" 
                                       id="color" name="color" value="{{ old('color', '#6366f1') }}" style="width: 100%;">
                                @error('color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="budget_amount" class="form-label">Budget Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $currency_symbol ?? '$' }}</span>
                                    <input type="number" step="0.01" min="0" 
                                           class="form-control @error('budget_amount') is-invalid @enderror" 
                                           id="budget_amount" name="budget_amount" value="{{ old('budget_amount') }}">
                                </div>
                                @error('budget_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="budget_period" class="form-label">Budget Period</label>
                                <select class="form-select @error('budget_period') is-invalid @enderror" id="budget_period" name="budget_period">
                                    <option value="">Select Period</option>
                                    @foreach($budgetPeriods as $value => $label)
                                        <option value="{{ $value }}" {{ old('budget_period') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('budget_period')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="sort_order" class="form-label">Sort Order</label>
                                <input type="number" min="0" class="form-control @error('sort_order') is-invalid @enderror" 
                                       id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}">
                                @error('sort_order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                       {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active Status</label>
                            </div>
                            <small class="text-muted">Inactive categories won't be available for new expenses</small>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('expenses.categories.index') }}" class="btn btn-secondary">
                                <i class="ti ti-x me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-1"></i> Create Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Preview</h4>
                </div>
                <div class="card-body text-center">
                    <div class="avatar-md rounded-circle mx-auto mb-3" id="preview-icon-wrapper" style="background-color: #6366f1;">
                        <span class="avatar-title rounded-circle bg-transparent">
                            <i class="ti ti-category fs-24 text-white" id="preview-icon"></i>
                        </span>
                    </div>
                    <h5 class="mb-1" id="preview-name">Category Name</h5>
                    <p class="text-muted small mb-0" id="preview-description">Category description will appear here</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Tips</h4>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="ti ti-check text-success me-2"></i>
                            Use clear, descriptive names
                        </li>
                        <li class="mb-2">
                            <i class="ti ti-check text-success me-2"></i>
                            Set budgets to track spending
                        </li>
                        <li class="mb-2">
                            <i class="ti ti-check text-success me-2"></i>
                            Use parent categories for grouping
                        </li>
                        <li>
                            <i class="ti ti-check text-success me-2"></i>
                            Icons: wallet, car, home, tools, etc.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('name');
            const descInput = document.getElementById('description');
            const iconInput = document.getElementById('icon');
            const colorInput = document.getElementById('color');
            
            const previewName = document.getElementById('preview-name');
            const previewDesc = document.getElementById('preview-description');
            const previewIcon = document.getElementById('preview-icon');
            const previewIconWrapper = document.getElementById('preview-icon-wrapper');

            nameInput.addEventListener('input', function() {
                previewName.textContent = this.value || 'Category Name';
            });

            descInput.addEventListener('input', function() {
                previewDesc.textContent = this.value || 'Category description will appear here';
            });

            iconInput.addEventListener('input', function() {
                previewIcon.className = `ti ti-${this.value || 'category'} fs-24 text-white`;
            });

            colorInput.addEventListener('input', function() {
                previewIconWrapper.style.backgroundColor = this.value;
            });
        });
    </script>
@endsection
