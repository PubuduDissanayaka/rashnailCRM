# Coupon Reporting & Analytics Guide

## Introduction

The Coupon Reporting module provides comprehensive analytics on coupon performance, redemption patterns, and revenue impact. This guide explains how to access, interpret, and use these reports to optimize your coupon campaigns.

## Accessing Reports

### Navigation
1. Log in as an administrator with **`view reports`** permission.
2. Go to **Reports → Coupons** in the main sidebar.
3. You will land on the **Coupon Analytics Dashboard**.

### Date Range Selection

All reports are filtered by a date range:
- **Default range**: Last 30 days
- **Custom range**: Use the date picker in the top-right corner
- **Quick ranges**: Today, Yesterday, Last 7 Days, Last 30 Days, This Month, Last Month

**Important:** The date range applies to **redemption date** (when coupon was used), not coupon creation date.

## Report Types

### 1. Summary Dashboard

**Location:** Main dashboard page (`/reports/coupons`)

**Metrics Displayed:**

| Metric | Description | Business Insight |
|--------|-------------|------------------|
| **Total Coupons** | Number of coupons created in period | Campaign volume |
| **Active Coupons** | Coupons currently active (within date range) | Live promotions |
| **Redeemed Coupons** | Number of unique coupon redemptions | Engagement level |
| **Total Discount** | Sum of all discount amounts given | Cost of promotion |
| **Sales with Coupons** | Number of sales that used at least one coupon | Penetration rate |
| **Revenue Impact** | Total discount amount (same as Total Discount) | Direct cost |
| **Redemption Rate** | (Redeemed Coupons / Total Coupons) × 100 | Effectiveness |
| **Avg Discount per Redemption** | Total Discount / Redeemed Coupons | Average discount value |

**Visualizations:**
- **Sparkline chart** of daily redemptions over the period
- **Donut chart** of coupon types redeemed
- **Bar chart** of top 5 coupons by redemptions

### 2. Redemption Analytics

**Access:** Click **Redemption Analytics** tab or go directly to `/api/reports/coupons/redemption-analytics` (JSON).

**Data Presented:**
- Daily breakdown of redemptions, total discount, unique coupons, unique customers.
- Table with columns: Date, Redemptions, Total Discount, Unique Coupons, Unique Customers.
- Line chart showing redemption trend over time.

**Use Cases:**
- Identify peak redemption days (e.g., weekends, holidays).
- Monitor campaign launch impact.
- Detect unusual spikes or drops.

### 3. Performance by Coupon Type

**Access:** Click **Performance by Type** tab.

**Breakdown:**
- **Percentage Discount**
- **Fixed Amount**
- **BOGO**
- **Free Shipping**
- **Tiered Discount**

**Metrics per Type:**
- Total coupons of that type
- Active coupons
- Expired coupons
- Redeemed coupons
- Total discount given

**Insights:**
- Which coupon type drives most redemptions?
- Which type gives highest average discount?
- Compare redemption rates across types.

### 4. Top Performing Coupons

**Access:** Click **Top Coupons** tab.

**Lists:**
- **By Redemptions** – Coupons with highest number of uses.
- **By Total Discount** – Coupons that gave largest total discount amount.

**Columns:**
- Coupon Code, Name, Type, Discount Value, Redemption Count, Total Discount Given, Avg Discount per Redemption.

**Actions:**
- Click coupon code to view its detail page.
- Export list to CSV for further analysis.

### 5. Usage by Period

**Access:** Click **Usage by Period** tab.

**Aggregation Levels:**
- **Day** – Daily totals
- **Week** – Week‑of‑year totals (ISO week)
- **Month** – Monthly totals

**Metrics per Period:**
- Redemptions
- Total Discount
- Unique Coupons
- Unique Customers
- Unique Locations

**Use Cases:**
- Weekly performance tracking for recurring campaigns.
- Monthly comparison (MoM growth).
- Seasonal pattern detection.

### 6. Redemption by Location

**Access:** Click **By Location** tab.

**Data:**
- List of locations with redemption counts and total discount.
- Percentage distribution across locations.

**Insights:**
- Which locations have highest coupon usage?
- Are there under‑performing locations that need promotion?
- Geographic targeting effectiveness.

### 7. Redemption by Customer Group

**Access:** Click **By Customer Group** tab.

**Data:**
- Breakdown of redemptions by customer group (VIP, Students, etc.).
- Shows which segments respond best to coupons.

**Applications:**
- Refine targeting for future campaigns.
- Measure group‑specific campaign success.

## Exporting Reports

### Export Formats

Each report tab includes export buttons for:

1. **CSV** – Comma‑separated values (Excel‑compatible)
2. **PDF** – Formatted PDF with charts and tables
3. **Excel** – Native XLSX file with multiple sheets

### Export Steps

1. Select desired date range.
2. Navigate to the report tab.
3. Click the export icon (CSV, PDF, Excel) in the top‑right corner.
4. File downloads automatically.

### Scheduled Exports

You can automate report delivery via **Scheduled Reports**:

1. Go to **Settings → Notifications → Scheduled Reports**.
2. Click **Create Scheduled Report**.
3. Choose **Coupon Redemption Summary** (or other coupon report types).
4. Set frequency:
   - **Daily** – Sent every morning at 6:00 AM
   - **Weekly** – Monday morning
   - **Monthly** – First day of month
5. Add recipients (email addresses).
6. Save.

Scheduled reports are delivered as PDF attachments.

## Interpreting Key Metrics

### Redemption Rate

**Formula:** `(Redeemed Coupons / Total Coupons) × 100`

