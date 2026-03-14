# Coupon System Admin User Guide

## Introduction

This guide provides comprehensive instructions for administrators on managing the Enterprise Coupon Management System. You will learn how to create, edit, delete, and monitor coupons, generate bulk coupons, manage customer groups, and analyze coupon performance.

## Access Requirements

### Required Permissions
- **`manage system`** – Required for all coupon management operations
- **`view reports`** – Required for accessing coupon analytics

### Accessing the Coupon Module
1. Log in to the POS Admin Dashboard with an account that has `manage system` permission.
2. Navigate to **Marketing → Coupons** in the main sidebar.
3. You will be directed to the Coupon Management dashboard.

## Coupon Management

### Viewing All Coupons

The Coupon Index page (`/coupons`) displays all coupons in a searchable, paginated table.

**Features:**
- **Search** – Filter by coupon code, name, or description
- **Filters** – Filter by coupon type, status (active/expired), date range
- **Sorting** – Click column headers to sort
- **Bulk Actions** – Select multiple coupons for batch activation/deletion
- **Quick Stats** – Summary of total, active, expired, and redeemed coupons

**Actions per Coupon:**
- **View** – Click coupon code to see details
- **Edit** – Pencil icon to modify coupon
- **Delete** – Trash icon (soft delete, can be restored)
- **Copy** – Duplicate coupon with new code

### Creating a New Coupon

**Step‑by‑Step:**

1. Click **Create Coupon** button on the Coupon Index page.
2. Fill in the multi‑section form:

#### Section 1: Basic Information
- **Coupon Code** – Unique identifier (e.g., `SUMMER25`). Can be auto‑generated.
- **Name** – Descriptive name for internal reference.
- **Description** – Optional detailed description.

#### Section 2: Discount Settings
- **Coupon Type** – Select from:
  - **Percentage Discount** – Percentage off subtotal
  - **Fixed Amount** – Fixed currency amount off
  - **Buy X Get Y (BOGO)** – Buy certain quantity, get another free
  - **Free Shipping** – Waive shipping costs
  - **Tiered Discount** – Discount based on purchase amount tiers
- **Discount Value** – Percentage or fixed amount.
- **Maximum Discount** (for percentage coupons) – Cap on discount amount.
- **Minimum Purchase Amount** – Required subtotal for coupon to be valid.

#### Section 3: Validity & Limits
- **Start Date/Time** – When coupon becomes active.
- **End Date/Time** – When coupon expires (optional).
- **Timezone** – Timezone for date interpretation.
- **Total Usage Limit** – Maximum number of redemptions (leave empty for unlimited).
- **Per‑Customer Limit** – How many times a single customer can use this coupon (default 1).
- **Stackable** – Allow this coupon to be combined with other coupons.

#### Section 4: Restrictions
- **Location Restriction** – Choose:
  - **All Locations** – Coupon valid everywhere
  - **Specific Locations** – Select which locations can redeem
- **Customer Eligibility** – Choose:
  - **All Customers** – No restriction
  - **New Customers** – Only customers who joined within last 30 days with no prior purchases
  - **Existing Customers** – Only customers with at least one previous purchase
  - **Customer Groups** – Only members of selected customer groups
- **Product Restrictions** – Choose:
  - **All Products** – No restriction
  - **Specific Products** – Only selected products/services/packages
  - **Categories** – Only products from selected categories

#### Section 5: Advanced Settings
- **Metadata** – JSON field for custom data (e.g., tiered discount thresholds).
- **Batch Assignment** – Optionally assign coupon to a bulk generation batch.

3. Click **Save Coupon**.
4. The system validates all inputs and creates the coupon. You will be redirected to the coupon detail page.

### Editing a Coupon

1. From the Coupon Index, click the **Edit** icon for the desired coupon.
2. The edit form is pre‑populated with current values.
3. Make changes and click **Update Coupon**.

**Note:** Editing a coupon does not affect already‑redeemed coupons. Changes apply only to future redemptions.

### Deleting a Coupon

1. From the Coupon Index, click the **Delete** icon for the coupon.
2. Confirm deletion in the SweetAlert dialog.

**Important:** Coupons are soft‑deleted (remain in database with `deleted_at` timestamp). They cannot be redeemed after deletion but can be restored by a developer if needed.

### Viewing Coupon Details

Click a coupon code from the index to view its detail page (`/coupons/{id}`).

