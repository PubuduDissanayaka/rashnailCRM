# Business Hours-Based Attendance System Migration Plan

## Current System Analysis

### Existing Shift-Based Architecture
The current system uses a shift-based model with the following components:

**Database Schema:**
- `shifts` table: Defines shift templates (name, code, start_time, end_time, grace_period_minutes, etc.)
- `shift_assignments` table: Links users to shifts with effective dates
- `attendances` table: Core attendance records with shift-based calculations
- Related tables: `attendance_breaks`, `attendance_meta`, `attendance_audit_logs`, `holidays`

**Business Logic (AttendanceService):**
- Shift assignment lookup for each user/date
- Late arrival calculation based on shift start time + grace period
- Overtime calculation based on shift end time + threshold
- Early departure calculation
- Break tracking and deduction

**Models:**
- `Attendance`: Contains shift-based calculation methods (`isLateCheckIn`, `calculateOvertimeMinutes`, etc.)
- `Shift`: Business logic for shift hours validation and calculations
- `ShiftAssignment`: User-shift relationships with date ranges

**Key Dependencies:**
- Shift assignments determine expected working hours
- All late/overtime calculations reference shift data
- UI may display shift information (though not found in current views)

## New Business Hours-Based Design

### Business Requirements
1. **Single daily schedule**: Same business hours every weekday (e.g., 9am-5pm)
2. **Grace period**: 15 minutes allowed for early/late clock-ins
3. **Standard day**: 8-hour workday
4. **Overtime calculation**: Any time worked beyond business hours
5. **Weekends**: No business hours (treated as special days)
6. **Holidays**: Use existing holidays table for special closures

### Data Model Design

#### 1. Business Hours Configuration
Store in `settings` table with JSON structure:
```json
{
  "business_hours": {
    "monday": {"open": "09:00", "close": "17:00", "enabled": true},
    "tuesday": {"open": "09:00", "close": "17:00", "enabled": true},
    "wednesday": {"open": "09:00", "close": "17:00", "enabled": true},
    "thursday": {"open": "09:00", "close": "17:00", "enabled": true},
    "friday": {"open": "09:00", "close": "17:00", "enabled": true},
    "saturday": {"open": null, "close": null, "enabled": false},
    "sunday": {"open": null, "close": null, "enabled": false}
  },
  "grace_period_minutes": 15,
  "overtime_threshold_minutes": 0,
  "minimum_shift_hours": 0,
  "maximum_shift_hours": 12
}
```

#### 2. Attendance Calculation Logic
**Status Determination:**
- `present`: Clock-in within business hours ± grace period
- `late`: Clock-in after business hours start + grace period
- `absent`: No clock-in recorded
- `leave`: Marked as leave (manual/admin entry)
- `half_day`: Worked less than 4 hours (configurable)

**Hour Calculations:**
- Total hours = (check_out - check_in) - break minutes
- Overtime hours = time worked beyond business close time
- Late minutes = clock-in time after (business open + grace period)
- Early departure minutes = clock-out time before business close

#### 3. Edge Case Handling
- **Clock-in before open**: Count as early, but status based on grace period
- **Clock-out after close**: Calculate overtime
- **Multiple clock-ins/outs**: Use first in, last out
- **Weekend/holiday attendance**: Special attendance type, overtime rules may differ
- **Partial day**: Minimum hours threshold for half-day status

### Migration Strategy

#### Phase 1: Schema Changes
1. **Add business hours configuration** to settings table
2. **Deprecate shift-related columns** in attendance table (keep for backward compatibility)
3. **Create migration to copy** shift-based data to new format where possible
4. **Add new calculated fields** for business hours-based metrics

#### Phase 2: Data Migration
1. **Extract current shift assignments** and convert to business hours expectations
2. **Calculate historical attendance** using business hours rules (optional)
3. **Preserve shift data** in archive tables for reporting
4. **Update existing attendance records** with new status calculations

#### Phase 3: Code Refactoring
1. **Update AttendanceService** to remove shift dependencies
2. **Modify Attendance model** business logic methods
3. **Update AttendanceController** endpoints
4. **Remove shift references** from views and APIs
5. **Update reports and dashboards**