**Interpretation:**
- **< 5%** – Low engagement; consider improving promotion or targeting.
- **5–20%** – Typical range for broad campaigns.
- **> 20%** – High engagement; campaign very effective.

**Actionable Insight:** If redemption rate is low, check coupon visibility, ease of use, and restrictions.

### Average Discount per Redemption

**Formula:** `Total Discount / Redeemed Coupons`

**Interpretation:**
- **High value** – Coupons are generous; monitor revenue impact.
- **Low value** – Coupons are modest; may need to increase discount to drive sales.

**Benchmark:** Compare with average transaction value to understand relative discount.

### Sales with Coupons Penetration

**Formula:** `Sales with Coupons / Total Sales × 100`

**Note:** This metric requires total sales data (available in general sales reports).

**Interpretation:** What percentage of all transactions used a coupon? High penetration indicates coupons are widely adopted.

### Revenue Impact vs. Incremental Sales

While the system shows **total discount given**, true ROI requires measuring incremental sales attributed to coupons. This is currently not automated; manual analysis needed.

## Advanced Analysis Techniques

### Cohort Analysis

1. Export redemption data (CSV).
2. Group coupons by campaign (use batch name or code prefix).
3. Compare redemption patterns across campaigns.

### Customer Lifetime Value (CLV) Impact

1. Identify customers who redeemed coupons.
2. Track their subsequent purchases (via customer sales report).
3. Compare CLV of coupon‑redeeming customers vs. non‑redeemers.

### A/B Testing Coupons

To test different coupon designs:
1. Create two similar coupons with different parameters (e.g., 10% off vs. $5 off).
2. Distribute equally to similar customer segments.
3. Use **Performance by Coupon** report to compare redemption rates and average discount.

## Report Customization

### Filtering by Coupon Attributes

Use the **Filters** panel (left sidebar) to narrow reports by:
- **Coupon Type** (percentage, fixed, etc.)
- **Location** (single or multiple locations)
- **Customer Group**
- **Campaign Batch**

### Comparing Periods

The dashboard does not natively support period‑over‑period comparison. To compare:

1. Export data for Period A (e.g., last month) as CSV.
2. Export data for Period B (e.g., current month) as CSV.
3. Combine in Excel and calculate differences.

### Creating Custom Dashboards

For advanced users, the underlying API endpoints can be used to build custom dashboards (e.g., in Power BI, Tableau).

**API Endpoints:**
- `GET /api/reports/coupons/redemption-analytics`
- `GET /api/reports/coupons/performance-by-type`
- `GET /api/reports/coupons/usage-by-period`
- `GET /api/reports/coupons/top-coupons`

All endpoints accept `start_date` and `end_date` query parameters.

## Troubleshooting Report Issues

### Data Discrepancies

| Issue | Possible Cause | Solution |
|-------|---------------|----------|
| **Redemptions missing** | Sale voided after coupon applied | Voided sales are excluded from reports. |
| **Discount amounts don’t match** | Coupon discount changed after redemption | Reports use historical redemption records, not current coupon settings. |
| **Date range includes future dates** | Timezone mismatch | Ensure report timezone matches coupon timezone (UTC by default). |
| **Cached data outdated** | Reports cached for 5 minutes | Click **Refresh** button or wait a few minutes. |

### Empty Reports

- Verify date range is correct.
- Check that coupons exist and were redeemed in that period.
- Ensure you have permission to view reports for selected locations.

### Slow Report Loading

Large date ranges (> 6 months) with many redemptions may load slowly. Use **Usage by Period** (weekly/monthly) for long‑term trends.

## Best Practices for Campaign Analysis

### Pre‑Campaign Baseline

Before launching a new campaign, record:
- Baseline sales (average daily revenue)
- Baseline transaction count
- Baseline average transaction value

### During Campaign Monitoring

1. **Daily check** of Redemption Analytics dashboard.
2. **Alert threshold**: Set up manual alert if redemption rate deviates significantly from expected.
3. **Location‑level check**: Ensure all locations are redeeming coupons as expected.

### Post‑Campaign Review

1. **Export final reports** and save for historical records.
2. **Calculate ROI**:
   ```
   Incremental Revenue = (Sales during campaign − Baseline sales) − Total Discount Given
   ROI = Incremental Revenue / Total Discount Given
   ```
3. **Document lessons learned**:
   - Which coupon types performed best?
   - Which customer segments responded?
   - Any operational issues (POS errors, customer confusion)?

## Glossary

| Term | Definition |
|------|------------|
| **Redemption** | Single use of a coupon on a sale. |
| **Total Discount** | Sum of all discount amounts given by coupons. |
| **Unique Coupons** | Number of distinct coupon codes redeemed. |
| **Unique Customers** | Number of distinct customers who redeemed coupons. |
| **Redemption Rate** | Percentage of created coupons that were redeemed. |
| **Stackable** | Coupon can be combined with other coupons. |
| **Batch** | Group of coupons generated together with shared settings. |
| **Customer Eligibility** | Rules defining which customers can use a coupon. |
| **Product Restriction** | Rules limiting which products a coupon applies to. |

## Support & Resources

### Further Help
- **Analytics training** – Contact marketing department for advanced analysis workshops.
- **API documentation** – See [Coupon API Documentation](../docs/coupon-api-documentation.md).
- **System admin** – For technical issues with reports, open a ticket with IT.

### Related Documentation
- [Coupon System Architecture](../docs/coupon-system-architecture.md)
- [Admin User Guide](../docs/coupon-admin-guide.md)
- [POS Staff Guide](../docs/coupon-pos-guide.md)

---

*Document last updated: March 8, 2026*