# UBold v7.0 - Laravel Admin Template

## Project Overview

UBold v7.0 is a comprehensive Laravel-based admin dashboard template featuring a rich set of UI components, pages, and modules. The project uses Laravel 12.x framework with PHP 8.2+ and includes a wide variety of frontend libraries and UI components. It follows the standard Laravel skeleton application structure and implements a dynamic routing system that allows for modular page organization.

**Key Technologies:**
- **Backend:** Laravel 12.x with PHP 8.2+
- **Frontend Build:** Vite with npm/bun package management
- **CSS Framework:** Bootstrap 5.3+
- **JavaScript Libraries:** Extensive collection including Chart.js, DataTables, FullCalendar, and many more
- **Database:** Configured for multiple backends (settings in `config/database.php`)

**Main Features:**
- Complete admin dashboard template with multiple page layouts
- Rich set of UI components and widgets
- Chart visualization (ApexCharts, Chart.js)
- Data tables with advanced features (sorting, filtering, export)
- Calendar functionality
- Form components with validation
- File upload capabilities
- Maps integration (Leaflet, jsVectorMap)
- Responsive design with Bootstrap
- Multiple application modules (CRM, E-commerce, Email, etc.)

## Project Structure

```
UBold/
├── app/                    # Application source code
│   ├── Http/              # Controllers, middleware
│   ├── Models/            # Eloquent models
│   └── Providers/         # Service providers
├── bootstrap/             # Framework bootstrap files
├── config/                # Configuration files
├── database/              # Migrations, seeds, factories
├── public/                # Web root with assets
├── resources/             # Views, JS, SCSS
│   ├── js/                # JavaScript files
│   ├── scss/              # SCSS stylesheets
│   └── views/             # Blade templates
├── routes/                # Route definitions
├── storage/               # Compiled views, logs, cache
├── tests/                 # Test files
├── artisan                # CLI application
├── composer.json          # PHP dependencies
├── package.json           # Frontend dependencies
└── vite.config.js         # Vite build configuration
```

## UI Components and Page Locations

### View Directory Structure:
- `resources/views/dashboard/` - Main dashboard pages (index.blade.php, index-2.blade.php)
- `resources/views/ui/` - Individual UI components (buttons, cards, modals, etc.)
  - accordions.blade.php
  - alerts.blade.php
  - badges.blade.php
  - breadcrumb.blade.php
  - buttons.blade.php
  - cards.blade.php
  - carousel.blade.php
  - collapse.blade.php
  - colors.blade.php
  - dropdowns.blade.php
  - grid.blade.php
  - images.blade.php
  - links.blade.php
  - list-group.blade.php
  - modals.blade.php
  - notifications.blade.php
  - offcanvas.blade.php
  - pagination.blade.php
  - placeholders.blade.php
  - popovers.blade.php
  - progress.blade.php
  - scrollspy.blade.php
  - spinners.blade.php
  - tabs.blade.php
  - tooltips.blade.php
  - typography.blade.php
  - utilities.blade.php
  - videos.blade.php
- `resources/views/apps/` - Application modules
  - api-keys.blade.php
  - calendar.blade.php
  - chat.blade.php
  - file-manager.blade.php
  - social-feed.blade.php
- `resources/views/charts/` - Chart examples
  - apex-* (various ApexChart examples)
  - chartjs/ (Chart.js examples)
- `resources/views/tables/` - Table implementations
  - datatables-* (various DataTables examples)
  - static.blade.php
  - custom.blade.php
- `resources/views/form/` - Form components and examples
  - elements.blade.php
  - layouts.blade.php
  - validation.blade.php
  - text-editors.blade.php
  - and more
- `resources/views/pages/` - Static pages
  - coming-soon.blade.php
  - faq.blade.php
  - pricing.blade.php
  - sitemap.blade.php
  - and others
- `resources/views/layouts/` - Layout templates for the admin panel
  - Various layout options for different sections
