@extends('layouts.vertical', ['title' => 'Appointments Calendar'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css', 'node_modules/choices.js/public/assets/styles/choices.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Appointments Calendar'])

    <!-- CSRF Token for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="d-flex mb-3 gap-1">
        <!-- Sidebar with filters and quick actions -->
        <div class="card h-100 mb-0 d-none d-lg-flex rounded-end-0">
            <div class="card-body">
                <button class="btn btn-primary w-100 btn-new-event">
                    <i class="ti ti-plus me-2 align-middle"></i>
                    Create New Appointment
                </button>

                <div id="external-events">
                    <p class="text-muted mt-2 fst-italic fs-xs mb-3">Drag and drop your event or click in the calendar</p>
                    @php
                        $eventColors = ['primary', 'secondary', 'success', 'danger', 'info', 'warning', 'dark'];
                    @endphp
                    @foreach($services as $index => $service)
                        @php
                            $color = $eventColors[$index % count($eventColors)];
                        @endphp
                        <div class="external-event fc-event bg-{{ $color }}-subtle text-{{ $color }} fw-semibold"
                            data-class="bg-{{ $color }}-subtle text-{{ $color }} border-{{ $color }}"
                            data-service-id="{{ $service->id }}">
                            <i class="ti ti-circle-filled me-2"></i>{{ $service->name }}
                        </div>
                    @endforeach
                </div>

                <!-- Staff Filter -->
                <div class="mb-3 mt-4">
                    <label class="form-label fw-semibold">Filter by Staff</label>
                    <select class="form-select" id="staffFilter">
                        <option value="">All Staff</option>
                        @foreach($staff as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Filter by Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Status Legend -->
                <div class="mt-4">
                    <p class="text-muted fw-semibold mb-2">Status Legend</p>
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-primary-subtle text-primary me-2" style="width: 20px; height: 20px;"></span>
                        <span class="fs-sm">Scheduled</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-warning-subtle text-warning me-2" style="width: 20px; height: 20px;"></span>
                        <span class="fs-sm">In Progress</span>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-success-subtle text-success me-2" style="width: 20px; height: 20px;"></span>
                        <span class="fs-sm">Completed</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-danger-subtle text-danger me-2" style="width: 20px; height: 20px;"></span>
                        <span class="fs-sm">Cancelled</span>
                    </div>
                </div>

                <!-- Today's Stats -->
                <div class="mt-4 pt-3 border-top">
                    <p class="text-muted fw-semibold mb-2">Today's Overview</p>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fs-sm">Total Appointments:</span>
                        <span class="fw-semibold" id="todayTotal">{{ $stats['total'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fs-sm">Completed:</span>
                        <span class="fw-semibold text-success" id="todayCompleted">{{ $stats['completed'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fs-sm">In Progress:</span>
                        <span class="fw-semibold text-warning" id="todayInProgress">{{ $stats['in_progress'] }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="fs-sm">Pending:</span>
                        <span class="fw-semibold text-primary" id="todayPending">{{ $stats['scheduled'] }}</span>
                    </div>
                </div>
            </div>
        </div> <!-- end card-->
        <div class="card h-100 mb-0 rounded-start-0 flex-grow-1 border-start-0">
            <div class="d-lg-none d-inline-flex card-header">
                <button class="btn btn-primary btn-new-event">
                    <i class="ti ti-plus me-2 align-middle"></i>
                    Create New Appointment
                </button>
            </div>
            <div class="card-body" data-simplebar="" data-simplebar-md="" style="height: calc(100% - 350px);">
                <div id="calendar"></div>
            </div> <!-- end card-body -->
        </div> <!-- end card-->
    </div> <!-- end row-->

    <!-- Modal Add/Edit Appointment -->
    <div class="modal fade" id="event-modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form class="needs-validation" id="event-form" name="event-form" novalidate="">
                    @csrf
                    <input type="hidden" id="appointment-id" name="id">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modal-title">Create Appointment</h4>
                        <button aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-2">
                                    <label class="control-label form-label" for="event-title">Appointment Title</label>
                                    <input class="form-control" id="event-title" name="title"
                                        placeholder="Insert Appointment Title" required="" type="text" />
                                    <div class="invalid-feedback">Please provide a valid appointment title</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-2">
                                    <label class="control-label form-label" for="event-status">Status</label>
                                    <select class="form-select" id="event-status" name="status" required="">
                                        <option value="">Select a status</option>
                                        <option value="scheduled" selected="">Scheduled</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a valid status</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="control-label form-label mb-0" for="event-customer">Customer</label>
                                        @can('create customers')
                                        <button type="button" class="btn btn-sm btn-soft-primary py-0 px-2" id="toggle-quick-customer" title="Add new customer">
                                            <i class="ti ti-user-plus fs-sm me-1"></i> New
                                        </button>
                                        @endcan
                                    </div>
                                    <select class="form-select" id="event-customer" name="customer_id" required="">
                                        <option value="">Select a customer</option>
                                        @foreach($customers ?? [] as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->full_name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Please select a valid customer</div>
                                </div>

                                {{-- Quick-add customer inline panel --}}
                                @can('create customers')
                                <div id="quick-customer-panel" class="card border border-primary-subtle bg-primary-subtle bg-opacity-10 p-3 mb-2 rounded-3" style="display:none!important;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-semibold fs-sm text-primary"><i class="ti ti-user-plus me-1"></i>Quick Add Customer</span>
                                        <button type="button" class="btn-close btn-close-sm" id="close-quick-customer" aria-label="Close"></button>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="text" class="form-control form-control-sm" id="qc-first-name" placeholder="First Name *" maxlength="100">
                                        </div>
                                        <div class="col-6">
                                            <input type="text" class="form-control form-control-sm" id="qc-last-name" placeholder="Last Name *" maxlength="100">
                                        </div>
                                        <div class="col-6">
                                            <input type="tel" class="form-control form-control-sm" id="qc-phone" placeholder="Phone *" maxlength="30">
                                        </div>
                                        <div class="col-6">
                                            <input type="email" class="form-control form-control-sm" id="qc-email" placeholder="Email (optional)">
                                        </div>
                                        <div class="col-12">
                                            <label for="qc-joined-date" class="form-label fs-xs mb-1 text-muted">Joined Date</label>
                                            <input type="date" class="form-control form-control-sm" id="qc-joined-date"
                                                   value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}">
                                        </div>
                                    </div>
                                    <div id="qc-error" class="text-danger fs-xs mt-1" style="display:none;"></div>
                                    <div class="mt-2 d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-sm" id="qc-save-btn">
                                            <i class="ti ti-check me-1"></i>Save & Select
                                        </button>
                                        <button type="button" class="btn btn-light btn-sm" id="close-quick-customer-2">Cancel</button>
                                    </div>
                                </div>
                                @endcan
                            </div>
                            <div class="col-12">
                                <div class="mb-2">
                                    <label class="control-label form-label" for="event-service">Service</label>
                                    <select class="form-select" id="event-service" name="service_id" required="">
                                        <option value="">Select a service</option>
                                        @foreach($services ?? [] as $service)
                                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Please select a valid service</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-2">
                                    <label class="control-label form-label" for="event-staff">Staff</label>
                                    <select class="form-select" id="event-staff" name="user_id" required="">
                                        <option value="">Select staff member</option>
                                        @foreach($staff as $member)
                                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Please select a valid staff</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-2">
                                    <label class="control-label form-label" for="event-date-time">Date & Time</label>
                                    <input class="form-control" id="event-date-time" name="appointment_date"
                                        type="datetime-local" required="" />
                                    <div class="invalid-feedback">Please provide a valid date and time</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-2">
                                    <label class="control-label form-label" for="event-notes">Notes</label>
                                    <textarea class="form-control" id="event-notes" name="notes" rows="3" placeholder="Add any special notes..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button class="btn btn-danger" id="btn-delete-event" type="button">Delete</button>
                            <button class="btn btn-light ms-auto" data-bs-dismiss="modal" type="button">Close</button>
                            <button class="btn btn-primary" id="btn-save-event" type="submit">Save</button>
                        </div>
                    </div>
                </form>
            </div>
            <!-- end modal-content-->
        </div>
        <!-- end modal dialog-->
    </div>
    <!-- end modal-->
@endsection

@section('scripts')
    @vite(['resources/js/pages/appointments-calendar.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js', 'node_modules/choices.js/public/assets/scripts/choices.min.js'])
    <script>
        // Initialize Choices.js for the dropdowns in the appointment modal
        // Business hours data (from settings)
        @php
            $businessHours = $business['business.hours'] ?? [
                'monday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                'tuesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                'wednesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                'thursday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                'friday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                'saturday' => ['open' => '10:00', 'close' => '16:00', 'closed' => false],
                'sunday' => ['open' => null, 'close' => null, 'closed' => true],
            ];
        @endphp
        window.businessHours = @json($businessHours);
        window.quickCustomerUrl = '{{ route('customers.quick-store') }}';
        window.csrfToken = '{{ csrf_token() }}';
    </script>
    <script>
    (function () {
        const panel   = document.getElementById('quick-customer-panel');
        const toggle  = document.getElementById('toggle-quick-customer');
        const closeBtn  = document.getElementById('close-quick-customer');
        const closeBtn2 = document.getElementById('close-quick-customer-2');
        const saveBtn = document.getElementById('qc-save-btn');
        const errEl   = document.getElementById('qc-error');

        if (!panel || !toggle) return;

        function showPanel() {
            panel.style.removeProperty('display');
            document.getElementById('qc-first-name').focus();
        }
        function hidePanel() {
            panel.style.display = 'none';
            ['qc-first-name','qc-last-name','qc-phone','qc-email'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            errEl.style.display = 'none';
        }

        toggle.addEventListener('click', showPanel);
        closeBtn?.addEventListener('click', hidePanel);
        closeBtn2?.addEventListener('click', hidePanel);

        saveBtn?.addEventListener('click', async function () {
            const firstName  = document.getElementById('qc-first-name').value.trim();
            const lastName   = document.getElementById('qc-last-name').value.trim();
            const phone      = document.getElementById('qc-phone').value.trim();
            const email      = document.getElementById('qc-email').value.trim();
            const joinedDate = document.getElementById('qc-joined-date')?.value || '';

            errEl.style.display = 'none';

            if (!firstName || !lastName || !phone) {
                errEl.textContent = 'First name, last name and phone are required.';
                errEl.style.display = 'block';
                return;
            }

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…';

            try {
                const resp = await fetch(window.quickCustomerUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ first_name: firstName, last_name: lastName, phone, email: email || undefined, joined_date: joinedDate || undefined }),
                });

                const data = await resp.json();

                if (!resp.ok || !data.success) {
                    const msgs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Failed to save customer.');
                    errEl.textContent = msgs;
                    errEl.style.display = 'block';
                    return;
                }

                // Add the new customer to the Choices.js dropdown and select it
                const customerSelect = document.getElementById('event-customer');
                const choicesInstance = customerSelect?._choices;

                if (choicesInstance) {
                    choicesInstance.setChoices([{ value: String(data.customer.id), label: data.customer.full_name, selected: true }], 'value', 'label', false);
                } else {
                    const opt = new Option(data.customer.full_name, data.customer.id, true, true);
                    customerSelect.add(opt);
                    customerSelect.value = data.customer.id;
                    customerSelect.dispatchEvent(new Event('change'));
                }

                hidePanel();

                // Show brief success toast
                if (window.Swal) {
                    Swal.fire({ icon: 'success', title: 'Customer added!', text: data.customer.full_name + ' has been created and selected.', timer: 2000, showConfirmButton: false, toast: true, position: 'top-end' });
                }
            } catch (e) {
                errEl.textContent = 'Network error. Please try again.';
                errEl.style.display = 'block';
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="ti ti-check me-1"></i>Save & Select';
            }
        });
    })();
    </script>
@endsection