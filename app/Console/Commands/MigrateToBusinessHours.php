<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\ShiftAssignment;
use App\Models\Shift;
use App\Services\BusinessHoursService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MigrateToBusinessHours extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:migrate-to-business-hours 
                            {--dry-run : Preview changes without saving}
                            {--batch-size=100 : Number of records to process at a time}
                            {--start-date= : Start date for migration (YYYY-MM-DD)}
                            {--end-date= : End date for migration (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing attendance records from shift-based to business hours-based system';

    /**
     * Business hours service instance.
     *
     * @var BusinessHoursService
     */
    protected $businessHoursService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->businessHoursService = app(BusinessHoursService::class);
        
        $this->info('Starting migration from shift-based to business hours-based attendance system...');
        
        if ($this->option('dry-run')) {
            $this->info('DRY RUN MODE: No changes will be saved.');
        }
        
        // Get migration parameters
        $startDate = $this->option('start-date') ? Carbon::parse($this->option('start-date')) : null;
        $endDate = $this->option('end-date') ? Carbon::parse($this->option('end-date')) : null;
        $batchSize = (int) $this->option('batch-size');
        
        // Build query
        $query = Attendance::query();
        
        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }
        
        $totalRecords = $query->count();
        $this->info("Found {$totalRecords} attendance records to migrate.");
        
        if ($totalRecords === 0) {
            $this->warn('No records found to migrate.');
            return;
        }
        
        // Process in batches
        $processed = 0;
        $updated = 0;
        $skipped = 0;
        
        $query->orderBy('date')->chunk($batchSize, function ($attendances) use (&$processed, &$updated, &$skipped) {
            foreach ($attendances as $attendance) {
                $processed++;
                
                $this->line("Processing record {$processed}: ID {$attendance->id}, Date {$attendance->date}, User {$attendance->user_id}");
                
                // Get original shift data for reference
                $shiftData = $this->getShiftDataForAttendance($attendance);
                
                // Calculate business hours for this date
                $businessHoursConfig = $this->businessHoursService->getConfig();
                $businessHoursForDate = $this->businessHoursService->getHoursForDate($attendance->date);
                
                if (!$businessHoursForDate || !$businessHoursForDate['enabled']) {
                    $this->warn("  Skipping: No business hours configured for {$attendance->date}");
                    $skipped++;
                    continue;
                }
                
                // Determine business hours type
                $businessHoursType = $this->determineBusinessHoursType($attendance, $businessHoursConfig);
                
                // Calculate expected hours based on business hours
                $expectedHours = $this->calculateExpectedHours($attendance, $businessHoursConfig, $businessHoursType);
                
                // Map half_day to regular since enum doesn't have half_day
                $mappedType = $businessHoursType === 'half_day' ? 'regular' : $businessHoursType;
                
                // Update attendance record
                $updateData = [
                    'business_hours_type' => $mappedType,
                    'expected_hours' => $expectedHours,
                    'calculated_using_business_hours' => true,
                ];
                
                // Preserve shift data in meta if needed
                if ($shiftData) {
                    $meta = $attendance->meta ?? [];
                    $meta['migrated_from_shift'] = $shiftData;
                    $updateData['meta'] = $meta;
                }
                
                if (!$this->option('dry-run')) {
                    $attendance->update($updateData);
                    $updated++;
                    $this->info("  Updated: Type={$businessHoursType}, Expected={$expectedHours}h");
                } else {
                    $this->info("  Would update: Type={$businessHoursType}, Expected={$expectedHours}h");
                }
            }
            
            $this->info("Batch processed: {$processed} total, {$updated} updated, {$skipped} skipped");
        });
        
        // Summary
        $this->newLine();
        $this->info('=== MIGRATION SUMMARY ===');
        $this->info("Total records processed: {$processed}");
        $this->info("Records updated: {$updated}");
        $this->info("Records skipped: {$skipped}");
        
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN COMPLETED: No changes were saved.');
            $this->info('To apply changes, run without --dry-run option.');
        } else {
            $this->info('Migration completed successfully!');
            
            // Optional: Archive shift data
            $this->info('Consider archiving shift data after verifying migration:');
            $this->info('  - Shift assignments can be safely deleted');
            $this->info('  - Shifts table can be archived or removed');
            $this->info('  - Run php artisan attendance:archive-shift-data after verification');
        }
    }
    
    /**
     * Get shift data for an attendance record.
     *
     * @param Attendance $attendance
     * @return array|null
     */
    private function getShiftDataForAttendance(Attendance $attendance): ?array
    {
        $shiftAssignment = ShiftAssignment::where('user_id', $attendance->user_id)
            ->whereDate('effective_date', $attendance->date)
            ->first();
            
        if (!$shiftAssignment) {
            return null;
        }
        
        $shift = Shift::find($shiftAssignment->shift_id);
        
        return [
            'shift_id' => $shiftAssignment->shift_id,
            'shift_name' => $shift ? $shift->name : null,
            'shift_start' => $shift ? $shift->start_time : null,
            'shift_end' => $shift ? $shift->end_time : null,
            'assignment_id' => $shiftAssignment->id,
        ];
    }
    
    /**
     * Determine business hours type for an attendance record.
     *
     * @param Attendance $attendance
     * @param array $businessHoursConfig
     * @return string
     */
    private function determineBusinessHoursType(Attendance $attendance, array $businessHoursConfig): string
    {
        $date = Carbon::parse($attendance->date);
        $dayOfWeek = strtolower($date->format('l'));
        
        // Check if it's a weekend
        if (!($businessHoursConfig['business_hours'][$dayOfWeek]['enabled'] ?? false)) {
            return 'weekend';
        }
        
        // Check if it's a holiday
        if ($this->businessHoursService->getBusinessDayType($date) === 'holiday') {
            return 'holiday';
        }
        
        // Check for half day
        if ($attendance->status === 'half_day' || $attendance->hours_worked < 4) {
            return 'half_day';
        }
        
        // Default to regular business day
        return 'regular';
    }
    
    /**
     * Calculate expected hours based on business hours and type.
     *
     * @param Attendance $attendance
     * @param array $businessHoursConfig
     * @param string $type
     * @return float
     */
    private function calculateExpectedHours(Attendance $attendance, array $businessHoursConfig, string $type): float
    {
        $date = Carbon::parse($attendance->date);
        $dayOfWeek = strtolower($date->format('l'));
        
        switch ($type) {
            case 'weekend':
            case 'holiday':
                return 0.0;
                
            case 'half_day':
                $daySchedule = $businessHoursConfig['business_hours'][$dayOfWeek] ?? null;
                if ($daySchedule && $daySchedule['enabled'] && $daySchedule['open'] && $daySchedule['close']) {
                    // Half of the regular business hours
                    $open = Carbon::createFromFormat('H:i', $daySchedule['open']);
                    $close = Carbon::createFromFormat('H:i', $daySchedule['close']);
                    // Ensure both times are on the same day
                    $close->setDate($open->year, $open->month, $open->day);
                    $totalHours = abs($close->diffInMinutes($open)) / 60;
                    return round($totalHours / 2, 2);
                }
                return 4.0; // Default half day
                
            case 'regular':
            default:
                $daySchedule = $businessHoursConfig['business_hours'][$dayOfWeek] ?? null;
                if ($daySchedule && $daySchedule['enabled'] && $daySchedule['open'] && $daySchedule['close']) {
                    $open = Carbon::createFromFormat('H:i', $daySchedule['open']);
                    $close = Carbon::createFromFormat('H:i', $daySchedule['close']);
                    // Ensure both times are on the same day
                    $close->setDate($open->year, $open->month, $open->day);
                    $totalHours = abs($close->diffInMinutes($open)) / 60;
                    return round($totalHours, 2);
                }
                return 8.0; // Default full day
        }
    }
}