@extends('layouts.vertical', ['title' => 'Expense Details'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Expenses', 'title' => 'Expense Details'])

    <div class="row">
        <div class="col-lg-8">
            <!-- Expense Details Card -->
            <div class="card">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">{{ $expense->expense_number }} - {{ $expense->title }}</h4>
                        <p class="text-muted mb-0">Created by {{ $expense->creator->name ?? 'System' }} on {{ $expense->created_at->format('d M, Y') }}</p>
                    </div>
                    <div>
                        @include('expenses.partials.status-badge', ['status' => $expense->status])
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Category</label>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-{{ $expense->category->color ?? 'secondary' }}-subtle text-{{ $expense->category->color ?? 'secondary' }} me-2">
                                        <i class="ti ti-{{ $expense->category->icon ?? 'category' }} fs-xs"></i>
                                    </span>
                                    <h5 class="mb-0">{{ $expense->category->name ?? 'Uncategorized' }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Expense Date</label>
                                <h5 class="mb-0">{{ $expense->expense_date->format('d M, Y') }}</h5>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Vendor</label>
                                <h5 class="mb-0">{{ $expense->vendor_name ?? 'N/A' }}</h5>
                                @if($expense->vendor_contact)
                                <small class="text-muted">{{ $expense->vendor_contact }}</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Due Date</label>
                                <h5 class="mb-0 {{ $expense->isOverdue() ? 'text-danger' : '' }}">
                                    {{ $expense->due_date->format('d M, Y') }}
                                    @if($expense->isOverdue())
                                    <span class="badge bg-danger ms-2">Overdue</span>
                                    @endif
                                </h5>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label text-muted">Amount</label>
                                <h3 class="mb-0">{{ $currency_symbol ?? '$' }}{{ number_format($expense->amount, 2) }}</h3>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label text-muted">Tax</label>
                                <h5 class="mb-0">{{ $currency_symbol ?? '$' }}{{ number_format($expense->tax_amount, 2) }}</h5>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label text-muted">Total</label>
                                <h3 class="mb-0 text-primary">{{ $currency_symbol ?? '$' }}{{ number_format($expense->total_amount, 2) }}</h3>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Payment Method</label>
                                <h5 class="mb-0">{{ ucfirst(str_replace('_', ' ', $expense->payment_method)) ?? 'Not specified' }}</h5>
                                @if($expense->payment_reference)
                                <small class="text-muted">Reference: {{ $expense->payment_reference }}</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Paid Date</label>
                                <h5 class="mb-0">
                                    @if($expense->paid_date)
                                        {{ $expense->paid_date->format('d M, Y') }}
                                    @else
                                        <span class="text-muted">Not paid yet</span>
                                    @endif
                                </h5>
                            </div>
                        </div>
                    </div>

                    @if($expense->description)
                    <div class="mb-3">
                        <label class="form-label text-muted">Description</label>
                        <div class="border rounded p-3 bg-light">
                            {{ $expense->description }}
                        </div>
                    </div>
                    @endif

                    @if($expense->notes)
                    <div class="mb-3">
                        <label class="form-label text-muted">Internal Notes</label>
                        <div class="border rounded p-3 bg-light">
                            {{ $expense->notes }}
                        </div>
                    </div>
                    @endif

                    @if($expense->is_recurring)
                    <div class="alert alert-info">
                        <i class="ti ti-repeat me-2"></i>
                        This is a recurring expense ({{ $expense->recurring_frequency }})
                        @if($expense->recurring_end_date)
                            until {{ $expense->recurring_end_date->format('d M, Y') }}
                        @endif
                    </div>
                    @endif

                    @if($expense->rejection_reason)
                    <div class="alert alert-danger">
                        <i class="ti ti-x me-2"></i>
                        <strong>Rejection Reason:</strong> {{ $expense->rejection_reason }}
                        @if($expense->rejected_at)
                            <br><small class="text-muted">Rejected on {{ $expense->rejected_at->format('d M, Y') }}</small>
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            <!-- Attachments Section -->
            @if($expense->attachments->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Attachments</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($expense->attachments as $attachment)
                        <div class="col-md-4 mb-3">
                            <div class="card border">
                                <div class="card-body text-center">
                                    <div class="avatar-lg mx-auto mb-3">
                                        <div class="avatar-title bg-soft-primary rounded-circle">
                                            <i class="ti ti-file-text fs-24"></i>
                                        </div>
                                    </div>
                                    <h6 class="fs-sm mb-1">{{ Str::limit($attachment->filename, 20) }}</h6>
                                    <p class="text-muted mb-2">{{ $attachment->getFormattedSize() }}</p>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('expenses.attachments.download', $attachment->id) }}" class="btn btn-sm btn-primary">
                                            <i class="ti ti-download me-1"></i> Download
                                        </a>
                                        @can('expenses.manage')
                                        <button type="button" class="btn btn-sm btn-light" onclick="deleteAttachment({{ $attachment->id }})">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Comments Section -->
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Comments & Timeline</h4>
                </div>
                <div class="card-body">
                    @if($expense->comments->count() > 0)
                        <div class="timeline">
                            @foreach($expense->comments->sortByDesc('created_at') as $comment)
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-0">{{ $comment->user->name }}</h6>
                                        <small class="text-muted">{{ $comment->created_at->format('d M, Y h:i A') }}</small>
                                    </div>
                                    <p class="mb-2">{{ $comment->comment }}</p>
                                    @if($comment->is_internal)
                                    <span class="badge bg-secondary">Internal</span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="ti ti-message-off fs-24 mb-2 d-block text-muted"></i>
                            <p class="text-muted">No comments yet.</p>
                        </div>
                    @endif

                    @can('expenses.view')
                    <div class="mt-4">
                        <form action="{{ route('expenses.comments.store', $expense->id) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="comment" class="form-label">Add Comment</label>
                                <textarea class="form-control" id="comment" name="comment" rows="3" placeholder="Add a comment..." required></textarea>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="is_internal" name="is_internal" value="1">
                                <label class="form-check-label" for="is_internal">
                                    Internal comment (visible only to staff)
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-send me-1"></i> Add Comment
                            </button>
                        </form>
                    </div>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Right Column - Actions & Timeline -->
        <div class="col-lg-4">
            <!-- Action Buttons -->
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Actions</h4>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @can('expenses.manage')
                            @if(!in_array($expense->status, ['paid', 'rejected']))
                            <a href="{{ route('expenses.edit', $expense->id) }}" class="btn btn-primary">
                                <i class="ti ti-edit me-1"></i> Edit Expense
                            </a>
                            @endif
                        @endcan

                        @can('expenses.approve')
                            @if($expense->status == 'pending')
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="ti ti-check me-1"></i> Approve Expense
                            </button>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="ti ti-x me-1"></i> Reject Expense
                            </button>
                            @endif
                        @endcan

                        @can('expenses.manage')
                            @if($expense->status == 'approved')
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                <i class="ti ti-cash me-1"></i> Mark as Paid
                            </button>
                            @endif
                        @endcan

                        @can('expenses.manage')
                            @if($expense->status != 'paid')
                            <form id="delete-form" action="{{ route('expenses.destroy', $expense->id) }}" method="POST" class="d-grid">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                                    <i class="ti ti-trash me-1"></i> Delete Expense
                                </button>
                            </form>
                            @endif
                        @endcan

                        <a href="{{ route('expenses.index') }}" class="btn btn-light">
                            <i class="ti ti-arrow-left me-1"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>

            <!-- Timeline/Audit Trail -->
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Status Timeline</h4>
                </div>
                <div class="card-body">
                    <div class="timeline timeline-simple">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Created</h6>
                                <small class="text-muted">{{ $expense->created_at->format('d M, Y h:i A') }}</small>
                                <p class="mb-0">by {{ $expense->creator->name ?? 'System' }}</p>
                            </div>
                        </div>

                        @if($expense->status == 'pending' && $expense->updated_at != $expense->created_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Submitted for Approval</h6>
                                <small class="text-muted">{{ $expense->updated_at->format('d M, Y h:i A') }}</small>
                            </div>
                        </div>
                        @endif

                        @if($expense->approved_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Approved</h6>
                                <small class="text-muted">{{ $expense->approved_at->format('d M, Y h:i A') }}</small>
                                <p class="mb-0">by {{ $expense->approver->name ?? 'System' }}</p>
                            </div>
                        </div>
                        @endif

                        @if($expense->paid_date)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Marked as Paid</h6>
                                <small class="text-muted">{{ $expense->paid_date->format('d M, Y') }}</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Modals -->
    @include('expenses.partials.approve-modal')
    @include('expenses.partials.reject-modal')
    @include('expenses.partials.payment-modal')
@endsection

@section('scripts')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        function confirmDelete() {
            Swal.fire({
                title: 'Confirm Deletion',
                text: 'Are you sure you want to delete this expense? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form').submit();
                }
            });
        }

        function deleteAttachment(attachmentId) {
            Swal.fire({
                title: 'Delete Attachment',
                text: 'Are you sure you want to delete this attachment?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/expenses/attachments/${attachmentId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json'
                        }
                    }).then(response => {
                        if (response.ok) {
                            location.reload();
                        } else {
                            Swal.fire('Error', 'Failed to delete attachment', 'error');
                        }
                    });
                }
            });
        }
    </script>
@endsection