#### Phase 4: Testing & Validation
1. **Unit tests** for new business hours calculations
2. **Integration tests** for migration scripts
3. **Data validation** comparing old vs new calculations
4. **User acceptance testing** with sample data

### Database Migration Plan

#### Tables to Modify:
1. **`attendances` table**:
   - Add `business_hours_type` enum (regular, weekend, holiday)
   - Add `expected_hours` decimal(5,2) based on business hours
   - Deprecate `shift_assignment_id` (nullable, eventually remove)

2. **`settings` table**:
   - Add business hours configuration entries

3. **Tables to Archive (optional)**:
   - `shifts` → `archived_shifts`
   - `shift_assignments` → `archived_shift_assignments`

#### Migration Scripts Needed:
1. `migrate_business_hours_configuration.php`
2. `convert_shift_assignments_to_business_hours.php`
3. `recalculate_historical_attendance.php`
4. `cleanup_shift_references.php`

### Code Refactoring Steps

#### 1. AttendanceService Updates
- Remove `getUserShiftForDate()` method
- Replace with `getBusinessHoursForDate()` 
- Update `checkIn()` to use business hours for status determination
- Update `checkOut()` to calculate overtime based on business close time
- Remove shift-specific calculation methods

#### 2. Attendance Model Updates
- Replace `isLateCheckIn()` with business hours-based logic
- Replace `calculateOvertimeMinutes()` with business close time logic
- Update `calculateEarlyDepartureMinutes()` 
- Add helper methods: `isWithinBusinessHours()`, `getBusinessHoursForDate()`

#### 3. Controller Updates
- Remove shift assignment endpoints if no longer needed
- Update manual entry to use business hours
- Modify reports to use new calculation methods

#### 4. View Updates
- Remove shift display from attendance listings
- Update dashboard statistics calculation
- Modify filters to use business hours instead of shifts

### Business Logic Implementation

#### Core Algorithms:

```php
// Determine if clock-in is late
function isLateCheckIn($checkInTime, $businessOpen, $gracePeriod) {
    $graceEnd = $businessOpen->addMinutes($gracePeriod);
    return $checkInTime > $graceEnd;
}

// Calculate overtime
function calculateOvertime($checkOutTime, $businessClose) {
    if ($checkOutTime <= $businessClose) {
        return 0;
    }
    return $checkOutTime->diffInMinutes($businessClose);
}

// Determine attendance status
function determineStatus($checkInTime, $checkOutTime, $businessHours) {
    if (!$checkInTime) return 'absent';
    
    $isLate = isLateCheckIn($checkInTime, $businessHours['open'], $gracePeriod);
    
    if ($checkOutTime) {
        $hoursWorked = calculateHoursWorked($checkInTime, $checkOutTime);
        if ($hoursWorked < $minimumHours) return 'half_day';
    }
    
    return $isLate ? 'late' : 'present';
}
```

### Risk Assessment & Mitigation

#### Risks:
1. **Data loss** during migration
2. **Calculation discrepancies** between old and new systems
3. **Performance impact** of recalculating historical data
4. **User confusion** with changed status calculations

#### Mitigations:
1. **Backup all data** before migration
2. **Run parallel calculations** for validation
3. **Phase rollout** with feature flags
4. **User training** and documentation updates

### Rollback Plan
1. Keep shift tables intact during transition
2. Maintain backward-compatible APIs
3. Feature flag to switch between systems
4. Quick rollback script to restore shift-based calculations

## Implementation Timeline

### Phase 1: Foundation (Week 1)
- Design finalization and approval
- Database schema changes
- Business hours configuration UI

### Phase 2: Core Logic (Week 2)
- AttendanceService refactoring
- Attendance model updates
- Unit test creation

### Phase 3: Migration (Week 3)
- Data migration scripts
- Historical data conversion
- Validation and testing

### Phase 4: Integration (Week 4)
- Controller and view updates
- Report modifications
- User acceptance testing

### Phase 5: Deployment (Week 5)
- Production migration
- Monitoring and support
- Documentation updates

## Success Metrics
1. All attendance calculations use business hours
2. No shift references in codebase
3. Historical data accurately converted
4. Performance maintained or improved
5. User satisfaction with new system

## Next Steps
1. Review and approve this design
2. Create detailed technical specifications
3. Begin implementation in Code mode
4. Schedule migration window
5. Plan user training sessions