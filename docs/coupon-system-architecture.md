# Coupon System Architecture

## Overview

The Enterprise Coupon Management System is a comprehensive solution integrated into the Laravel POS application, providing robust coupon/voucher management capabilities. The system supports multiple coupon types, advanced rule engines, bulk generation, and real-time POS integration.

### Key Architectural Components

1. **Database Layer** - 9 dedicated migrations for coupon entities and relationships
2. **Model Layer** - Eloquent models with relationships and business logic
3. **Service Layer** - Core business logic for validation, discount calculation, and reporting
4. **Controller Layer** - Web controllers for admin management and API endpoints
5. **UI Layer** - Admin interface using UBold theme with DataTables and SweetAlert
6. **POS Integration** - Real-time coupon validation at checkout

## Database Schema

### Core Tables

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `coupons` | Master coupon data | `id`, `code`, `type`, `discount_value`, `start_date`, `end_date`, `usage_limits` |
| `coupon_batches` | Bulk coupon generation batches | `id`, `name`, `pattern`, `count`, `status`, `settings` |
| `customer_groups` | Customer segmentation for eligibility | `id`, `name`, `description`, `active` |
| `coupon_redemptions` | Track coupon usage | `id`, `coupon_id`, `sale_id`, `customer_id`, `discount_amount` |
| `sale_coupons` | Link coupons to sales | `id`, `sale_id`, `coupon_id`, `coupon_redemption_id` |
| `coupon_customer_groups` | Many-to-many: coupons ↔ customer groups | `coupon_id`, `customer_group_id` |
| `coupon_locations` | Many-to-many: coupons ↔ locations | `coupon_id`, `location_id` |
| `coupon_products` | Polymorphic: coupons ↔ products/services | `coupon_id`, `product_id`, `product_type` |
| `coupon_categories` | Polymorphic: coupons ↔ categories | `coupon_id`, `category_id`, `categorizable_type` |

### Schema Relationships

```
CouponBatch (1) → (many) Coupon
Coupon (1) → (many) CouponRedemption
Coupon (many) ↔ (many) CustomerGroup (via coupon_customer_groups)
Coupon (many) ↔ (many) Location (via coupon_locations)
Coupon (many) ↔ (many) Service/ServicePackage (via coupon_products)
Coupon (many) ↔ (many) ServicePackageCategory (via coupon_categories)
Sale (1) → (many) SaleCoupon
SaleCoupon (1) → (1) CouponRedemption
```

### Key Constraints

- Unique coupon codes enforced at database level
- Foreign key constraints with cascading deletes where appropriate
- Indexes on frequently queried columns (`active`, `start_date`, `end_date`, `type`, `batch_id`)
- JSON column for metadata storage (tiered discount rules, custom settings)

## Model Layer

### Coupon Model (`App\Models\Coupon`)

**Constants:**
- `TYPE_PERCENTAGE`, `TYPE_FIXED`, `TYPE_BOGO`, `TYPE_FREE_SHIPPING`, `TYPE_TIERED`
- `LOCATION_RESTRICTION_ALL`, `LOCATION_RESTRICTION_SPECIFIC`
- `CUSTOMER_ELIGIBILITY_ALL`, `CUSTOMER_ELIGIBILITY_NEW`, `CUSTOMER_ELIGIBILITY_EXISTING`, `CUSTOMER_ELIGIBILITY_GROUPS`
- `PRODUCT_RESTRICTION_ALL`, `PRODUCT_RESTRICTION_SPECIFIC`, `PRODUCT_RESTRICTION_CATEGORIES`

**Relationships:**
- `batch()` → `CouponBatch`
- `redemptions()` → `CouponRedemption`
- `customerGroups()` → `CustomerGroup` (many-to-many)
- `locations()` → `Location` (many-to-many)
- `products()` → polymorphic to `Service`/`ServicePackage`
- `categories()` → polymorphic to `ServicePackageCategory`
- `saleCoupons()` → `SaleCoupon`

**Scopes:**
- `scopeActive()` - Active coupons within date range
- `scopeExpired()` - Expired but still active coupons
- `scopeValidForCustomer()` - Basic customer validation

**Helper Methods:**
- `isExpired()`, `isActive()`
- `remainingUses()`, `hasRemainingUses()`
- `customerUsageCount()`, `canBeUsedByCustomer()`

