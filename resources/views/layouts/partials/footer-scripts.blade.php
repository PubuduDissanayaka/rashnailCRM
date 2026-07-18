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