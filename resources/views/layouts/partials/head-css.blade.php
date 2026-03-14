@yield('css')

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
