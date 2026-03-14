# Coupon System API Documentation

## Overview

The Coupon System exposes RESTful API endpoints for programmatic coupon validation, application, and reporting. This documentation covers all public API endpoints, request/response formats, authentication, and error handling.

## Base URL

All API endpoints are relative to your application's base URL:

```
https://your‑domain.com/api
```

For local development:
```
http://localhost:8000/api
```

## Authentication

### Web Authentication
Endpoints under `/api/coupons/*` and `/api/reports/coupons/*` require the same session authentication as the web interface (via Laravel's `auth` middleware). Ensure you are logged in as a user with appropriate permissions.

### API Token Authentication (Future)
Planned but not yet implemented. Currently, only web session authentication is supported.

## Rate Limiting

- **Default**: 60 requests per minute per IP address.
- **Exceeded response**: HTTP 429 with JSON body `{"message": "Too Many Attempts."}`

## Common Response Formats

### Success Response
```json
{
  "success": true,
  "data": { ... },
  "message": "Optional success message"
}
```

### Error Response
```json
{
  "success": false,
  "error": "Error description",
  "errors": {
    "field": ["Validation error message"]
  },
  "code": "ERROR_CODE"
}
```

### Paginated Response
```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 20,
    "to": 20,
    "total": 100
  },
  "links": {
    "first": "https://...?page=1",
    "last": "https://...?page=5",
    "prev": null,
    "next": "https://...?page=2"
  }
}
```

## Coupon Validation & Application

### Validate Coupon
Validate a coupon for a given sale and customer.

**Endpoint:** `POST /api/coupons/validate`

**Permissions:** `view sales`

**Request Body:**
```json
{
  "code": "SUMMER25",
  "sale_id": 12345,
  "customer_id": 67890,
  "subtotal": 150.00,
  "items": [
    {
      "product_type": "App\\Models\\Service",
      "product_id": 1,
      "price": 75.00,
      "quantity": 2
    }
  ]
}
```

**Parameters:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `code` | string | Yes | Coupon code (case‑insensitive) |
| `sale_id` | integer | Yes | ID of the sale (must exist) |
| `customer_id` | integer | No | ID of customer (if known) |
| `subtotal` | float | Yes | Current sale subtotal |
| `items` | array | No | Sale items for product restriction validation |
| `items[].product_type` | string | Yes | Fully qualified model class |
| `items[].product_id` | integer | Yes | ID of product/service/package |
| `items[].price` | float | Yes | Unit price |
| `items[].quantity` | integer | Yes | Quantity |

**Success Response (Valid Coupon):**
```json
{
  "success": true,
  "data": {
    "valid": true,
    "coupon": {
      "id": 42,
      "code": "SUMMER25",
      "name": "Summer Sale 25% Off",
      "type": "percentage",
      "discount_value": 25.0,
      "max_discount_amount": 50.0,
      "minimum_purchase_amount": 100.0,
      "stackable": false,
      "active": true
    },
    "discount_amount": 37.50,
    "validation_messages": []
  }
}
```

**Success Response (Invalid Coupon):**
```json
{
  "success": true,
  "data": {
    "valid": false,
    "coupon": null,
    "discount_amount": 0,
    "validation_messages": [
      "Minimum purchase amount not met.",
      "Coupon is not valid for this location."
    ]
  }
}
```

**Error Responses:**
- `400 Bad Request` – Missing required fields or validation errors.
- `404 Not Found` – Coupon not found.
- `403 Forbidden` – Insufficient permissions.

### Apply Coupon
Apply a validated coupon to a sale.

**Endpoint:** `POST /api/coupons/apply`

**Permissions:** `apply coupons` (implicit with `view sales`)

**Request Body:**
```json
{
  "code": "SUMMER25",
  "sale_id": 12345,
  "customer_id": 67890
}
```

**Success Response:**
```json
{
  "success": true,
  "data": {
    "redemption": {
      "id": 999,
      "coupon_id": 42,
      "sale_id": 12345,
      "customer_id": 67890,
      "discount_amount": 37.50,
      "redeemed_at": "2026-03-08 11:30:00",
      "redeemed_by_user_id": 1
    },
    "sale": {
      "id": 12345,
      "subtotal": 150.00,
      "coupon_discount_amount": 37.50,
      "total_amount": 112.50
    }
  }
}
```

**Error Responses:**
- `400 Bad Request` – Coupon invalid (returns validation messages).
- `409 Conflict` – Coupon already applied to sale.
- `422 Unprocessable Entity` – Business rule violation (e.g., stackability).

### Remove Coupon
Remove a coupon from a sale.

**Endpoint:** `DELETE /api/coupons/{sale_id}/coupons/{coupon_id}`

**Permissions:** `view sales`

**Success Response:**
```json
{
  "success": true,
  "data": {
    "message": "Coupon removed successfully",
    "sale": {
      "id": 12345,
      "coupon_discount_amount": 0,
      "total_amount": 150.00
    }
  }
}
```

## Coupon Management (Admin)

### List Coupons
Retrieve paginated list of coupons.

**Endpoint:** `GET /api/coupons`

**Permissions:** `manage system`

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 20 | Items per page |
| `search` | string | null | Search in code, name, description |
| `type` | string | null | Filter by coupon type |
| `active` | boolean | null | Filter by active status |
| `start_date` | date | null | Filter coupons created after date |
| `end_date` | date | null | Filter coupons created before date |

**Success Response:** Paginated response with coupon objects.

### Get Coupon Details
Retrieve a single coupon with its relationships.

**Endpoint:** `GET /api/coupons/{id}`

**Permissions:** `manage system`

**Success Response:**
```json
{
  "success": true,
  "data": {
    "id": 42,
    "code": "SUMMER25",
    "name": "Summer Sale 25% Off",
    "description": "25% off for summer promotion",
    "type": "percentage",
    "discount_value": 25.0,
    "max_discount_amount": 50.0,
    "minimum_purchase_amount": 100.0,
    "start_date": "2026-06-01 00:00:00",
    "end_date": "2026-08-31 23:59:59",
    "timezone": "UTC",
    "total_usage_limit": 1000,
    "per_customer_limit": 1,
    "stackable": false,
    "active": true,
    "location_restriction_type": "all",
    "customer_eligibility_type": "all",
    "product_restriction_type": "all",
    "metadata": null,
    "batch_id": null,
    "created_at": "2026-03-08 10:00:00",
    "updated_at": "2026-03-08 10:00:00",
    "redemptions_count": 245,
    "batch": null,
    "customer_groups": [],
    "locations": [],
    "products": [],
    "categories": []
  }
}
```

### Create Coupon
Create a new coupon.

**Endpoint:** `POST /api/coupons`

**Permissions:** `manage system`

**Request Body:** (fields same as Coupon model, see example in update endpoint)

**Success Response:** `201 Created` with coupon object.

### Update Coupon
Update an existing coupon.

**Endpoint:** `PUT /api/coupons/{id}`

**Permissions:** `manage system`

**Request Body Example:**
```json
{
  "name": "Updated Summer Sale",
  "end_date": "2026-09-30 23:59:59",
  "total_usage_limit": 1500,
  "active": true
}
```

**Success Response:** Updated coupon object.

### Delete Coupon
Soft‑delete a coupon.

**Endpoint:** `DELETE /api/coupons/{id}`

**Permissions:** `manage system`

**Success Response:**
```json
{
  "success": true,
  "data": {
    "message": "Coupon deleted successfully"
  }
}
```

## Bulk Coupon Generation

### Create Batch
Create a batch for bulk coupon generation.

**Endpoint:** `POST /api/coupons/batches`

**Permissions:** `manage system`

**Request Body:**
```json
{
  "name": "Summer Campaign 2026",
  "description": "Bulk coupons for summer promotion",
  "pattern": "SUMMER-{RANDOM6}-{DATE}",
  "count": 500,
  "settings": {
    "type": "percentage",
    "discount_value": 15.0,
    "minimum_purchase_amount": 50.0,
    "start_date": "2026-06-01 00:00:00",
    "end_date": "2026-08-31 23:59:59"
  }
}
```

**Success Response:** Batch object with `status: pending`.

### Generate Coupons
Start generation of coupons for a batch.

**Endpoint:** `POST /api/coupons/batches/{id}/generate`

**Permissions:** `manage system`

**Success Response:**
```json
{
  "success": true,
  "data": {
    "message": "Batch generation started",
    "batch": {
      "id": 5,
      "status": "generating",
      "progress": 0
    }
  }
}
```

### Get Batch Status
Retrieve batch details and generation progress.

**Endpoint:** `GET /api/coupons/batches/{id}`

**Permissions:** `manage system`

**Success Response:** Batch object with `generated_count` and `progress_percentage`.

## Reporting API

All reporting endpoints require `view reports` permission.

### Redemption Analytics
Get daily redemption metrics.

**Endpoint:** `GET /api/reports/coupons/redemption-analytics`

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `start_date` | date | Yes | Start date (YYYY‑MM‑DD) |
| `end_date` | date | Yes | End date (YYYY‑MM‑DD) |
| `location_id` | integer | No | Filter by location |

**Success Response:**
```json
{
  "success": true,
  "data": [
    {
      "date": "2026-03-01",
      "redemptions": 12,
      "total_discount": 145.50,
      "unique_coupons": 8,
      "unique_customers": 10
    },
    ...
  ]
}
```

### Performance by Coupon Type
Get aggregated metrics by coupon type.

**Endpoint:** `GET /api/reports/coupons/performance-by-type`

**Query Parameters:** Same as redemption analytics.

**Success Response:**
```json
{
  "success": true,
  "data": [
    {
      "type": "percentage",
      "total_coupons": 45,
      "active_coupons": 30,
      "expired_coupons": 10,
      "redeemed_coupons": 120,
      "total_discount": 1250.75
    },
    ...
  ]
}
```

### Usage by Period
Get aggregated usage by day, week, or month.

**Endpoint:** `GET /api/reports/coupons/usage-by-period`

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `start_date` | date | Yes | Start date |
| `end_date` | date | Yes | End date |
| `period` | string | No | `day`, `week`, `month` (default: `day`) |

**Success Response:** Array of period aggregates.

### Top Performing Coupons
Get top coupons by redemptions or discount amount.

**Endpoint:** `GET /api/reports/coupons/top-coupons`

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `start_date` | date | Yes | Start date |
| `end_date` | date | Yes | End date |
| `limit` | integer | No | Number of results (default: 10) |
| `order_by` | string | No | `redemptions` or `discount` (default: `redemptions`) |

**Success Response:** Array of coupon objects with extra metrics.

## Webhooks (Planned)

Webhooks are not yet implemented but are planned for future releases. The following events will be available:

- `coupon.redeemed` – When a coupon is successfully applied
- `coupon.expired` – When a coupon reaches its end date
- `coupon.limit_reached` – When total usage limit is exhausted

## Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `COUPON_NOT_FOUND` | 404 | Coupon with given code/ID does not exist |
| `COUPON_EXPIRED` | 400 | Coupon is past its end date |
| `COUPON_INACTIVE` | 400 | Coupon is marked inactive |
| `USAGE_LIMIT_REACHED` | 400 | Total usage limit exhausted |
| `CUSTOMER_LIMIT_REACHED` | 400 | Customer has reached per‑customer limit |
| `MINIMUM_PURCHASE_NOT_MET` | 400 | Sale subtotal below minimum requirement |
| `LOCATION_RESTRICTED` | 400 | Coupon not valid for sale location |
| `CUSTOMER_INELIGIBLE` | 400 | Customer does not meet eligibility criteria |
| `PRODUCT_RESTRICTED` | 400 | No eligible products in sale |
| `NOT_STACKABLE` | 400 | Coupon cannot be combined with others already applied |
| `BATCH_GENERATION_FAILED` | 500 | Bulk coupon generation failed |
| `INSUFFICIENT_PERMISSIONS` | 403 | User lacks required permission |

## Examples

### PHP (Laravel HTTP Client)
```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken($token)->post('https://your‑domain.com/api/coupons/validate', [
    'code' => 'SUMMER25',
    'sale_id' => 12345,
    'subtotal' => 150.00,
]);

if ($response->successful()) {
    $data = $response->json()['data'];
    if ($data['valid']) {
        echo "Discount: $" . $data['discount_amount'];
    } else {
        echo "Errors: " . implode(', ', $data['validation_messages']);
    }
}
```

### JavaScript (Fetch)
```javascript
fetch('/api/coupons/validate', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        code: 'SUMMER25',
        sale_id: 12345,
        subtotal: 150.00
    })
})
.then(response => response.json())
.then(data => {
    if (data.success && data.data.valid) {
        console.log('Discount:', data.data.discount_amount);
    }
});
```

### cURL
```bash
curl -X POST "https://your‑domain.com/api/coupons/validate" \
  -H "Content-Type: application/json" \
  -H "Cookie: laravel_session=..." \
  -d '{
    "code": "SUMMER25",
    "sale_id": 12345,
    "subtotal": 150.00
  }'
```

## Testing

### Sandbox Environment
A sandbox environment is available at `https://sandbox.your‑domain.com/api` with test coupons:

| Code | Type | Discount | Restrictions |
|------|------|----------|--------------|
| `TEST10` | percentage | 10% | None |
| `TESTFIXED` | fixed | $5.00 | Minimum purchase $20 |
| `TESTLOCATION` | percentage | 15% | Location‑specific |

### Automated Tests
The coupon API is covered by the `CouponApiTest` PHPUnit test suite. Run:

```bash
php artisan test --filter=CouponApiTest
```

## Support

- **API Issues**: Contact development team with request/response examples.
- **Feature Requests**: Submit via project issue tracker.
- **Documentation Updates**: This document is maintained in `docs/coupon-api-documentation.md`.

---

*Document last updated: March 8, 2026*