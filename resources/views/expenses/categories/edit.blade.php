@extends('layouts.vertical', ['title' => 'Edit Expense Category'])

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Expenses', 'title' => 'Edit Expense Category'])

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Category Details</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('expenses.categories.update', $category->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $category->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="parent_id" class="form-label">Parent Category</label>
                                <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
                                    <option value="">None (Root Category)</option>
                                    @foreach($parentCategories as $parent)
                                        <option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>
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
                                      id="description" name="description" rows="3">{{ old('description', $category->description) }}</textarea>
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
                                           id="icon" name="icon" value="{{ old('icon', $category->icon ?? 'category') }}" placeholder="e.g., wallet, car, home">
                                </div>
                                <small class="text-muted">Enter a Tabler icon name (without ti- prefix)</small>
                                @error('icon')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="color" class="form-label">Color</label>
                                <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror" 
                                       id="color" name="color" value="{{ old('color', $category->color ?? '#6366f1') }}" style="width: 100%;">
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
                                           id="budget_amount" name="budget_amount" value="{{ old('budget_amount', $category->budget_amount) }}">
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
                                        <option value="{{ $value }}" {{ old('budget_period', $category->budget_period) == $value ? 'selected' : '' }}>
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
                                       id="sort_order" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}">
                                @error('sort_order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                       {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active Status</label>
                            </div>
                            <small class="text-muted">Inactive categories won't be available for new expenses</small>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('expenses.categories.index') }}" class="btn btn-secondary">
                                <i class="ti ti-x me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-1"></i> Update Category
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
                    <div class="avatar-md rounded-circle mx-auto mb-3" id="preview-icon-wrapper" style="background-color: {{ $category->color ?? '#6366f1' }};">
                        <span class="avatar-title rounded-circle bg-transparent">
                            <i class="ti ti-{{ $category->icon ?? 'category' }} fs-24 text-white" id="preview-icon"></i>
                        </span>
                    </div>
                    <h5 class="mb-1" id="preview-name">{{ $category->name }}</h5>
                    <p class="text-muted small mb-0" id="preview-description">{{ $category->description ?: 'No description' }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Category Stats</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Expenses</span>
                        <span class="fw-semibold">{{ $category->expenses->count() ?? 0 }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Amount</span>
                        <span class="fw-semibold">{{ $currency_symbol ?? '$' }}{{ number_format($category->expenses->sum('total_amount') ?? 0, 2) }}</span>
                    </div>
                    @if($category->budget_amount)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Budget</span>
                        <span class="fw-semibold">{{ $currency_symbol ?? '$' }}{{ number_format($category->budget_amount, 2) }}/{{ $category->budget_period }}</span>
                    </div>
                    <div class="mt-3">
                        @php
                            $utilization = $category->getBudgetUtilization();
                            $progressColor = $utilization > 100 ? 'danger' : ($utilization > 75 ? 'warning' : 'success');
                        @endphp
                        <div class="d-flex justify-content-between mb-1">
                            <small>Budget Utilization</small>
                            <small>{{ number_format($utilization, 1) }}%</small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-{{ $progressColor }}" role="progressbar" 
                                 style="width: {{ min($utilization, 100) }}%"></div>
                        </div>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between mt-2">
                        <span class="text-muted">Created</span>
                        <span class="fw-semibold">{{ $category->created_at->format('M d, Y') }}</span>
                    </div>
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
                previewDesc.textContent = this.value || 'No description';
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
