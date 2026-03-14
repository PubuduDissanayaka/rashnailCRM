# Business Hours Attendance System - Implementation Todo List

## Phase 1: Database Schema Changes

### 1.1 Create Business Hours Migration
- [ ] Create migration: `2025_12_24_000001_add_business_hours_to_settings.php`
- [ ] Add business hours configuration to settings table
- [ ] Add default business hours (Mon-Fri 9am-5pm, Sat-Sun closed)
- [ ] Add grace period, overtime threshold settings

### 1.2 Update Attendances Table
- [ ] Create migration: `2025_12_24_000002_add_business_hours_fields_to_attendances.php`
- [ ] Add `business_hours_type` enum('regular', 'weekend', 'holiday', 'special')
- [ ] Add `expected_hours` decimal(5,2) nullable
- [ ] Add `calculated_using_business_hours` boolean default false
- [ ] Add indexes for new fields

### 1.3 Create Archive Tables (Optional)
- [ ] Create `archived_shifts` table (copy of shifts)
- [ ] Create `archived_shift_assignments` table
- [ ] Create migration to copy existing data

## Phase 2: Business Hours Configuration

### 2.1 Settings Model Updates
- [ ] Update Setting model to handle business hours JSON
- [ ] Add helper methods: `getBusinessHours()`, `setBusinessHours()`
- [ ] Add validation for business hours structure

### 2.2 Business Hours Service
- [ ] Create `BusinessHoursService` class
- [ ] Methods: `getHoursForDate()`, `isBusinessDay()`, `getNextBusinessDay()`
- [ ] Calculate expected hours based on business hours configuration
- [ ] Handle holidays integration

### 2.3 Admin Interface
- [ ] Create business hours settings page
- [ ] Add form to configure daily business hours
- [ ] Add grace period, overtime threshold configuration
- [ ] Add validation and save logic

## Phase 3: AttendanceService Refactoring

### 3.1 Remove Shift Dependencies
- [ ] Remove `getUserShiftForDate()` method
- [ ] Replace with `getBusinessHoursForDate()` 
- [ ] Update method signatures to remove shift parameters

### 3.2 Check-In Logic
- [ ] Update `checkIn()` to use business hours for status determination
- [ ] Calculate late arrival based on business open time + grace period
- [ ] Set `business_hours_type` based on date (regular, weekend, holiday)
- [ ] Set `expected_hours` based on business hours configuration

### 3.3 Check-Out Logic
- [ ] Update `checkOut()` to calculate overtime based on business close time
- [ ] Calculate early departure minutes
- [ ] Update hours worked calculation
- [ ] Set `calculated_using_business_hours` to true

### 3.4 Break Management
- [ ] Keep existing break logic (unchanged)
- [ ] Ensure break deductions work with new hour calculations

### 3.5 Notification Updates
- [ ] Update notification messages to reference business hours instead of shifts
- [ ] Update late check-in notifications

## Phase 4: Attendance Model Updates

### 4.1 Calculation Methods
- [ ] Replace `isLateCheckIn()` with business hours-based logic
- [ ] Replace `calculateOvertimeMinutes()` 
- [ ] Replace `calculateEarlyDepartureMinutes()`
- [ ] Replace `calculateLateArrivalMinutes()`
- [ ] Add `getBusinessHoursForDate()` helper

### 4.2 New Business Methods
- [ ] Add `isWithinBusinessHours()` method
- [ ] Add `getExpectedCheckInTime()` 
- [ ] Add `getExpectedCheckOutTime()`
- [ ] Add `getBusinessDayType()` (regular, weekend, holiday)

### 4.3 Accessor Updates
- [ ] Update status badges to reflect business hours logic
- [ ] Update formatted time displays
- [ ] Add business hours information to JSON serialization

## Phase 5: Controller Updates

### 5.1 AttendanceController
- [ ] Remove shift assignment endpoints if unused
- [ ] Update manual entry to use business hours
- [ ] Update report generation
- [ ] Update dashboard statistics

### 5.2 API Endpoints
- [ ] Update `todayDetails()` to include business hours info
- [ ] Update `userStatistics()` for new calculations
- [ ] Ensure backward compatibility where needed

### 5.3 Validation Updates
- [ ] Update request validation for business hours context
- [ ] Add validation for clock-in/out within reasonable business hours

