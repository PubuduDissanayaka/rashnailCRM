<?php

namespace App\DTOs;

use Carbon\Carbon;

class FilterCriteria
{
    public Carbon $startDate;
    public Carbon $endDate;
    public ?int $staffId = null;
    public ?int $roleId = null;
    public ?string $status = null;
    public ?string $exportFormat = null;

    /**
     * Create FilterCriteria from request data
     *
     * @param array $data
     * @return self
     */
    public static function fromRequest(array $data): self
    {
        $criteria = new self();

        // Set date range with defaults
        $criteria->startDate = isset($data['start_date']) 
            ? Carbon::parse($data['start_date'])->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();
            
        $criteria->endDate = isset($data['end_date'])
            ? Carbon::parse($data['end_date'])->endOfDay()
            : Carbon::now()->endOfDay();

        // Validate date range (max 1 year for performance)
        $maxDateRange = $criteria->startDate->copy()->addYear();
        if ($criteria->endDate->greaterThan($maxDateRange)) {
            $criteria->endDate = $maxDateRange;
        }

        // Set optional filters
        $criteria->staffId = isset($data['staff_id']) && !empty($data['staff_id'])
            ? (int) $data['staff_id']
            : null;

        $criteria->roleId = isset($data['role_id']) && !empty($data['role_id'])
            ? (int) $data['role_id']
            : null;

        $criteria->status = isset($data['status']) && !empty($data['status'])
            ? $data['status']
            : null;

        $criteria->exportFormat = isset($data['export_format']) && !empty($data['export_format'])
            ? $data['export_format']
            : null;

        return $criteria;
    }

    /**
     * Check if staff filter is applied
     *
     * @return bool
     */
    public function hasStaffFilter(): bool
    {
        return $this->staffId !== null;
    }

    /**
     * Check if role filter is applied
     *
     * @return bool
     */
    public function hasRoleFilter(): bool
    {
        return $this->roleId !== null;
    }

    /**
     * Check if status filter is applied
     *
     * @return bool
     */
    public function hasStatusFilter(): bool
    {
        return $this->status !== null;
    }

    /**
     * Check if export is requested
     *
     * @return bool
     */
    public function isExportRequested(): bool
    {
        return $this->exportFormat !== null;
    }

    /**
     * Get date range as array
     *
     * @return array
     */
    public function getDateRangeArray(): array
    {
        return [
            'start' => $this->startDate->format('Y-m-d'),
            'end' => $this->endDate->format('Y-m-d'),
            'days' => $this->startDate->diffInDays($this->endDate) + 1,
        ];
    }

    /**
     * Get filter description for display
     *
     * @return string
     */
    public function getFilterDescription(): string
    {
        $parts = [];

        $parts[] = "Period: {$this->startDate->format('M d, Y')} to {$this->endDate->format('M d, Y')}";

        if ($this->hasStaffFilter()) {
            $parts[] = "Staff: #{$this->staffId}";
        }

        if ($this->hasRoleFilter()) {
            $parts[] = "Role: #{$this->roleId}";
        }

        if ($this->hasStatusFilter()) {
            $parts[] = "Status: " . ucfirst($this->status);
        }

        return implode(' | ', $parts);
    }

    /**
     * Validate the filter criteria
     *
     * @return array Array of validation errors, empty if valid
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->startDate->greaterThan($this->endDate)) {
            $errors[] = 'Start date cannot be after end date';
        }

        if ($this->startDate->diffInDays($this->endDate) > 366) {
            $errors[] = 'Date range cannot exceed 1 year for performance reasons';
        }

        if ($this->exportFormat && !in_array($this->exportFormat, ['csv', 'pdf'])) {
            $errors[] = 'Invalid export format. Must be csv or pdf';
        }

        if ($this->status && !in_array($this->status, ['present', 'late', 'absent', 'leave', 'half_day'])) {
            $errors[] = 'Invalid status value';
        }

        return $errors;
    }
}