- `resources/views/auth/` - Authentication pages
- `resources/views/ecommerce/` - E-commerce module pages
- `resources/views/crm/` - CRM module pages
- `resources/views/email/` - Email related pages
- `resources/views/maps/` - Map integration examples

## Building and Running

### Prerequisites
- PHP 8.2+
- Composer
- Node.js and npm/bun
- Database server (MySQL, PostgreSQL, SQLite, etc.)

### Setup
1. Install PHP dependencies:
   ```bash
   composer install
   ```

2. Install frontend dependencies:
   ```bash
   npm install
   # or if using bun:
   bun install
   ```

3. Create environment file:
   ```bash
   cp .env.example .env
   ```

4. Generate application key:
   ```bash
   php artisan key:generate
   ```

5. Configure database settings in `.env` file:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. Run database migrations:
   ```bash
   php artisan migrate
   ```

### Development Commands

**Development Server:**
```bash
npm run dev
# or
php artisan serve
```

**Build Assets for Production:**
```bash
npm run build
```

**Run Tests:**
```bash
composer test
# or
php artisan test
```

**Other Useful Commands:**
```bash
# Clear application cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Seed the database
php artisan db:seed

# Generate a new migration
php artisan make:migration CreateTableNameTable

# Generate a new controller
php artisan make:controller ControllerName
```

## Development Conventions

### Code Style
- PHP: PSR-4 autoloading standards, Laravel conventions
- JavaScript: Standard linting (Laravel Pint may be used)
- SCSS: Bootstrap integration with custom styles

### Configuration
- All environment-specific settings are in `.env` file
- Application configuration in `config/` directory
- Database migrations in `database/migrations/`
- Model factories in `database/factories/`
- Database seeds in `database/seeders/`

### Frontend Assets
- SCSS styles in `resources/scss/`
- JavaScript in `resources/js/`
- Compiled assets through Vite build process
- All frontend dependencies through NPM

### Routing
- Web routes defined in `routes/web.php`
- Uses a dynamic routing system via `RoutingController`
- Single segment routes map to view files in the respective directories
- Multi-segment routes map to nested view structures

## Key Frontend Libraries

The project includes many frontend libraries for enhanced functionality:

- **Charts:** ApexCharts, Chart.js
- **Data Tables:** DataTables with various plugins (buttons, responsive, select, etc.)
- **Form Components:** Select2, Choices.js, FilePond, Quill editor
- **Date/Time:** Flatpickr, DateRangePicker
- **Maps:** Leaflet, jsVectorMap
- **Icons:** Tabler Icons
- **UI Components:** SweetAlert2, TourGuideJS
- **Drag & Drop:** SortableJS
- **File Processing:** PDFMake, PDF.js

## Environment Variables

Key environment variables in the `.env` file:

- `APP_NAME` - Application name
- `APP_ENV` - Environment (local, production, etc.)
- `APP_KEY` - Encryption key
- `APP_DEBUG` - Debug mode
- `DB_*` - Database connection settings
- `MAIL_*` - Email configuration
- `CACHE_*` - Cache driver settings
- `SESSION_*` - Session configuration

## Testing

The application uses PHPUnit for testing. Tests are located in the `tests/` directory:
- `tests/Unit/` - Unit tests
- `tests/Feature/` - Feature/integration tests

To run tests:
```bash
php artisan test
```

## Deployment

For production deployment:
1. Run `npm run build` to compile production assets
2. Set `APP_ENV=production` and `APP_DEBUG=false`
3. Configure web server to point to `public/` directory
4. Set proper file permissions
5. Run `php artisan config:cache` to optimize configuration
6. Run `php artisan route:cache` to optimize routes (optional)
7. Run `php artisan view:cache` to cache views

## Security

- Use environment variables for sensitive data
- Validate and sanitize all user inputs
- Use Laravel's built-in CSRF protection
- Follow Laravel security best practices
- Keep dependencies updated
- Use HTTPS in production