**Information Displayed:**
- All coupon attributes
- Redemption statistics (total redemptions, total discount given, unique customers)
- Recent redemptions table (last 10 redemptions with customer, sale, date, discount)
- Usage graph (redemptions per day for last 30 days)
- Customer eligibility and restriction summaries

## Bulk Coupon Generation

### Creating a Batch

Bulk generation allows you to create hundreds or thousands of coupons with a single pattern.

**Steps:**

1. Navigate to **Coupons → Bulk Generate** or click **Generate Bulk Coupons** on the Coupon Index.
2. Fill in the batch form:
   - **Batch Name** – Descriptive name (e.g., “Summer Sale 2026”)
   - **Description** – Optional notes
   - **Pattern** – Code pattern using placeholders:
     - `{RANDOM6}` – 6‑character random uppercase string
     - `{RANDOM8}` – 8‑character random uppercase string
     - `{DATE}` – Current date in YYYYMMDD format
     - `{TIME}` – Unix timestamp
     - Example: `SUMMER-{RANDOM6}-{DATE}`
   - **Number of Coupons** – How many unique codes to generate (max 10,000 per batch)
   - **Coupon Settings** – Default values for all coupons in the batch (same as single coupon creation)
3. Click **Generate Batch**.

### Monitoring Batch Progress

1. Navigate to **Coupons → Batches** to see all batches.
2. Each batch shows:
   - **Status** – Pending, Generating, Completed, Failed
   - **Progress Bar** – Percentage of coupons generated
   - **Count** – Total / generated
   - **Created Date**
3. Click a batch to view its detail page (`/coupons/batches/{id}`) with:
   - List of generated coupons (paginated)
   - Option to download CSV of all codes
   - Regeneration options for failed coupons

### Using Generated Coupons

Coupons generated in a batch inherit the batch’s settings. They appear in the main coupon list and can be managed individually.

## Customer Group Management

### Creating Customer Groups

Customer groups allow you to target coupons to specific customer segments.

1. Navigate to **Marketing → Customer Groups**.
2. Click **Create Customer Group**.
3. Provide:
   - **Name** – Group identifier (e.g., “VIP Customers”, “Students”)
   - **Description** – Purpose of the group
   - **Active** – Toggle to enable/disable
4. Click **Save**.

### Assigning Customers to Groups

Currently, customer‑group assignment is managed via:
- **Customer edit page** – Each customer profile has a “Group” dropdown.
- **CSV import** – Use the customer import feature with a `group_id` column.
- **API** – Programmatically assign via `PUT /api/customers/{id}`.

### Using Groups in Coupons

When creating/editing a coupon, select **Customer Eligibility → Customer Groups** and choose the groups that are eligible.

## Coupon Validation & Testing

### Manual Validation

Before publishing a coupon, you can test its validation using the **Test Coupon** tool:

1. Go to the coupon detail page.
2. Click **Test Validation** button.
3. Enter a sale subtotal, select a customer (optional), and add products.
4. Click **Validate** to see:
   - Whether the coupon would be valid
   - Detailed error messages if invalid
   - Calculated discount amount

### Automated Testing

The system includes a comprehensive test suite (`CouponTest`, `CouponAdminTest`) that validates all business rules. Run `php artisan test --filter=CouponTest` to ensure everything works.

## Reporting & Analytics

### Accessing Reports

Navigate to **Reports → Coupons** to view the coupon analytics dashboard.

### Available Reports

1. **Summary Dashboard**
   - Total coupons created
   - Active coupons
   - Redeemed coupons
   - Total discount given
   - Redemption rate
   - Average discount per redemption

2. **Redemption Analytics**
   - Daily redemption trend graph
   - Table of redemptions per day with discount totals

3. **Performance by Coupon Type**
   - Breakdown of redemptions and discount by type (percentage, fixed, etc.)

4. **Top Performing Coupons**
   - List of coupons sorted by redemptions or total discount given

5. **Usage by Period**
   - Aggregated data by day, week, or month

6. **Redemption by Location**
   - Geographic distribution of coupon usage

7. **Redemption by Customer Group**
   - Which customer segments are using coupons most

### Exporting Reports

Each report includes an **Export** button (CSV, PDF, Excel). Exports include filtered data only.

### Scheduling Reports