### CouponBatch Model (`App\Models\CouponBatch`)

**Constants:**
- `STATUS_PENDING`, `STATUS_GENERATING`, `STATUS_COMPLETED`, `STATUS_FAILED`

**Relationships:**
- `coupons()` → `Coupon`

**Helper Methods:**
- `isCompleted()`, `remainingToGenerate()`, `progressPercentage()`

### Other Models

- `CouponRedemption` - Tracks each redemption with customer, sale, and discount details
- `CustomerGroup` - Customer segmentation for targeted coupon distribution
- `SaleCoupon` - Links coupons to sales for reporting and discount calculation

## Service Layer

### CouponService (`App\Services\CouponService`)

**Core Methods:**

1. **`validate(Coupon $coupon, Sale $sale, ?Customer $customer)`**
   - Validates 8 validation rules:
     1. Active status and date validity
     2. Total usage limit
     3. Per-customer limit
     4. Minimum purchase amount
     5. Location restrictions
     6. Customer eligibility (new/existing/groups)
     7. Product restrictions (specific products/categories)
     8. Stackability with other coupons

2. **`calculateDiscount(Coupon $coupon, float $subtotal, array $items)`**
   - Handles all coupon types:
     - **Percentage**: `subtotal * (discount_value / 100)` with optional max cap
     - **Fixed**: Fixed amount deduction (capped at subtotal)
     - **BOGO**: Buy X Get Y logic (placeholder implementation)
     - **Free Shipping**: Zero shipping cost (placeholder)
     - **Tiered**: Multi‑threshold discounts defined in metadata

3. **`applyCoupon(Sale $sale, string $code, ?Customer $customer)`**
   - Validates coupon, calculates discount, creates redemption and sale‑coupon records
   - Updates sale totals atomically within a database transaction

4. **`removeCoupon(Sale $sale, Coupon $coupon)`**
   - Removes coupon from sale and recalculates totals

5. **`generateBulkCoupons(CouponBatch $batch)`**
   - Generates multiple coupons from a pattern (`{RANDOM6}`, `{DATE}`, etc.)

6. **`getRedemptionStats(Coupon $coupon)`**
   - Returns redemption counts, total discount, unique customers, and time‑based metrics

7. **`getAvailableCouponsForCustomer(Customer $customer, ?Location $location)`**
   - Filters active coupons by location and customer eligibility

### CouponReportService (`App\Services\CouponReportService`)

**Reporting Methods:**

- `getSummaryStats()` – High‑level dashboard metrics
- `getRedemptionAnalytics()` – Daily redemption trends
- `getPerformanceByType()` – Performance by coupon type
- `getTopPerformingCoupons()` – Top coupons by redemptions/discount
- `getUsageByPeriod()` – Aggregated usage by day/week/month
- `getRedemptionByLocation()` – Geographic distribution
- `getRedemptionByCustomerGroup()` – Segmentation analysis

**Caching Strategy:**
- All reports cached with configurable TTL (default: 5 minutes)
- Cache keys incorporate filter criteria to ensure data freshness

## Controller Layer

### CouponController (`App\Http\Controllers\CouponController`)

**Web Routes (protected by `manage system` permission):**

| Route | Method | Description |
|-------|--------|-------------|
| `GET /coupons` | `index()` | List all coupons with pagination |
| `GET /coupons/create` | `create()` | Show coupon creation form |
| `POST /coupons` | `store()` | Create new coupon |
| `GET /coupons/{coupon}` | `show()` | Show coupon details |
| `GET /coupons/{coupon}/edit` | `edit()` | Edit coupon form |
| `PUT /coupons/{coupon}` | `update()` | Update coupon |
| `DELETE /coupons/{coupon}` | `destroy()` | Delete coupon |
| `GET /coupons/bulk/create` | `createBulk()` | Bulk generation form |
| `POST /coupons/bulk/generate` | `generateBulk()` | Generate bulk coupons |
| `GET /coupons/batches` | `batches()` | List coupon batches |
| `GET /coupons/batches/{batch}` | `showBatch()` | Show batch details |

**API Routes:**

| Route | Method | Description |
|-------|--------|-------------|
| `POST /api/coupons/validate` | `validateCoupon()` | Validate coupon for POS checkout |
| `GET /api/coupons/report` | `report()` | Basic coupon report data |

