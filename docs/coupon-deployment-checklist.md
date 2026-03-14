# Coupon System Deployment & Setup Guide

## Overview

This guide provides step‑by‑step instructions for deploying and configuring the Enterprise Coupon Management System in a Laravel POS environment. It covers installation, database migration, permission setup, configuration, and production rollout.

## Prerequisites

### System Requirements

- **Laravel** 10.x or higher
- **PHP** 8.1+
- **Database** MySQL 8.0+ / PostgreSQL 12+ / SQLite 3.35+ (for testing)
- **Composer** 2.5+
- **Node.js** 18+ (for frontend assets)

### Existing POS Installation

The coupon system is designed to integrate with the existing Laravel POS application. Ensure the following modules are already installed and functional:

- User authentication & role‑based permissions
- Sales module (Sale model, POS interface)
- Customer management
- Service/Product catalog
- Location management

## Installation Steps

### 1. Code Integration

The coupon system is already included in the codebase. If you are deploying a fresh instance:

```bash
# Clone the repository
git clone <repository-url>
cd rns-fixed-final

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install frontend dependencies
npm install --production
npm run build
```

### 2. Environment Configuration

Ensure your `.env` file contains the necessary database and cache settings:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password

CACHE_DRIVER=redis  # Recommended for production
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis  # For bulk coupon generation
```

### 3. Database Migration

Run the coupon‑specific migrations:

```bash
php artisan migrate --path=database/migrations/2026_03_08_114016_create_coupons_table.php
php artisan migrate --path=database/migrations/2026_03_08_114057_create_coupon_batches_table.php
php artisan migrate --path=database/migrations/2026_03_08_114128_create_customer_groups_table.php
php artisan migrate --path=database/migrations/2026_03_08_114129_create_customer_group_members_table.php
php artisan migrate --path=database/migrations/2026_03_08_114201_create_coupon_customer_groups_table.php
php artisan migrate --path=database/migrations/2026_03_08_114235_create_coupon_locations_table.php
php artisan migrate --path=database/migrations/2026_03_08_114305_create_coupon_products_table.php
php artisan migrate --path=database/migrations/2026_03_08_114336_create_coupon_categories_table.php
php artisan migrate --path=database/migrations/2026_03_08_114406_create_coupon_redemptions_table.php
php artisan migrate --path=database/migrations/2026_03_08_114439_create_sale_coupons_table.php
php artisan migrate --path=database/migrations/2026_03_08_114512_add_coupon_fields_to_sales_table.php
```

Or run all pending migrations:

```bash
php artisan migrate
```

**Verification:** After migration, verify the tables exist in your database:

```sql
SHOW TABLES LIKE '%coupon%';
```

### 4. Seed Initial Data (Optional)

Seed default customer groups and test coupons:

```bash
php artisan db:seed --class=CouponSeeder
```

The seeder creates:
- **Customer Groups**: “VIP”, “Students”, “First‑Time Buyers”
- **Sample Coupons**: TEST10 (10% off), WELCOME5 ($5 off), BOGOHAIR (buy one haircut get one free)
- **Sample Batch**: “Demo Batch” with 50 generated coupons

## Configuration

### Permission Setup

The coupon system relies on Laravel‑Spatie permissions. Ensure the following permissions exist in the `permissions` table:

| Permission | Guard | Description |
|------------|-------|-------------|
| `view coupons` | web | View coupon list and details |
| `create coupons` | web | Create new coupons |
| `edit coupons` | web | Edit existing coupons |
| `delete coupons` | web | Delete coupons |
| `manage system` | web | Full coupon management (includes all above) |
| `apply coupons` | web | Apply coupons at POS (implied by `view sales`) |
| `view reports` | web | Access coupon analytics |

**Assigning Permissions:**

1. Assign the `manage system` role to administrators who should manage coupons.
2. Assign the `view sales` role to POS staff (automatically grants `apply coupons`).

To verify permissions:

```bash
php artisan permission:show
```

### Role Configuration

If using the built‑in role system, ensure the following roles have appropriate permissions:

- **Super Admin** – `manage system`, `view reports`
- **Manager** – `view coupons`, `view reports`
- **Cashier** – `view sales` (implies `apply coupons`)

### Cache Configuration

For optimal performance, configure Redis or Memcached as cache driver. Update `config/cache.php`:

```php
'default' => env('CACHE_DRIVER', 'redis'),
```

Set the report cache duration (default 300 seconds) in `.env`:

```env
REPORT_CACHE_DURATION=300
```

### Queue Configuration

Bulk coupon generation uses Laravel queues. Configure a queue worker:

```bash
# Start queue worker
php artisan queue:work --queue=coupon_batches
```

For production, use Supervisor to keep the worker running.

## Integration with POS

### Sales Model Update

The migration `add_coupon_fields_to_sales_table` adds two columns to the `sales` table:

- `coupon_discount_amount` (decimal) – cumulative discount from coupons
- `applied_coupon_ids` (json) – array of coupon IDs applied

Verify the Sale model (`app/Models/Sale.php`) includes these fields in `$fillable` and `$casts`.

### POS Interface Update

Ensure the POS Blade template (`resources/views/pos/index.blade.php`) includes the coupon input section. If not present, merge the coupon component.

Check that the following JavaScript file is loaded:

```html
<script src="{{ asset('js/pages/pos.js') }}"></script>
```

And that it contains coupon validation logic.

### API Route Registration

Verify that coupon API routes are registered in `routes/web.php`:

```php
Route::post('/api/coupons/validate', [CouponController::class, 'validateCoupon']);
```

## Testing the Installation

### 1. Unit Tests

Run the coupon test suite:

```bash
php artisan test --filter=CouponTest
php artisan test --filter=CouponAdminTest
php artisan test --filter=CouponApiTest
```

All tests should pass.

### 2. Functional Testing

**Admin Interface:**
1. Log in as a user with `manage system` permission.
2. Navigate to **Marketing → Coupons**.
3. Create a new coupon.
4. Verify coupon appears in list.

**POS Interface:**
1. Log in as a cashier.
2. Create a test sale with items totaling above coupon minimum.
3. Apply the coupon code.
4. Verify discount is calculated correctly.
5. Complete the sale and check redemption record appears.

**Reporting:**
1. As admin, go to **Reports → Coupons**.
2. Verify data appears (may be empty initially).
3. Use date filters.

## Production Deployment Checklist

### Pre‑Deployment

- [ ] **Backup database** – Ensure you have a recent backup.
- [ ] **Review migration files** – Confirm no conflicts with existing schema.
- [ ] **Test in staging** – Full test of all coupon features.
- [ ] **Inform stakeholders** – Notify admin and POS staff of upcoming changes.

### Deployment Steps

1. **Merge code** into production branch.
2. **Run migrations** (`php artisan migrate --force`).
3. **Clear caches**:
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   php artisan cache:clear
   ```