## Phase 6: View Updates

### 6.1 Attendance Index Views
- [ ] Remove shift information columns
- [ ] Add business hours context display
- [ ] Update status indicators
- [ ] Update filters to use business days

### 6.2 Dashboard Views
- [ ] Update statistics calculation
- [ ] Update charts and graphs
- [ ] Add business hours configuration link

### 6.3 Report Views
- [ ] Update report columns
- [ ] Update summary calculations
- [ ] Add business hours context to exports

### 6.4 Staff Views
- [ ] Update staff attendance display
- [ ] Remove shift assignment information
- [ ] Update monthly summaries

## Phase 7: Data Migration

### 7.1 Migration Scripts
- [ ] Create `MigrateToBusinessHoursCommand` artisan command
- [ ] Script to calculate business hours for existing attendances
- [ ] Script to update status based on new rules
- [ ] Script to archive shift data

### 7.2 Historical Data
- [ ] Option to recalculate historical attendance
- [ ] Validation script to compare old vs new calculations
- [ ] Backup and rollback procedures

### 7.3 Shift Data Handling
- [ ] Archive current shifts and assignments
- [ ] Provide access to historical shift data for reporting
- [ ] Update any reports that reference shifts

## Phase 8: Testing

### 8.1 Unit Tests
- [ ] Test BusinessHoursService methods
- [ ] Test AttendanceService with business hours
- [ ] Test Attendance model calculations
- [ ] Test edge cases (weekends, holidays, early/late)

### 8.2 Integration Tests
- [ ] Test check-in/out flow with business hours
- [ ] Test break management
- [ ] Test report generation
- [ ] Test migration scripts

### 8.3 Feature Tests
- [ ] Test admin configuration interface
- [ ] Test user attendance flow
- [ ] Test manager approval workflow
- [ ] Test export functionality

## Phase 9: Deployment

### 9.1 Preparation
- [ ] Database backups
- [ ] Staging environment testing
- [ ] User notification and documentation
- [ ] Rollback plan verification

### 9.2 Migration Execution
- [ ] Run schema migrations
- [ ] Run data migration scripts
- [ ] Verify data integrity
- [ ] Enable new business hours logic

### 9.3 Post-Deployment
- [ ] Monitor system performance
- [ ] Address any calculation discrepancies
- [ ] Update user documentation
- [ ] Gather user feedback

## Phase 10: Cleanup

### 10.1 Code Cleanup
- [ ] Remove unused shift-related code
- [ ] Remove shift model and relationships
- [ ] Clean up migration artifacts
- [ ] Update documentation

### 10.2 Database Cleanup
- [ ] Drop shift tables after verification period
- [ ] Remove deprecated columns
- [ ] Optimize indexes
- [ ] Update database documentation

## Dependencies & Prerequisites

### Required Before Starting:
1. Database backup strategy in place
2. Staging environment for testing
3. User communication plan
4. Rollback procedures documented

### External Dependencies:
1. Holidays table must be populated
2. Settings table must be accessible
3. Existing attendance data must be valid

## Risk Mitigation

### High Risk Items:
1. **Data migration errors** - Test thoroughly on staging
2. **Calculation discrepancies** - Run parallel calculations
3. **Performance impact** - Monitor query performance
4. **User confusion** - Provide clear documentation

### Mitigation Strategies:
1. Feature flag for gradual rollout
2. Side-by-side comparison reports
3. Performance benchmarking
4. User training sessions

## Success Criteria

### Technical:
1. All attendance calculations use business hours
2. No shift references in codebase
3. Migration scripts complete without errors
4. Performance metrics maintained

### Business:
1. Accurate attendance tracking
2. Correct overtime calculations
3. Proper holiday handling
4. User acceptance of new system

## Next Actions

### Immediate (Week 1):
1. Review and finalize design
2. Create detailed technical specifications
3. Set up testing environment
4. Begin Phase 1 implementation

### Short-term (Week 2-3):
1. Complete database changes
2. Implement core business logic
3. Create migration scripts
4. Begin testing

### Medium-term (Week 4-5):
1. Complete view updates
2. Run full integration tests
3. Prepare deployment plan
4. User training materials

### Long-term (Week 6+):
1. Production deployment
2. Post-deployment monitoring
3. Cleanup of old code
4. System optimization