You can schedule automatic report delivery via:
1. **Settings → Notifications → Scheduled Reports**
2. Create a new scheduled report for “Coupon Redemption Summary”
3. Set frequency (daily, weekly, monthly) and recipients

## Best Practices

### Coupon Design

1. **Code Naming**
   - Use prefixes to identify campaigns (e.g., `SUMMER‑`, `VIP‑`)
   - Avoid ambiguous characters (0/O, 1/I/l)
   - Keep codes reasonably short for easy entry at POS

2. **Limits & Expiry**
   - Always set a total usage limit to control budget impact
   - Set end dates even for long‑running campaigns
   - Use per‑customer limits to prevent abuse

3. **Restrictions**
   - Use location restrictions for location‑specific promotions
   - Leverage customer groups for targeted marketing
   - Product restrictions can increase average ticket size

### Campaign Management

1. **Pre‑Launch Checklist**
   - [ ] Test coupon validation with various scenarios
   - [ ] Verify restrictions work as intended
   - [ ] Ensure POS staff are trained on how to apply the coupon
   - [ ] Communicate campaign details to all locations

2. **Monitoring During Campaign**
   - Check redemption dashboard daily
   - Watch for unexpected redemption patterns
   - Be prepared to deactivate coupons if issues arise

3. **Post‑Campaign Analysis**
   - Export final redemption reports
   - Calculate ROI (revenue impact vs. discount given)
   - Document lessons learned for future campaigns

## Common Tasks & How‑Tos

### How to Extend a Coupon’s Expiry Date

1. Edit the coupon
2. Update the **End Date** field
3. Save changes

**Note:** Already‑expired coupons will become active again if the new end date is in the future.

### How to Deactivate a Coupon Without Deleting It

1. Edit the coupon
2. Set **Active** toggle to “Off”
3. Save changes

Deactivated coupons will not appear in POS validation and cannot be redeemed.

### How to Allow More Uses of a Coupon

1. Edit the coupon
2. Increase **Total Usage Limit** (or set to empty for unlimited)
3. Save changes

The change takes effect immediately; existing redemptions count toward the new limit.

### How to Restrict a Coupon to Specific Products

1. Edit the coupon
2. Set **Product Restriction** to “Specific Products”
3. In the product selector, choose the eligible products/services/packages
4. Save changes

### How to Create a “First‑Time Customer” Coupon

1. Create a new coupon
2. Set **Customer Eligibility** to “New Customers”
3. Configure other settings (discount, limits, etc.)
4. Save

Only customers with no prior purchases will be able to redeem this coupon.

## Troubleshooting

### Coupon Not Working at POS

| Symptom | Possible Cause | Solution |
|---------|---------------|----------|
| “Coupon not found” | Code mistyped or coupon deleted | Verify code matches exactly; check coupon exists in admin |
| “Coupon expired” | End date has passed | Extend end date or create new coupon |
| “Minimum purchase not met” | Sale subtotal below minimum | Increase subtotal or lower minimum purchase amount |
| “Not valid for this location” | Location restriction mismatch | Adjust location restrictions or move sale to eligible location |
| “Already used maximum times” | Per‑customer limit reached | Increase limit or allow customer to use another coupon |
| “Cannot combine with other coupons” | Stackable = false & other coupons applied | Remove other coupons or make coupon stackable |

### Bulk Generation Issues

- **Slow generation** – Large batches (>5,000) may take minutes. Monitor progress on batch page.
- **Duplicate codes** – Pattern may produce collisions; system will retry up to 10 times per coupon.
- **Generation failed** – Check Laravel logs for errors; usually database connection issues.

### Report Data Discrepancies

- **Cached data** – Reports are cached for 5 minutes. Click **Refresh** to get latest.
- **Timezone mismatch** – Ensure report filters and coupon timezones align.
- **Missing redemptions** – Verify sale was completed (not voided) and coupon was properly applied.

## Support & Resources

### Internal Documentation
- [Coupon System Architecture](../docs/coupon-system-architecture.md)
- [API Documentation](../docs/coupon-api-documentation.md)
- [POS Staff Guide](../docs/coupon-pos-guide.md)

### Technical Support
- For system errors: Check `storage/logs/laravel.log`
- For bug reports: Create issue in project tracker
- For urgent issues: Contact development team

### Training Resources
- Video walkthroughs available in company learning portal
- Live training sessions scheduled monthly
- POS staff quick‑reference cards available for download

---

*Document last updated: March 8, 2026*