4. **Restart queue workers** (if using queues).
5. **Restart PHP‑FPM / web server** (if necessary).

### Post‑Deployment Verification

- [ ] Admin coupon interface loads without errors.
- [ ] POS coupon field accepts input and validates.
- [ ] Sales with coupons complete successfully.
- [ ] Reports show correct data.
- [ ] No JavaScript errors in browser console.
- [ ] No errors in Laravel logs (`storage/logs/laravel.log`).

## Performance Tuning

### Database Indexes

The migrations already create indexes for common query patterns. For additional performance, consider:

```sql
CREATE INDEX idx_coupons_code_active ON coupons(code, active, start_date, end_date);
CREATE INDEX idx_coupon_redemptions_date ON coupon_redemptions(created_at, coupon_id);
```

### Cache Optimization

- Increase `REPORT_CACHE_DURATION` to 900 seconds (15 minutes) if real‑time data is not critical.
- Use Redis for both cache and session storage.

### Queue Optimization

For large‑scale bulk generation (>10,000 coupons), split batches into smaller chunks (max 2,000 per batch) to avoid timeout.

## Security Considerations

### Permission Auditing

Regularly audit user permissions to ensure only authorized personnel can create/modify coupons.

### Coupon Code Entropy

Ensure coupon codes are sufficiently random to prevent guessing. The bulk generation pattern `{RANDOM8}` provides ~2.8 trillion possibilities.

### SQL Injection Prevention

The system uses Eloquent ORM, which parameterizes queries. No additional measures needed.

### XSS Protection

Blade templates automatically escape output. Ensure any custom coupon fields (e.g., name, description) are displayed via `{{ }}`.

## Monitoring & Maintenance

### Daily Checks

- Review coupon redemption dashboard for unusual activity.
- Check queue worker status (if using bulk generation).
- Monitor Laravel logs for coupon‑related errors.

### Monthly Tasks

- Archive expired coupons (soft‑delete) to keep list manageable.
- Review customer group assignments for relevance.
- Update coupon patterns for upcoming campaigns.

### Backup Strategy

Include coupon tables in regular database backups:

```bash
mysqldump -u user -p database coupons coupon_batches coupon_redemptions sale_coupons > coupon_backup.sql
```

## Rollback Plan

If critical issues arise, follow this rollback procedure:

### 1. Revert Code
```bash
git revert <commit-hash>   # Revert coupon feature commits
```

### 2. Rollback Migrations
```bash
php artisan migrate:rollback --step=9   # Rollback the 9 coupon migrations
```

### 3. Restore Database Backup
If data corruption occurred, restore from pre‑deployment backup.

### 4. Notify Users
Inform staff that coupon feature is temporarily unavailable.

## Troubleshooting Common Deployment Issues

### Migration Errors

**Error:** `SQLSTATE[42000]: Syntax error or access violation`

**Cause:** Database version mismatch or duplicate migration.

**Solution:** Check migration SQL syntax; ensure migrations haven’t been run before.

### Permission Denied Errors

**Error:** `403 Forbidden` when accessing coupon pages.

**Cause:** User lacks `manage system` or `view coupons` permission.

**Solution:** Assign appropriate permissions via admin panel or database.

### POS Coupon Field Missing

**Cause:** POS template not updated.

**Solution:** Verify `pos/index.blade.php` includes coupon section. Compare with backup version.

### Bulk Generation Hangs

**Cause:** Queue worker not running or job timeout.

**Solution:** Start queue worker; check `failed_jobs` table; increase `queue.timeout` in `config/queue.php`.

### Report Data Not Updating

**Cause:** Cache not expiring.

**Solution:** Clear cache with `php artisan cache:clear` or wait for cache TTL.

## Support Resources

- **Internal Documentation** – [Coupon System Architecture](./coupon-system-architecture.md)
- **Developer Contact** – team@example.com
- **Issue Tracker** – GitHub Projects board
- **Emergency Hotline** – (for critical production issues)

## Appendices

### Appendix A: Database Schema Diagram

See architecture documentation for ER diagram.

### Appendix B: Sample .env Configuration

```env
# Coupon‑specific settings
COUPON_CODE_PREFIX=CPN
COUPON_DEFAULT_TIMEZONE=UTC
COUPON_MAX_BATCH_SIZE=10000
REPORT_CACHE_DURATION=300
```

### Appendix C: Required Composer Packages

- `spatie/laravel‑permission` (already required)
- `laravel/ui` (for frontend)
- `maatwebsite/excel` (for report exports)

---

*Document last updated: March 8, 2026*