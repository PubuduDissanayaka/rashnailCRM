<!DOCTYPE html>
<html lang="en" @yield('html_attribute')>

<head>
    @include('layouts.partials/title-meta')

    @include('layouts.partials/head-css')
</head>

<body>
    <div class="wrapper">

        @include('layouts.partials/menu')

        <div class="content-page">

            <div class="container-fluid">

                @yield('content')

            </div>

            @include('layouts.partials/footer')

        </div>

    </div>

    @include('layouts.partials/customizer')

    @include('layouts.partials/footer-scripts')
</body>

</html>
