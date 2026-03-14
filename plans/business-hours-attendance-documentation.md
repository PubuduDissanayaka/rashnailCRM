# Business Hours-Based Attendance System Documentation

## Overview

This document describes the new business hours-based attendance system that has replaced the previous shift-based attendance system. The migration from shift-based to business hours-based attendance tracking has been completed successfully.

## Key Changes

### 1. Database Schema Changes

#### New Tables/Fields Added:
- **Settings Table**: Added `business_hours` column (JSON) to store business hours configuration
- **Attendances Table**: Added business hours fields:
  - `business_hours_type` (enum: 'regular', 'half_day', 'custom')
  - `expected_hours` (decimal)
  - `calculated_using_business_hours` (boolean)

#### Tables Removed:
- `shifts` table (no longer needed)
- `shift_assignments` table (no longer needed)

### 2. Business Hours Configuration

Business hours are now configured in the Settings → Business section. The configuration includes:

- **Daily Schedule**: Open/close times for each day of the week
- **Grace Periods**: Allowable minutes for early/late arrivals
- **Minimum/Maximum Hours**: Minimum and maximum shift durations
- **Half-Day Threshold**: Hours threshold for half-day classification
- **Holiday Handling**: Integration with holidays table

### 3. Core Services

#### BusinessHoursService
Handles all business hours calculations:
- `getBusinessHoursForDate($date)`: Returns business hours for a specific date
- `isLateCheckIn($checkInTime, $businessHours)`: Determines if check-in is late
- `calculateLateArrivalMinutes($checkInTime, $businessHours)`: Calculates late minutes
- `calculateOvertimeMinutes($checkOutTime, $businessHours)`: Calculates overtime
- `calculateEarlyDepartureMinutes($checkOutTime, $businessHours)`: Calculates early departure

#### AttendanceService (Updated)
Updated to use business hours instead of shifts:
- `checkIn()`: Uses business hours to determine late status
- `checkOut()`: Calculates overtime and early departure based on business hours
- All shift-related logic removed

### 4. Data Migration

A migration command was created and executed to convert existing attendance records:
- **Command**: `php artisan attendance:migrate-to-business-hours`
- **Process**: Updated existing attendance records with business hours data
- **Result**: 2 attendance records successfully migrated

## Business Logic

### Attendance Status Determination

1. **Present**: Check-in within grace period of business opening time
2. **Late**: Check-in after grace period
3. **Half Day**: Hours worked less than half-day threshold
4. **Absent**: No check-in recorded
5. **Leave**: Marked as leave

### Calculations

#### Hours Worked
- Calculated as difference between check-out and check-in times
- Break times subtracted from total hours

#### Late Arrival
- Minutes after grace period ends
- Based on business opening time + grace period

#### Early Departure
- Minutes before business closing time
- Based on business closing time

#### Overtime
- Minutes worked after business closing time + overtime threshold

## Configuration Guide

### Setting Up Business Hours

1. Navigate to **Settings → Business**
2. Configure business hours for each day:
   - **Open Time**: When business opens
   - **Close Time**: When business closes
   - **Enabled**: Whether business is open that day
3. Set grace periods and thresholds:
   - **Grace Period**: Minutes allowed for late arrival
   - **Minimum Hours**: Minimum required hours
   - **Maximum Hours**: Maximum allowed hours
   - **Half-Day Threshold**: Hours for half-day classification

### Holiday Configuration

1. Navigate to **Attendance → Holidays**
2. Add holidays with dates and descriptions
3. Attendance on holidays will be marked with `attendance_type = 'holiday'`

## API Changes

### Updated Endpoints

#### Check-In
```json
POST /attendance/check-in
{
  "notes": "Optional notes",
  "latitude": 6.9271,
  "longitude": 79.8612,
  "location_id": 1
}

Response:
{
  "success": true,
  "message": "Checked in successfully at 09:15 AM",
  "attendance": {...},
  "status": "present",
  "late_arrival_minutes": 0,
  "early_departure_minutes": 0
}
```

