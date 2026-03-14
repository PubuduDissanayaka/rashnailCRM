# Work Hour Reports Feature

## Overview
The Work Hour Reports feature provides administrators with comprehensive analytics and reporting capabilities for staff work hours. It integrates with the existing business hours-based attendance system to calculate actual hours worked, overtime, attendance rates, and compliance metrics.

## Features

### 1. **Report Views**
- **Staff Summary**: Aggregated view of work hours per staff member with totals, averages, and rates
- **Detailed Report**: Daily attendance records with check-in/check-out times, hours worked, and status
- **Staff Detail View**: Individual staff member's work hour history and statistics

### 2. **Filtering Capabilities**
- **Date Range**: Flexible date selection with quick range options (Today, This Week, Last 30 Days, etc.)
- **Staff Filter**: Filter by individual staff member
- **Role Filter**: Filter by staff role (staff, manager, supervisor)
- **Status Filter**: Filter by attendance status (present, late, absent, leave, half_day)

### 3. **Export Functionality**
- **CSV Export**: Comprehensive CSV export with summary and detailed sections
- **PDF Export**: Professional PDF report with formatted tables and statistics
- **Rate Limiting**: Export endpoints are rate-limited to 5 requests per minute

### 4. **Performance Features**
- **Query Caching**: Aggregated data is cached for 5 minutes to improve performance
- **Database Indexes**: Optimized indexes on attendance and role tables
- **Efficient Queries**: Optimized SQL queries with proper joins and aggregations

## Technical Architecture

### Database Schema
The feature uses the existing `attendances` table with business hours calculations. Key columns used:
- `hours_worked`: Actual hours worked (calculated using business hours)
- `expected_hours`: Expected hours based on business hours configuration
- `overtime_hours`: Hours worked beyond expected hours
- `status`: Attendance status (present, late, absent, leave, half_day)
- `calculated_using_business_hours`: Flag indicating business hours calculation

### Key Components

#### 1. **WorkHourReportService**
- Core business logic for report generation
- Methods: `getStaffSummary()`, `getDetailedReport()`, `getSummaryStatistics()`
- Implements query building with filters
- Includes caching layer for performance

#### 2. **FilterCriteria DTO**
- Data Transfer Object for filter parameters
- Handles validation and default values
- Methods: `fromRequest()`, `validate()`, `getDateRangeString()`

#### 3. **ExportService**
- Handles CSV and PDF export generation
- CSV: Streaming response with UTF-8 BOM for Excel compatibility
- PDF: Uses DomPDF with custom Blade template
- Includes validation for export limits

#### 4. **WorkHourReportController**
- RESTful API endpoints for report data
- Routes:
  - `GET /reports/work-hours` - Main report view
  - `GET /reports/work-hours/summary` - Staff summary data
  - `GET /reports/work-hours/detail` - Detailed records
  - `POST /reports/work-hours/export/csv` - CSV export
  - `POST /reports/work-hours/export/pdf` - PDF export

#### 5. **RateLimitReportExports Middleware**
- Rate limiting for export endpoints (5 requests per minute per user/IP)
- Returns 429 status with retry information when limit exceeded
- Adds rate limit headers to responses

## Installation & Setup

### Prerequisites
- Laravel 10+ with Spatie Permissions
- DomPDF package: `composer require barryvdh/laravel-dompdf`
- Business hours attendance system configured

### Database Migrations
Run the performance indexes migration:
```bash
php artisan migrate --path=database/migrations/2025_12_24_092549_add_indexes_for_work_hour_reports.php
```

### Routes
Routes are automatically registered in `routes/web.php` under the `can:view attendances` middleware group.

### Permissions
Users need the `view attendances` permission to access work hour reports. Administrators automatically have this permission.

## Usage Guide

### Accessing Reports
1. Navigate to `/reports/work-hours` (accessible to users with `view attendances` permission)
2. Use the filter panel to select date range, staff, role, and status
3. Click "Apply Filters" to update the report

### Exporting Reports
1. Apply desired filters
2. Click "Export CSV" for CSV download
3. Click "Export PDF" for PDF download (requires DomPDF)

### API Endpoints
All API endpoints return JSON responses:

#### Get Staff Summary
```http
GET /reports/work-hours/summary?start_date=2025-12-01&end_date=2025-12-24&staff_id=1&role_id=2&status=present
```

#### Get Detailed Report
```http
GET /reports/work-hours/detail?start_date=2025-12-01&end_date=2025-12-24&per_page=20&page=1
```

#### Export CSV
```http
POST /reports/work-hours/export/csv
Content-Type: application/x-www-form-urlencoded

start_date=2025-12-01&end_date=2025-12-24&staff_id=1
```

## Performance Considerations

### Caching Strategy
- Staff summary data: Cached for 5 minutes
- Date range statistics: Cached for 5 minutes
- Cache keys include filter parameters for specificity
- Cache can be cleared using `WorkHourReportService::clearCache()`

### Database Optimization
- Composite indexes on `attendances(date, user_id, calculated_using_business_hours)`
- Indexes on `model_has_roles(model_id, role_id)`
- Efficient JOIN operations with proper WHERE clauses

### Rate Limiting
- Export endpoints: 5 requests per minute per user/IP
- Prevents abuse and server overload
- Returns appropriate HTTP 429 responses

## Security Considerations

### Authorization
- All routes protected by `can:view attendances` middleware
- Admin users automatically have access via Gate::before rule
- Individual controller methods validate permissions

### Data Privacy
- Only shows data for staff users (not customers or other user types)
- Role-based filtering respects organizational hierarchy
- Export files are generated on-demand and not stored on server

### Input Validation
- FilterCriteria DTO validates all input parameters
- Date ranges validated (max 1 year for exports)
- Staff and role IDs validated against database

## Testing

### Running Tests
```bash
php artisan test tests/Feature/WorkHourReportTest.php
```

### Test Coverage
- Authentication and authorization
- Filter functionality
- Export generation
- Rate limiting
- Error handling
- Data accuracy

## Troubleshooting

### Common Issues

#### 1. **No Data Showing**
- Check if attendance records exist for the date range
- Verify `calculated_using_business_hours` is true for records
- Ensure staff users have the 'staff' role

#### 2. **Export Fails**
- Check DomPDF installation: `composer require barryvdh/laravel-dompdf`
- Verify PHP memory limit is sufficient for PDF generation
- Check rate limiting status (max 5 exports per minute)

#### 3. **Slow Performance**
- Ensure database indexes are created
- Check cache configuration (Redis recommended for production)
- Consider reducing date range for large datasets

#### 4. **Permission Errors**
- Verify user has `view attendances` permission
- Check Spatie permissions configuration
- Ensure admin users have administrator role

## Future Enhancements

### Planned Features
1. **Advanced Analytics**: Trend analysis, comparison reports
2. **Scheduled Reports**: Automated email delivery of reports
3. **Custom Metrics**: User-defined KPIs and calculations
4. **Real-time Updates**: WebSocket integration for live updates
5. **Multi-location Support**: Reports across multiple business locations

### Technical Improvements
1. **Redis Caching**: Implement Redis for distributed caching
2. **Queue Jobs**: Move PDF generation to background jobs
3. **API Versioning**: Versioned API endpoints
4. **GraphQL Support**: Alternative to REST API
5. **Data Warehouse Integration**: Integration with analytics platforms

## Support
For issues or questions:
1. Check the Laravel logs: `storage/logs/laravel.log`
2. Verify database migrations are up to date
3. Check PHP and package versions
4. Contact system administrator for permission issues

---

*Last Updated: December 24, 2025*  
*Version: 1.0.0*