@php
    $faviconPath = \App\Models\Setting::get('business.favicon');
    $faviconUrl = $faviconPath
        ? Storage::url($faviconPath)
        : (file_exists(public_path('images/favicon.ico')) ? asset('images/favicon.ico') : asset('images/logo-sm.png'));
    $tagline = \App\Models\Setting::get('business.tagline') ?: config('app.name');
@endphp
<meta charset="utf-8" />
<title>{{ $title }} | {{ $tagline }}</title>
<meta content="width=device-width, initial-scale=1" name="viewport" />
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta
    content="UBold is a modern, responsive admin dashboard available on ThemeForest. Ideal for building CRM, CMS, project management tools, and custom web applications with a clean UI, flexible layouts, and rich features."
    name="description" />
<meta
    content="UBold, admin dashboard, ThemeForest, Bootstrap 5 admin, responsive admin, CRM dashboard, CMS admin, web app UI, admin theme, premium admin template"
    name="keywords" />
<meta content="Coderthemes" name="author" />
<!-- App favicon -->
<link href="{{ $faviconUrl }}" rel="shortcut icon" />
