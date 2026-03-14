<div class="row">
    <!-- Left Column -->
    <div class="col-lg-8">
        <!-- Basic Information -->
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Basic Information</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="title" class="form-label">Expense Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $expense->title ?? '') }}" placeholder="Enter expense title" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $expense->category_id ?? '') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Enter expense description">{{ old('description', $expense->description ?? '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="vendor_name" class="form-label">Vendor Name</label>
                            <input type="text" class="form-control @error('vendor_name') is-invalid @enderror" id="vendor_name" name="vendor_name" value="{{ old('vendor_name', $expense->vendor_name ?? '') }}" placeholder="Enter vendor name">
                            @error('vendor_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="vendor_contact" class="form-label">Vendor Contact</label>
                            <input type="text" class="form-control @error('vendor_contact') is-invalid @enderror" id="vendor_contact" name="vendor_contact" value="{{ old('vendor_contact', $expense->vendor_contact ?? '') }}" placeholder="Email or phone number">
                            @error('vendor_contact')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Details -->
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Financial Details</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $currencySymbol ?? '$' }}</span>
                                <input type="number" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" step="0.01" min="0" value="{{ old('amount', $expense->amount ?? '') }}" placeholder="0.00" required>
                            </div>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="tax_amount" class="form-label">Tax Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $currencySymbol ?? '$' }}</span>
                                <input type="number" class="form-control @error('tax_amount') is-invalid @enderror" id="tax_amount" name="tax_amount" step="0.01" min="0" value="{{ old('tax_amount', $expense->tax_amount ?? 0) }}" placeholder="0.00">
                            </div>
                            @error('tax_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="total_amount" class="form-label">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $currencySymbol ?? '$' }}</span>
                                <input type="number" class="form-control @error('total_amount') is-invalid @enderror" id="total_amount" name="total_amount" step="0.01" min="0" value="{{ old('total_amount', $expense->total_amount ?? '') }}" placeholder="0.00" readonly>
                            </div>
                            <small class="text-muted">Auto-calculated (Amount + Tax)</small>
                            @error('total_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select class="form-select @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method">
                                <option value="">Select Method</option>
                                <option value="cash" {{ old('payment_method', $expense->payment_method ?? '') == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="card" {{ old('payment_method', $expense->payment_method ?? '') == 'card' ? 'selected' : '' }}>Credit/Debit Card</option>
                                <option value="check" {{ old('payment_method', $expense->payment_method ?? '') == 'check' ? 'selected' : '' }}>Check</option>
                                <option value="bank_transfer" {{ old('payment_method', $expense->payment_method ?? '') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="online" {{ old('payment_method', $expense->payment_method ?? '') == 'online' ? 'selected' : '' }}>Online Payment</option>
                            </select>
                            @error('payment_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="payment_reference" class="form-label">Payment Reference</label>
                            <input type="text" class="form-control @error('payment_reference') is-invalid @enderror" id="payment_reference" name="payment_reference" value="{{ old('payment_reference', $expense->payment_reference ?? '') }}" placeholder="Transaction ID or check number">
                            @error('payment_reference')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dates -->
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Dates</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="expense_date" class="form-label">Expense Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('expense_date') is-invalid @enderror" id="expense_date" name="expense_date" value="{{ old('expense_date', isset($expense->expense_date) ? $expense->expense_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                            @error('expense_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control @error('due_date') is-invalid @enderror" id="due_date" name="due_date" value="{{ old('due_date', isset($expense->due_date) ? $expense->due_date->format('Y-m-d') : '') }}">
                            @error('due_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="paid_date" class="form-label">Paid Date</label>
                            <input type="date" class="form-control @error('paid_date') is-invalid @enderror" id="paid_date" name="paid_date" value="{{ old('paid_date', isset($expense->paid_date) ? $expense->paid_date->format('Y-m-d') : '') }}" {{ isset($expense) && $expense->status == 'paid' ? '' : 'disabled' }}>
                            @error('paid_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-lg-4">
        <!-- Status & Actions -->
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">Status & Actions</h4>
            </div>
            <div class="card-body">
                @if(isset($expense) && in_array($expense->status, ['paid', 'rejected']))
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-circle me-2"></i>
                        This expense cannot be edited because it is {{ $expense->status }}.
                    </div>
                @endif

                <div class="mb-3">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" {{ isset($expense) && in_array($expense->status, ['paid', 'rejected']) ? 'disabled' : '' }}>
                        <option value="draft" {{ old('status', $expense->status ?? 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="pending" {{ old('status', $expense->status ?? '') == 'pending' ? 'selected' : '' }}>Pending Approval</option>
                        @if(isset($expense) && in_array($expense->status, ['approved', 'paid', 'rejected']))
                            <option value="{{ $expense->status }}" selected>{{ ucfirst($expense->status) }}</option>
                        @endif
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Recurring Expense -->
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring" value="1" {{ old('is_recurring', $expense->is_recurring ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_recurring">
                            This is a recurring expense
                        </label>
                    </div>
                </div>

                <div id="recurring_fields" style="display: {{ old('is_recurring', $expense->is_recurring ?? false) ? 'block' : 'none' }};">
                    <div class="mb-3">
                        <label for="recurring_frequency" class="form-label">Frequency</label>
                        <select class="form-select @error('recurring_frequency') is-invalid @enderror" id="recurring_frequency" name="recurring_frequency">
                            <option value="">Select Frequency</option>
                            <option value="weekly" {{ old('recurring_frequency', $expense->recurring_frequency ?? '') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="monthly" {{ old('recurring_frequency', $expense->recurring_frequency ?? '') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="quarterly" {{ old('recurring_frequency', $expense->recurring_frequency ?? '') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                            <option value="yearly" {{ old('recurring_frequency', $expense->recurring_frequency ?? '') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                        </select>
                        @error('recurring_frequency')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="recurring_end_date" class="form-label">End Date (Optional)</label>
                        <input type="date" class="form-control @error('recurring_end_date') is-invalid @enderror" id="recurring_end_date" name="recurring_end_date" value="{{ old('recurring_end_date', isset($expense->recurring_end_date) ? $expense->recurring_end_date->format('Y-m-d') : '') }}">
                        @error('recurring_end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- File Attachments -->
                <div class="mb-3">
                    <label class="form-label">Attachments</label>
                    <div class="file-upload">
                        <input type="file" class="form-control @error('attachments.*') is-invalid @enderror" id="attachments" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <small class="text-muted">Max 10MB per file. Allowed: PDF, JPG, PNG, DOC, DOCX</small>
                        @error('attachments.*')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if(isset($expense) && $expense->attachments->count() > 0)
                        <div class="mt-3">
                            <h6 class="fs-sm">Existing Attachments:</h6>
                            <ul class="list-unstyled">
                                @foreach($expense->attachments as $attachment)
                                <li class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <i class="ti ti-file-text me-2"></i>
                                        <a href="{{ route('expenses.attachments.download', $attachment->id) }}" class="text-primary">{{ $attachment->filename }}</a>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light" onclick="removeAttachment({{ $attachment->id }})">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <!-- Notes -->
                <div class="mb-3">
                    <label for="notes" class="form-label">Internal Notes</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3" placeholder="Internal notes (not visible to approvers)">{{ old('notes', $expense->notes ?? '') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="card">
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>
                        {{ isset($expense) ? 'Update Expense' : 'Create Expense' }}
                    </button>
                    <a href="{{ route('expenses.index') }}" class="btn btn-light">
                        <i class="ti ti-x me-1"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Toggle recurring fields
    document.getElementById('is_recurring').addEventListener('change', function() {
        document.getElementById('recurring_fields').style.display = this.checked ? 'block' : 'none';
    });

    // Auto-calculate total amount
    document.getElementById('amount').addEventListener('input', calculateTotal);
    document.getElementById('tax_amount').addEventListener('input', calculateTotal);

    function calculateTotal() {
        const amount = parseFloat(document.getElementById('amount').value) || 0;
        const tax = parseFloat(document.getElementById('tax_amount').value) || 0;
        const total = amount + tax;
        document.getElementById('total_amount').value = total.toFixed(2);
    }

    // Initialize calculation on page load
    document.addEventListener('DOMContentLoaded', calculateTotal);

    // Remove attachment function
    function removeAttachment(attachmentId) {
        Swal.fire({
            title: 'Remove Attachment',
            text: 'Are you sure you want to remove this attachment?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, remove it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Add hidden input to mark attachment for deletion
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'remove_attachments[]';
                input.value = attachmentId;
                document.querySelector('form').appendChild(input);
                
                // Remove from UI
                event.target.closest('li').remove();
            }
        });
    }
</script>
@endpush