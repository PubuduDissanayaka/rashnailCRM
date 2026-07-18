@yield('css')

<!-- Global Vendor CSS (via CDN — avoids Vite manifest issues) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker@3/daterangepicker.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@2/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-buttons-bs5@3/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-responsive-bs5@3/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-select-bs5@2/css/select.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-fixedcolumns-bs5@5/css/fixedColumns.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-fixedheader-bs5@4/css/fixedHeader.bootstrap5.min.css">
<!-- dropzone CSS bundled via Vite — CDN path returns wrong MIME type -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css">

<!-- SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<!-- Theme Config Js -->
@vite(['resources/js/config.js'])
@vite(['resources/scss/app.scss'])

@php $authBgImagePath = \App\Models\Setting::get('business.auth_bg_image') @endphp
@if($authBgImagePath)
<style>
    .card-side-img {
        background-image: url("{{ \Illuminate\Support\Facades\Storage::url($authBgImagePath) }}") !important;
    }
</style>
@endif
