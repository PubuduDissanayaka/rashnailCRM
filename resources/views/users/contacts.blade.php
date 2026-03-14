@extends('layouts.vertical', ['title' => 'Contacts'])

@section('css')
@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => 'Apps', 'title' => 'Contacts'])

    <div class="row">
        <div class="col-lg-12">
            <form class="card border p-3">
                <div class="row gap-3">
                    <!-- Search Input -->
                    <div class="col-lg-4">
                        <div class="app-search">
                            <input class="form-control" placeholder="Search contact name..." type="text" id="searchContacts" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                    </div>
                    <div class="col">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="me-2 fw-semibold">Filter By:</span>
                            <!-- Role Filter -->
                            <div class="app-search">
                                <select class="form-select form-control my-1 my-md-0" id="filterRole">
                                    <option selected="">Role</option>
                                    <option value="administrator">Administrator</option>
                                    <option value="staff">Staff</option>
                                </select>
                                <i class="app-search-icon text-muted" data-lucide="user-check"></i>
                            </div>
                            <!-- Submit Button -->
                            <button class="btn btn-secondary" type="button" id="applyFilters">Apply</button>
                            <!-- Layout Toggle Buttons -->
                            <div aria-label="Layout toggle button group" class="ms-auto flex-shrink-0" role="group">
                                <input checked="" class="btn-check" id="btnradio1" name="btnradio" type="radio" />
                                <label class="btn btn-soft-primary btn-icon" for="btnradio1">
                                    <i class="fs-lg" data-lucide="layout-grid"></i>
                                </label>
                                <input class="btn-check" id="btnradio2" name="btnradio" type="radio" />
                                <label class="btn btn-soft-primary btn-icon" for="btnradio2">
                                    <i class="fs-lg" data-lucide="list"></i>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="row" id="contactsContainer">
        @foreach ($users as $user)
        <div class="col-md-6 col-xxl-3 contact-card" data-role="{{ $user->role }}" data-name="{{ strtolower($user->name) }}">
            <div class="card">
                <div class="card-body text-center">
                    <!-- Avatar -->
                    <img alt="avatar" class="rounded-circle" height="72"
                         src="{{ $user->avatar ? asset('storage/avatars/' . $user->avatar) : '/images/users/user-3.jpg' }}"
                         width="72" />
                    <!-- Name & Role -->
                    <h5 class="mb-0 mt-2 d-flex align-items-center justify-content-center">
                        <a class="link-reset" href="{{ route('profile.show', $user) }}">{{ $user->name }}</a>
                    </h5>
                    <!-- Role Badge -->
                    <span class="text-muted fs-xs">{{ ucfirst($user->role) }}</span><br />
                    <span class="badge bg-{{ $user->role === 'administrator' ? 'primary' : 'secondary' }} my-1">{{ ucfirst($user->role) }}</span><br />
                    <!-- Email -->
                    <span class="text-muted"><a class="text-decoration-none text-danger" href="mailto:{{ $user->email }}">{{ $user->email }}</a></span>
                    <!-- Buttons -->
                    <div class="mt-3">
                        <a href="{{ route('profile.show', $user) }}" class="btn btn-primary btn-sm me-1">View Profile</a>
                        @can('edit users')
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                        @endcan
                    </div>
                    <!-- Divider -->
                    <hr class="my-3 border-dashed" />
                    <!-- Stats - Placeholder -->
                    <div class="d-flex justify-content-between text-center">
                        <div>
                            <h5 class="mb-0">-</h5><span class="text-muted">Appointments</span>
                        </div>
                        <div>
                            <h5 class="mb-0">-</h5><span class="text-muted">Customers</span>
                        </div>
                        <div>
                            <h5 class="mb-0">-</h5><span class="text-muted">Sales</span>
                        </div>
                    </div>
                    <!-- Footer -->
                    <hr class="mt-3 border-dashed" />
                    <div class="text-end text-muted fs-xs"><i class="ti ti-clock me-1"></i> Joined {{ $user->created_at->format('M Y') }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div> <!-- end row-->
    <!-- Pagination would be implemented dynamically with JavaScript if needed -->
    <div class="d-flex justify-content-center mt-4">
        <nav aria-label="Contacts pagination">
            <ul class="pagination">
                <li class="page-item disabled">
                    <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                </li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item">
                    <a class="page-link" href="#">Next</a>
                </li>
            </ul>
        </nav>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchContacts');
            const filterRole = document.getElementById('filterRole');
            const applyBtn = document.getElementById('applyFilters');
            const contactCards = document.querySelectorAll('.contact-card');

            function filterContacts() {
                const searchTerm = searchInput.value.toLowerCase();
                const selectedRole = filterRole.value.toLowerCase();

                contactCards.forEach(card => {
                    const name = card.getAttribute('data-name');
                    const role = card.getAttribute('data-role');

                    const nameMatch = name.includes(searchTerm);
                    const roleMatch = selectedRole === 'role' || role === selectedRole;

                    if (nameMatch && roleMatch) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            searchInput.addEventListener('input', filterContacts);
            applyBtn.addEventListener('click', filterContacts);
            filterRole.addEventListener('change', function() {
                // Automatically apply filter when role is changed
                filterContacts();
            });
        });
    </script>
@endsection
