<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">Reject Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('expenses.reject', $expense->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="avatar-lg mx-auto mb-3">
                            <div class="avatar-title bg-danger-subtle text-danger rounded-circle">
                                <i class="ti ti-x fs-24"></i>
                            </div>
                        </div>
                        <h5>Reject this expense?</h5>
                        <p class="text-muted mb-0">
                            Expense <strong>{{ $expense->expense_number }}</strong> will be rejected and returned to the creator.
                        </p>
                    </div>

                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle me-2"></i>
                        Rejected expenses cannot be edited unless they are resubmitted as a new expense.
                    </div>

                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('rejection_reason') is-invalid @enderror" id="rejection_reason" name="rejection_reason" rows="4" placeholder="Please provide a reason for rejecting this expense..." required>{{ old('rejection_reason') }}</textarea>
                        @error('rejection_reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">This reason will be visible to the expense creator.</small>
                    </div>

                    <div class="mb-3">
                        <label for="rejection_notes" class="form-label">Internal Notes (Optional)</label>
                        <textarea class="form-control" id="rejection_notes" name="rejection_notes" rows="2" placeholder="Internal notes for staff only"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="ti ti-x me-1"></i> Reject Expense
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>