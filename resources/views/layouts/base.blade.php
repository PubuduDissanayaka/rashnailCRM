<!DOCTYPE html>
<html lang="en">

<head>
    @include('layouts.partials/title-meta')

    @include('layouts.partials/head-css')
</head>

<body>
    @yield('content')

    @include('layouts.partials/footer-scripts')
</body>

</html>
