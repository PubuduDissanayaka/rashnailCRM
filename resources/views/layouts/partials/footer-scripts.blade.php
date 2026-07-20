<!-- Global Vendor JS (via CDN — avoids Vite manifest issues) -->
<!-- jQuery first — required by daterangepicker, datatables, and other CDN plugins -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker@3/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker@3/daterangepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net@2/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@2/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-buttons@3/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-buttons-bs5@3/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-responsive@3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-select@2/js/dataTables.select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-fixedcolumns@5/js/dataTables.fixedColumns.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net-fixedheader@4/js/dataTables.fixedHeader.min.js"></script>
<!-- dropzone: bundled via Vite (npm package), CDN version returns wrong MIME type -->
<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.currencySymbol = "{{ $currencySymbol ?? '$' }}";
</script>
<!-- App js -->
@vite(['resources/js/app.js'])

@yield('scripts')

<!-- Global Quick Search -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.querySelector('.topbar-search');
    const resultsDiv = document.getElementById('search-results');
    if (!searchInput || !resultsDiv) return;

    let debounceTimer = null;

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const q = this.value.trim();
        if (q.length < 1) {
            resultsDiv.style.display = 'none';
            resultsDiv.innerHTML = '';
            return;
        }
        debounceTimer = setTimeout(function () {
            fetch('/search?q=' + encodeURIComponent(q), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                const items = data.results || [];
                if (items.length === 0) {
                    resultsDiv.innerHTML = '<div class="text-muted text-center py-2 fs-xs">No results found</div>';
                    resultsDiv.style.display = 'block';
                    return;
                }
                let html = '';
                items.forEach(function (item) {
                    html += '<a href="' + item.url + '" class="dropdown-item d-flex align-items-center gap-2 px-2 py-1 rounded">'
                        + '<i class="' + item.icon + ' fs-sm"></i>'
                        + '<div class="d-flex flex-column">'
                        + '<span class="fw-semibold fs-xs">' + item.label + '</span>'
                        + '<small class="text-muted">' + item.sub + '</small>'
                        + '</div>'
                        + '<span class="badge bg-light text-muted ms-auto fs-xxs">' + item.type + '</span>'
                        + '</a>';
                });
                resultsDiv.innerHTML = html;
                resultsDiv.style.display = 'block';
            })
            .catch(function () {
                resultsDiv.style.display = 'none';
            });
        }, 300);
    });

    // Hide on click outside
    document.addEventListener('click', function (e) {
        if (!searchInput.closest('#global-search')?.contains(e.target)) {
            resultsDiv.style.display = 'none';
        }
    });

    // Hide on Escape
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            resultsDiv.style.display = 'none';
            this.blur();
        }
    });
});
</script>