# Coupon System Troubleshooting Guide

## Introduction

This guide helps administrators, developers, and support staff diagnose and resolve issues with the Enterprise Coupon Management System. It covers common problems, error messages, log analysis, and step‑by‑step resolution procedures.

## How to Use This Guide

1. **Identify the symptom** (e.g., “Coupon not applying at POS”).
2. **Check the relevant section** below.
3. **Follow diagnostic steps** to pinpoint the cause.
4. **Apply recommended solutions**.
5. **Escalate** if problem persists.

## Quick Reference Table

| Symptom | Likely Cause | Section |
|---------|--------------|---------|
| Coupon not found at POS | Code mistyped, coupon deleted, or not active | [Validation Issues](#validation-issues) |
| “Minimum purchase not met” | Subtotal below coupon minimum | [Validation Issues](#validation-issues) |
| “Coupon expired” | End date passed or timezone mismatch | [Date & Time Issues](#date--time-issues) |
| “Usage limit reached” | Total or per‑customer limit exhausted | [Usage Limit Issues](#usage-limit-issues) |
| “Not valid for location” | Location restriction misconfigured | [Restriction Issues](#restriction-issues) |
| “Cannot combine coupons” | Stackable flag false | [Stackability Issues](#stackability-issues) |
| Bulk generation stuck | Queue worker down, job timeout | [Bulk Generation Issues](#bulk-generation-issues) |
| Reports showing old data | Cache not refreshed | [Reporting Issues](#reporting-issues) |
| Admin page errors 403/404 | Permission missing, route missing | [Permission & Access Issues](#permission--access-issues) |
| Database errors | Migration missing, foreign key violation | [Database Issues](#database-issues) |
| POS coupon field missing | Template not updated | [POS Integration Issues](#pos-integration-issues) |

## Validation Issues

### Coupon Not Found (Error: “Coupon not found.”)

**Possible Causes:**

1. **Code mismatch** – Coupon code entered incorrectly (case‑insensitive but exact spelling).
2. **Coupon deleted** – Soft‑deleted coupons are excluded from validation.
3. **Coupon inactive** – `active` flag is `false`.
4. **Date validity** – Coupon not yet started or already expired.

**Diagnostic Steps:**

1. **Check coupon existence:**
   ```bash
   php artisan tinker
   >>> App\Models\Coupon::where('code', 'COUPONCODE')->first();
   ```
   If `null`, coupon does not exist.

2. **Check active status:**
   ```bash
   >>> App\Models\Coupon::where('code', 'COUPONCODE')->where('active', true)->first();
   ```

3. **Check date validity:**
   ```bash
   >>> $c = App\Models\Coupon::where('code', 'COUPONCODE')->first();
   >>> $c->isActive();  // Should return true
   >>> $c->start_date->isPast();  // true
   >>> $c->isExpired();  // false
   ```

4. **Check soft deletes:**
   ```bash
   >>> App\Models\Coupon::withTrashed()->where('code', 'COUPONCODE')->first();
   ```

**Solutions:**

- **Correct code** – Verify code with customer/admin.
- **Reactivate coupon** – Edit coupon, set `active = true`.
- **Adjust dates** – Extend `end_date` or adjust `start_date`.
- **Restore deleted coupon** – Use `forceDelete()` and recreate, or restore from backup.

### Minimum Purchase Not Met

**Check:** Sale subtotal vs `minimum_purchase_amount`.

**Diagnostic:**
```bash
>>> $c = App\Models\Coupon::where('code', 'CODE')->first();
>>> $c->minimum_purchase_amount;
```

**Solutions:**
- Increase sale subtotal.
- Lower coupon’s minimum purchase amount (edit coupon).
- Override validation (not recommended; modify business rules).

### Customer Eligibility Errors

**Messages:**
- “Coupon is for new customers only.”
- “Coupon is for existing customers only.”
- “You are not eligible for this coupon.”

**Diagnostic:**

1. **Check customer’s status:**
   ```bash
   >>> $customer = App\Models\Customer::find(ID);
   >>> $customer->sales()->count();  // 0 = new customer
   >>> $customer->created_at->diffInDays(now());  // days since account creation
   ```

2. **Check customer group membership:**
   ```bash
   >>> $customer->group_id;
   >>> $coupon->customerGroups()->pluck('id');
   ```

**Solutions:**
- Assign customer to correct group.
- Change coupon eligibility type (if business rules allow).
- Explain eligibility requirements to customer.

## Date & Time Issues

### Coupon Showing as Expired When It Should Be Active

**Common Causes:**

1. **Timezone mismatch** – Coupon uses UTC, POS uses local time.
2. **End date inclusive/exclusive logic** – Coupon expires at 00:00 on end date.
3. **Server time incorrect** – System clock skewed.

**Diagnostic:**

1. **Compare times:**
   ```bash
   >>> $c = App\Models\oupon::find(ID);
   >>> echo $c->timezone;
   >>> echo $c->end_date->setTimezone($c->timezone);
   >>> echo now($c->timezone);
   ```

2. **Check Laravel timezone setting (`config/app.php`):**
   ```php
   'timezone' => 'UTC',
   ```

**Solutions:**

- Adjust coupon `timezone` to match location.
- Extend `end_date` by one day.
- Ensure server time synchronized (NTP).

### Start Date in Future

Coupon not active until start date. If you need it immediately, set `start_date` to current time.

## Usage Limit Issues

### Total Usage Limit Reached

**Message:** “Coupon usage limit reached.”

**Diagnostic:**
```bash
>>> $c = App\Models\Coupon::find(ID);
>>> $c->total_usage_limit;
>>> $c->redemptions()->count();
```

**Solutions:**
- Increase `total_usage_limit` (edit coupon).
- Create new coupon with same settings but new code.
- If limit mistakenly reached due to voided sales, manually delete redemption records (caution).

### Per‑Customer Limit Reached

**Message:** “You have already used this coupon the maximum number of times.”

**Diagnostic:**
```bash
>>> $c->per_customer_limit;
>>> $c->customerUsageCount($customer);
```

**Solutions:**
- Increase `per_customer_limit`.
- Create exception for VIP customers (manual override not supported; need custom logic).

## Restriction Issues

### Location Restrictions

**Message:** “Coupon is not valid for this location.”

**Diagnostic:**

1. **Check coupon location settings:**
   ```bash
   >>> $c->location_restriction_type;  // 'specific' or 'all'
   >>> $c->locations()->pluck('name');
   ```

2. **Verify sale location:**
   ```bash
   >>> $sale->location_id;
   ```

**Solutions:**
- Add location to coupon’s allowed locations.
- Change restriction type to “all”.
- Move sale to eligible location (if business allows).

### Product Restrictions

**Message:** “Coupon does not apply to any items in the sale.”

**Diagnostic:**

1. **Check restriction type:**
   ```bash
   >>> $c->product_restriction_type;  // 'specific', 'categories', 'all'
   ```

2. **List eligible products/categories:**
   ```bash
   >>> $c->products()->pluck('name');
   >>> $c->categories()->pluck('name');
   ```

3. **Compare with sale items.**

**Solutions:**
- Add missing products to coupon.
- Change restriction to “all” (if appropriate).
- Swap sale items for eligible ones.

## Stackability Issues

### “Cannot combine with other coupons”

**Cause:** Coupon has `stackable = false` and other coupons already applied.

**Diagnostic:**
```bash
>>> $c->stackable;  // false
>>> $sale->coupons()->count();  // >0
```

**Solutions:**
- Remove other coupons before applying this one.
- Edit coupon to make it stackable (if business allows).
- Inform customer they must choose one coupon.

## Bulk Generation Issues

### Batch Stuck in “Generating” Status

**Possible Causes:**

1. **Queue worker not running.**
2. **Job timed out.**
3. **Database deadlock.**
4. **Pattern collision** – unable to generate unique code after 10 attempts.

**Diagnostic:**

1. **Check queue worker status:**
   ```bash
   sudo supervisorctl status
   ```

2. **Check failed jobs table:**
   ```bash
   php artisan queue:failed
   ```

3. **Check Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Examine batch progress:**
   ```bash
   php artisan tinker
   >>> $b = App\Models\CouponBatch::find(ID);
   >>> $b->status;
   >>> $b->generated_count;
   >>> $b->remainingToGenerate();
   ```

**Solutions:**

- **Restart queue worker:**
  ```bash
  sudo supervisorctl restart laravel-worker
  ```
- **Retry failed job:**
  ```bash
  php artisan queue:retry all
  ```
- **Manually mark batch as failed and retry:**
  ```bash
  >>> $b->update(['status' => 'pending']);
  >>> // Then trigger generation via admin interface
  ```
- **Increase `QUEUE_TIMEOUT`** in `.env` (default 60 seconds) for large batches.

### Duplicate Coupon Codes

**Cause:** Pattern not random enough; collision occurred.

**Solution:** Use more entropy in pattern (e.g., `{RANDOM8}` instead of `{RANDOM6}`). System automatically retries up to 10 times per coupon; if still duplicate, batch fails.

## Reporting Issues

### Reports Showing Cached Data

**Symptom:** Report displays old numbers despite recent redemptions.

**Cause:** Reports are cached for performance (default TTL 300 seconds).

**Solution:**

1. **Clear cache:**
   ```bash
   php artisan cache:clear
   ```
2. **Wait** 5 minutes for cache to expire.
3. **Disable caching** temporarily by setting `REPORT_CACHE_DURATION=0` in `.env` (not recommended for production).

### Missing Redemptions in Report

**Possible Causes:**

1. **Date filter mismatch** – redemption outside selected range.
2. **Sale voided** – voided sales excluded from reports.
3. **Location filter** – redemptions at other locations filtered out.

**Diagnostic:**

1. **Check redemption record existence:**
   ```bash
   >>> App\Models\CouponRedemption::whereBetween('created_at', [$start, $end])->count();
   ```

2. **Check voided sales:**
   ```bash
   >>> App\Models\Sale::whereNotNull('voided_at')->whereHas('coupons')->count();
   ```

**Solutions:**
- Adjust date range.
- Include voided sales in report (requires code change).
- Remove location filter.

### Report Page Loading Slowly

**Cause:** Large date range with many redemptions; complex queries.

**Solutions:**
- Reduce date range.
- Use “Usage by Period” with weekly/monthly aggregation.
- Add database indexes (see Deployment Guide).
- Upgrade server resources.

## Permission & Access Issues

### Admin Page Returns 403 Forbidden

**Cause:** User lacks `manage system` or `view coupons` permission.

**Diagnostic:**

1. **Check user permissions:**
   ```bash
   php artisan tinker
   >>> $user = App\Models\User::find(ID);
   >>> $user->getAllPermissions()->pluck('name');
   ```

2. **Verify route middleware** in `routes/web.php` (should include `can:manage system`).

**Solutions:**
- Assign missing permission via admin panel or database.
- If permission exists, clear permission cache:
  ```bash
  php artisan permission:cache-reset
  ```

### POS Staff Cannot Apply Coupons

**Cause:** User lacks `view sales` permission (which implies `apply coupons`).

**Solution:** Assign `view sales` role to user.

## Database Issues

### Migration Errors

**Error:** `SQLSTATE[42S01]: Base table or view already exists`

**Cause:** Table already created by previous migration.

**Solution:**
```bash
php artisan migrate:status   # See which migrations have run
php artisan migrate:rollback --step=1   # Rollback last migration
php artisan migrate   # Run again
```

If stuck, manually drop table (caution) and rerun migration.

### Foreign Key Constraint Failures

**Error:** `Cannot add or update a child row: a foreign key constraint fails`

**Cause:** Referenced row missing (e.g., `batch_id` references non‑existent batch).

**Diagnostic:** Identify missing parent record.

**Solution:** Ensure referenced record exists, or set `batch_id` to `NULL`.

## POS Integration Issues

### Coupon Input Field Missing in POS Interface

**Cause:** POS Blade template not updated with coupon component.

**Solution:**

1. **Compare `resources/views/pos/index.blade.php` with known working version.**
2. **Ensure the following section exists:**
   ```blade
   <!-- Coupon Application -->
   <div class="card">
       <div class="card-body">
           <h5 class="card-title">Apply Coupon</h5>
           <div class="input-group">
               <input type="text" class="form-control" id="couponCode" placeholder="Enter coupon code">
               <button class="btn btn-primary" id="applyCoupon">Apply</button>
           </div>
           <div id="couponMessages"></div>
           <div id="appliedCoupons"></div>
       </div>
   </div>
   ```
3. **Check JavaScript inclusion:**
   ```blade
   <script src="{{ asset('js/pages/pos.js') }}"></script>
   ```

### Coupon Validation Not Triggering

**Cause:** JavaScript error or missing event listener.

**Diagnostic:**
1. Open browser developer console (F12).
2. Look for errors.
3. Check network tab for `/api/coupons/validate` request.

**Solutions:**
- Fix JavaScript errors.
- Ensure CSRF token included in request headers.
- Verify route exists (`php artisan route:list | grep validate`).

## Performance Issues

### Slow Coupon Validation

**Cause:** Complex restrictions (many product/location checks) without proper indexing.

**Solutions:**
- Add indexes on pivot tables (`coupon_products`, `coupon_locations`).
- Reduce number of restrictions per coupon.
- Cache validation results for same coupon/sale (not implemented).

### High Database Load During Bulk Generation

**Cause:** Inserting thousands of coupons in a single transaction.

**Solutions:**
- Split batch into smaller chunks (max 2,000 per batch).
- Use `dispatch` with delay between chunks.
- Increase database `innodb_buffer_pool_size`.

## Logging & Debugging

### Enabling Detailed Logging

Set `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

Check `storage/logs/laravel.log` for coupon‑related entries.

### Common Log Messages

| Log Entry | Meaning | Action |
|-----------|---------|--------|
| `Coupon validation failed: minimum purchase` | Business rule triggered | Normal operation |
| `Coupon not found: CODE` | Invalid code entered | Inform user |
| `Queue job CouponGenerationJob failed` | Bulk generation error | Check job exception |
| `SQLSTATE[23000]: Integrity constraint violation` | Database foreign key issue | Fix missing reference |

### Debugging with Tinker

Use Laravel Tinker to inspect data:

```bash
php artisan tinker
>>> $c = App\Models\Coupon::first();
>>> $c->redemptions;
>>> $c->isActive();
```

## Escalation Procedures

### When to Escalate to Development Team

- **Data corruption** – Coupon or redemption records missing/corrupted.
- **Security breach** – Unauthorized coupon generation or redemption.
- **System‑wide outage** – Coupon module causing POS failure.
- **Bug in core logic** – Discount calculation incorrect.

### Information to Provide

1. **Error message** (exact wording).
2. **Steps to reproduce**.
3. **Coupon code(s)** involved.
4. **Relevant logs** (snippet from `laravel.log`).
5. **Screenshot** of error (if UI related).

### Contact Channels

- **Slack:** #coupon‑support
- **Email:** coupons‑support@example.com
- **Phone:** Ext. 5555 (urgent only)

## Preventive Maintenance

### Weekly Checks

- Monitor redemption counts for unusual spikes.
- Review failed jobs queue.
- Verify backup of coupon tables.

### Monthly Checks

- Archive expired coupons (soft‑delete).
- Review customer group assignments.
- Update coupon patterns for upcoming campaigns.

### Quarterly Checks

- Audit permission assignments.
- Review database indexes and query performance.
- Update documentation based on new issues.

## FAQ

**Q: Can a coupon be used after it’s deleted?**
A: No, soft‑deleted coupons are excluded from validation. However, redemptions that occurred before deletion remain valid.

**Q: How do I change a coupon’s code?**
A: Edit the coupon and modify the `code` field. Ensure uniqueness.

**Q: Can I apply a coupon retroactively to a completed sale?**
A: No. Coupons must be applied at time of sale. Manual database insertion possible but not recommended.

**Q: Why does a coupon work at one location but not another?**
A: Check location restrictions; also verify each location has its own POS instance with same coupon database.

**Q: How can I export all coupon codes?**
A: Use the batch export feature (batch detail page) or query database: `SELECT code FROM coupons WHERE deleted_at IS NULL`.

---

*Document last updated: March 8, 2026*