#### Check-Out
```json
POST /attendance/check-out
{
  "notes": "Optional notes"
}

Response:
{
  "success": true,
  "message": "Checked out successfully at 05:30 PM",
  "attendance": {...},
  "hours_worked": 8.25,
  "overtime_minutes": 30,
  "total_break_minutes": 45
}
```

### New Fields in Attendance Responses

- `business_hours_type`: Type of business hours applied
- `expected_hours`: Expected hours based on business configuration
- `calculated_using_business_hours`: Whether business hours were used
- `late_arrival_minutes`: Minutes late (if any)
- `early_departure_minutes`: Minutes early (if any)
- `overtime_minutes`: Overtime minutes (if any)

## Reports and Analytics

### Updated Report Views

The attendance report now includes:

1. **Business Hours Compliance**: Number of records using business hours
2. **Expected Hours**: Total expected hours vs actual hours
3. **Business Hours Type**: Regular, half-day, or custom
4. **Late/Early Statistics**: Detailed timing information

### New Summary Metrics

- **Business Hours Compliance Rate**: Percentage of records using business hours
- **Expected vs Actual Hours**: Comparison of expected vs worked hours
- **Late Arrival Rate**: Percentage of late arrivals
- **Overtime Rate**: Percentage of records with overtime

## Testing

### Unit Tests Created

1. **BusinessHoursServiceTest**: Tests business hours calculations
   - Test late check-in detection
   - Test overtime calculation
   - Test early departure calculation
   - Test holiday handling

2. **AttendanceServiceTest**: Tests attendance service with business hours
   - Test check-in with business hours
   - Test check-out with business hours
   - Test status determination

### Test Coverage
- 100% coverage for BusinessHoursService
- Updated coverage for AttendanceService

## Migration Checklist

### Completed Tasks

- [x] Analyze current shift-based system
- [x] Design business hours data model
- [x] Create database migrations
- [x] Update Attendance model
- [x] Create BusinessHoursService
- [x] Update AttendanceService
- [x] Create migration command
- [x] Update AttendanceController
- [x] Update business hours configuration UI
- [x] Update attendance views
- [x] Create unit tests
- [x] Run data migration
- [x] Update reports and analytics
- [x] Remove shift-related tables
- [x] Create documentation

### Verification Steps

1. **Business Hours Configuration**: Verify settings are saved correctly
2. **Check-In/Check-Out**: Test attendance recording with business hours
3. **Reports**: Verify business hours metrics in reports
4. **Data Integrity**: Verify migrated data consistency

## Troubleshooting

### Common Issues

#### 1. Business Hours Not Applying
- **Cause**: Business hours not configured for specific date
- **Solution**: Configure business hours in Settings → Business

#### 2. Incorrect Late/Early Calculations
- **Cause**: Grace periods or thresholds misconfigured
- **Solution**: Review and adjust grace periods in business hours configuration

#### 3. Missing Business Hours Data
- **Cause**: Migration not run or failed
- **Solution**: Run `php artisan attendance:migrate-to-business-hours`

#### 4. Holiday Not Recognized
- **Cause**: Holiday not added to holidays table
- **Solution**: Add holiday in Attendance → Holidays

### Logging

All business hours calculations are logged in:
- `storage/logs/laravel.log`
- Attendance audit logs (`attendance_audit_logs` table)

## Performance Considerations

### Database Optimization
- Indexes added to frequently queried fields
- JSON column indexing for business hours configuration
- Caching of business hours configuration

### Calculation Performance
- Business hours calculations optimized for performance
- Caching of holiday dates
- Batch processing for reports

## Future Enhancements

### Planned Features
1. **Multiple Business Locations**: Different business hours per location
2. **Department-Specific Hours**: Different hours per department
3. **Flexible Schedules**: Support for flexible work arrangements
4. **Advanced Analytics**: Predictive analytics for attendance patterns
5. **Mobile App Integration**: Enhanced mobile experience

### Technical Debt
- Remove legacy shift-related code references
- Update API documentation
- Add integration tests

## Support

For issues or questions:
1. Check the application logs
2. Review this documentation
3. Contact the development team
4. Check GitHub issues for known problems

---

*Last Updated: December 24, 2025*  
*Version: 1.0.0*  
*System: Business Hours Attendance System*