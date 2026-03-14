# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

UBold v7.0 is a Laravel 12.x admin dashboard template with Bootstrap 5 frontend. This is a comprehensive UI kit with extensive charting, data tables, forms, and visualization components.

**Stack:**
- Backend: Laravel 12.x (PHP 8.2+)
- Frontend: Vite, Bootstrap 5.3+, jQuery
- Charts: ApexCharts, Chart.js
- Data Tables: DataTables.net with extensive plugins
- Forms: Select2, Choices.js, FilePond, Quill
- Maps: Leaflet, jsVectorMap

## Development Commands

### Setup
```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate
```

### Development
```bash
# Run all dev services (server, queue, logs, vite)
composer dev

# Or run individually:
php artisan serve      # Laravel dev server
npm run dev           # Vite dev server with HMR
php artisan queue:listen --tries=1    # Queue worker
php artisan pail --timeout=0          # Log viewer
```

### Building
```bash
npm run build         # Build production assets
```

### Testing
```bash
composer test         # Clear config and run PHPUnit tests
php artisan test      # Run PHPUnit tests directly
```

### Code Quality
```bash
# Laravel Pint is available for PHP formatting
vendor/bin/pint
```

## Architecture

### Routing System

**Convention-based routing** via `RoutingController`:
- Routes map directly to Blade view paths
- `/{first}` → `resources/views/{first}.blade.php`
- `/{first}/{second}` → `resources/views/{first}/{second}.blade.php`
- `/{first}/{second}/{third}` → `resources/views/{first}/{second}/{third}.blade.php`

**Example:** URL `/charts/apex-bar` renders `resources/views/charts/apex-bar.blade.php`

This means:
- **No controllers needed for most pages** - just create the view
- All routes defined in `routes/web.php` use `RoutingController`
- Add new pages by creating Blade templates in the correct directory structure

### Frontend Asset Pipeline

**Vite configuration** (`vite.config.js`):
- All CSS/SCSS and JS files must be explicitly listed in the `input` array
- Main app file: `resources/js/app.js` (imported on all pages)
- Page-specific JS: `resources/js/pages/*.js` (imported per-page)
- Styles: `resources/scss/app.scss` + various vendor CSS imports

**Adding new pages:**
1. Create Blade view: `resources/views/section/page.blade.php`
2. Create page JS (if needed): `resources/js/pages/section-page.js`
3. Add JS to `vite.config.js` input array
4. Import in Blade: `@vite(['resources/js/pages/section-page.js'])`

### JavaScript Architecture

**Main App (`resources/js/app.js`):**
- `App` class: Core UI functionality (sidebar, popovers, tooltips, form validation, counter, etc.)
- `LayoutCustomizer` class: Theme switching, layout options, sidebar behavior
- `Plugins` class: Flatpickr, TouchSpin plugins
- `I18nManager` class: Multi-language support
- All initialized on `DOMContentLoaded`

**Chart Helpers:**
- `CustomApexChart` class: ApexCharts wrapper with theme-aware colors
- `CustomChartJs` class: Chart.js wrapper with theme-aware colors
- Both auto-rerender on theme/skin changes via `MutationObserver`
- Use `ins(color-name)` helper to get CSS custom property values

**Page-specific scripts:**
- Located in `resources/js/pages/*.js`
- Initialize page-specific functionality (charts, datatables, etc.)
- Import chart helpers: `import { CustomApexChart, ins } from '../app.js'`

### Blade Layout System

**Base layout:** `resources/views/layouts/base.blade.php`

**Partials:**
- `layouts/partials/head-css.blade.php` - CSS imports
- `layouts/partials/topbar.blade.php` - Top navigation bar
- `layouts/partials/menu.blade.php` - Vertical sidebar menu
- `layouts/partials/horizontal-nav.blade.php` - Horizontal menu
- `layouts/partials/footer.blade.php` - Footer
- `layouts/partials/footer-scripts.blade.php` - JS imports
- `layouts/partials/customizer.blade.php` - Theme customizer panel

**Typical page structure:**
```blade
@extends('layouts.base')
@section('content')
    <!-- page content -->
@endsection
```

### View Structure

Pages organized by feature:
- `dashboard/` - Dashboard pages
- `apps/` - Apps (calendar, chat, file manager, api-keys)
- `auth/` - Authentication pages
- `charts/` - Chart demos (apex-*, chartjs/*)
- `crm/` - CRM pages
- `ecommerce/` - E-commerce pages
- `email/` - Email templates
- `error/` - Error pages
- `form/` - Form component demos
- `maps/` - Map demos
- `tables/` - DataTables demos

## Key Patterns

### Adding a Chart Page

1. Create view: `resources/views/charts/my-chart.blade.php`
2. Create script: `resources/js/pages/chart-my-chart.js`
```javascript
import { CustomApexChart, ins } from '../app.js';

new CustomApexChart({
    selector: '#my-chart',
    series: [...],
    options: () => ({
        chart: { type: 'bar', height: 350 },
        // ... options
    })
});
```
3. Add to `vite.config.js` input array
4. Import in view: `@vite(['resources/js/pages/chart-my-chart.js'])`

### Adding a DataTable

DataTables are heavily used with various plugins. Common pattern:
```javascript
import DataTable from 'datatables.net';
import 'datatables.net-responsive-bs5';

new DataTable('#myTable', {
    responsive: true,
    // ... options
});
```

### Theme-Aware Colors

Use the `ins()` helper for dynamic colors:
```javascript
import { ins } from '../app.js';

const color = ins('primary');        // Get --ins-primary
const rgbaColor = ins('primary-rgb', 0.5);  // Get rgba(--ins-primary-rgb, 0.5)
```

### Multi-Language Support

Translation files in `public/data/translations/{lang}.json`
- Use `data-lang="key.path"` on elements
- `I18nManager` auto-translates on load
- Current language stored in `sessionStorage.__UBOLD_LANG__`

## Important Conventions

1. **Don't add routes** - use the existing convention-based routing
2. **Always add new JS files to vite.config.js** - Vite won't bundle them otherwise
3. **Page-specific JS should be self-contained** - avoid tight coupling
4. **Use CustomApexChart/CustomChartJs wrappers** - ensures theme compatibility
5. **Follow existing directory structure** - keeps code organized
6. **Blade partials are for layout only** - page logic stays in views

## Configuration Files

- `config/app.php` - Application settings
- `config/database.php` - Database connections
- `config/auth.php` - Authentication
- `config/cache.php` - Cache drivers
- `config/queue.php` - Queue configuration
- `config/session.php` - Session settings

## Database

Standard Laravel migrations in `database/migrations/`:
- `0001_01_01_000000_create_users_table.php`
- `0001_01_01_000001_create_cache_table.php`
- `0001_01_01_000002_create_jobs_table.php`

Factories in `database/factories/`, seeders in `database/seeders/`.

## Assets Organization

**Public assets:** `public/`
- `public/data/` - JSON data files (translations, sample data)
- `public/plugins/` - Third-party plugins

**Source assets:** `resources/`
- `resources/scss/` - SCSS source files
- `resources/js/` - JavaScript source files
- `resources/js/maps/` - Map data files
- `resources/js/pages/` - Page-specific scripts

## Session Storage Keys

The app uses sessionStorage for preferences:
- `__UBOLD_CONFIG__` - Layout/theme configuration
- `__UBOLD_LANG__` - Selected language
- `__user_has_visited__` - First visit tracking
- `theme` - Theme mode selection

## Production Build

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Set in `.env`:
```
APP_ENV=production
APP_DEBUG=false
```
