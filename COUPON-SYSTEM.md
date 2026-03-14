# Enterprise Coupon Management System

## Quick Links

- [Full Documentation Overview](docs/coupon-system-overview.md)
- [System Architecture](docs/coupon-system-architecture.md)
- [Admin User Guide](docs/coupon-admin-guide.md)
- [POS Staff Guide](docs/coupon-pos-guide.md)
- [Reporting Guide](docs/coupon-reporting-guide.md)
- [API Documentation](docs/coupon-api-documentation.md)
- [Deployment Checklist](docs/coupon-deployment-checklist.md)
- [Troubleshooting Guide](docs/coupon-troubleshooting-guide.md)

## Overview

The Enterprise Coupon Management System is a robust, feature‑rich coupon and voucher solution fully integrated into the Laravel POS application. It provides businesses with powerful tools to create, manage, and analyze promotional campaigns across multiple locations with advanced rules and real‑time POS validation.

## Core Features

### Coupon Types
- **Percentage Discount** – Percentage off subtotal (with optional maximum cap)
- **Fixed Amount** – Fixed currency amount off
- **Buy X Get Y (BOGO)** – Buy certain quantity, get another free
- **Free Shipping** – Waive shipping costs
- **Tiered Discount** – Discount based on purchase amount tiers

### Advanced Rule Engine
- **Date & Time Validity** – Start/end dates with timezone support
- **Usage Limits** – Total redemptions and per‑customer limits
- **Minimum Purchase** – Required subtotal to activate coupon
- **Location Restrictions** – Limit to specific store locations
- **Customer Eligibility** – Target new, existing, or grouped customers
- **Product Restrictions** – Apply to specific products or categories
- **Stackability** – Control whether coupons can be combined

### Bulk Operations
- Generate thousands of unique coupons with customizable patterns (`{RANDOM6}`, `{DATE}`, etc.)
- Batch management with progress tracking
- CSV export of generated codes

### Real‑time POS Integration
- Instant validation at checkout
- Automatic discount calculation
- Clear error messages for staff and customers
- Support for multiple coupons per sale (stackable)

### Comprehensive Analytics
- Dashboard with key metrics (redemption rate, total discount, etc.)
- Daily redemption trends
- Performance by coupon type
- Top‑performing coupons
- Location‑ and customer‑group breakdowns
- Export to CSV, PDF, Excel

## Quick Start for Administrators

1. **Ensure you have `manage system` permission.**
2. **Create a coupon:**
   - Navigate to **Marketing → Coupons → Create Coupon**
   - Fill in basic info, discount settings, validity, restrictions
   - Save
3. **Test the coupon:**
   - Use the **Test Validation** tool on the coupon detail page
   - Verify discount calculation and restrictions
4. **Distribute the coupon code** to customers or generate bulk codes.

## Quick Start for POS Staff

1. **Add items** to the sale.
2. **Enter coupon code** in the POS coupon field (right panel).
3. **Press Enter** – if valid, discount appears automatically.
4. **Handle errors** by reading the message to the customer (e.g., “Minimum purchase not met”).

## Development Integration

### API Endpoints
- `POST /api/coupons/validate` – Validate a coupon for a sale
- `POST /api/coupons/apply` – Apply a validated coupon
- `GET /api/reports/coupons/*` – Access analytics data

See [API Documentation](docs/coupon-api-documentation.md) for details.

### Extending the System
- Add new coupon types by extending the `Coupon` model and `CouponService`
- Add new restriction types via additional pivot tables and validation logic
- Customize reports by leveraging the `CouponReportService`

## System Requirements

- **Laravel** 10.x+
- **PHP** 8.1+
- **MySQL** 8.0+ / PostgreSQL 12+ / SQLite 3.35+
- **Redis** (recommended for caching and queues)
- **Node.js** 18+ (for frontend assets)

## Installation

The coupon system is already included in the codebase. To activate:

```bash
# Run the coupon migrations
php artisan migrate --path=database/migrations/2026_03_08_114*

# Assign permissions (via admin panel or database)
# Ensure your user has the `manage system` permission

# Verify POS interface includes coupon field
```

Detailed instructions: [Deployment Checklist](docs/coupon-deployment-checklist.md).

## Support

- **Administrators** – Refer to the [Admin User Guide](docs/coupon-admin-guide.md) and [Troubleshooting Guide](docs/coupon-troubleshooting-guide.md).
- **Developers** – Review the [System Architecture](docs/coupon-system-architecture.md) and [API Documentation](docs/coupon-api-documentation.md).
- **POS Staff** – Use the [POS Staff Guide](docs/coupon-pos-guide.md).

For bugs, feature requests, or urgent issues, contact the development team.

---

*Last updated: March 8, 2026*