### CouponReportController (`App\Http\Controllers\CouponReportController`)

**Reporting Routes:**

| Route | Method | Description |
|-------|--------|-------------|
| `GET /reports/coupons` | `index()` | Main reporting dashboard |
| `GET /api/reports/coupons/redemption-analytics` | `redemptionAnalytics()` | JSON redemption analytics |
| `GET /api/reports/coupons/performance-by-type` | `performanceByType()` | Performance by coupon type |
| `GET /api/reports/coupons/usage-by-period` | `usageByPeriod()` | Usage aggregated by period |
| `GET /api/reports/coupons/top-coupons` | `topCoupons()` | Top performing coupons |
| `GET /reports/coupons/export/{type}` | `export()` | Export reports (CSV/PDF) |

## UI Layer

### Admin Interface Components

**Views Location:** `resources/views/admin/coupons/`

- `index.blade.php` – DataTable listing with search, filters, and bulk actions
- `create.blade.php` – Multi‑step coupon creation form with conditional fields
- `edit.blade.php` – Similar to create, pre‑populated with existing data
- `show.blade.php` – Detail view with redemption history and statistics
- `bulk.blade.php` – Bulk coupon generation interface
- `batches.blade.php` – Batch listing with progress indicators
- `batch_show.blade.php` – Batch details and generated coupons

**UI Technologies:**
- **UBold Theme** – Consistent admin interface
- **DataTables** – Interactive tables with sorting, filtering, pagination
- **SweetAlert2** – Confirmation dialogs and notifications
- **Choices.js** – Advanced select inputs for multi‑selection
- **Flatpickr** – Date/time pickers for date ranges

### POS Integration Interface

**POS View:** `resources/views/pos/index.blade.php`

- Coupon input field with real‑time validation
- Applied coupons display with discount breakdown
- Error messages for invalid coupons
- Stackability indicators

## Integration with POS System

### Sales Model Extension

The `Sale` model includes new columns:
- `coupon_discount_amount` – Total discount from all applied coupons
- `applied_coupon_ids` – JSON array of coupon IDs applied to the sale

### Checkout Flow Integration

1. **Coupon Input** – Staff enters coupon code in POS interface
2. **Validation** – AJAX call to `/api/coupons/validate` returns validation result
3. **Application** – If valid, coupon is applied via `CouponService::applyCoupon()`
4. **Total Update** – Sale totals recalculated in real‑time
5. **Redemption Tracking** – `CouponRedemption` record created with audit trail

### Atomic Operations

All coupon applications and removals are wrapped in database transactions to ensure data consistency across `sales`, `coupon_redemptions`, and `sale_coupons` tables.

## Security & Permissions

### Role‑Based Access Control

- **`manage system` permission** required for all coupon management operations
- **`view sales` permission** required for POS coupon application
- Permission checks implemented via Laravel Policies and controller `authorize()` calls

### Validation Safeguards

- SQL injection prevented via Eloquent ORM and parameterized queries
- XSS protection via Blade templating engine
- CSRF protection on all form submissions
- Rate limiting on API endpoints

## Performance Considerations

### Database Indexing

- Composite index on `(active, start_date, end_date)` for efficient active coupon queries
- Foreign key indexes on all relationship columns
- Full‑text index on `code` and `name` for search operations

### Caching Strategy

- Report data cached with TTL configurable via `cache.report_cache_duration`
- Cache keys include filter parameters to prevent stale data
- Cache invalidation on coupon creation/redemption

### Query Optimization

- Eager loading of relationships (`with()`) to avoid N+1 queries
- Pagination on all list views (20 items per page)
- Selective column selection in reports to reduce memory usage

## Extension Points

### Custom Coupon Types

New coupon types can be added by:
1. Extending the `coupons.type` enum in migration
2. Adding constant to `Coupon` model
3. Implementing calculation logic in `CouponService::calculateDiscount()`
4. Adding validation rules in `CouponService::validate()`

### Additional Restriction Types

The system supports adding new restriction types via:
- New `*_restriction_type` enum values
- Corresponding pivot tables
- Validation logic in dedicated service methods

### Webhook Integration

Coupon redemptions can trigger webhooks by:
1. Listening to the `CouponRedeemed` event (if implemented)
2. Dispatching jobs to external systems
3. Integrating with marketing automation platforms

---

*Document last updated: March 8, 2026*