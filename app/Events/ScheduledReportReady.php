<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScheduledReportReady
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The report type.
     *
     * @var string
     */
    public $reportType;

    /**
     * The schedule frequency.
     *
     * @var string
     */
    public $scheduleFrequency;

    /**
     * The users who should receive the report.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    public $recipients;

    /**
     * The download URL for the report.
     *
     * @var string|null
     */
    public $downloadUrl;

    /**
     * The file path of the generated report.
     *
     * @var string|null
     */
    public $filePath;

    /**
     * The file size in bytes.
     *
     * @var int|null
     */
    public $fileSize;

    /**
     * The format of the report (csv, pdf, excel).
     *
     * @var string
     */
    public $format;

    /**
     * The period covered by the report.
     *
     * @var array
     */
    public $period;

    /**
     * Create a new event instance.
     *
     * @param string $reportType
     * @param string $scheduleFrequency
     * @param \Illuminate\Database\Eloquent\Collection $recipients
     * @param string|null $downloadUrl
     * @param string|null $filePath
     * @param int|null $fileSize
     * @param string $format
     * @param array $period
     * @return void
     */
    public function __construct(
        string $reportType,
        string $scheduleFrequency,
        $recipients,
        ?string $downloadUrl = null,
        ?string $filePath = null,
        ?int $fileSize = null,
        string $format = 'csv',
        array $period = []
    ) {
        $this->reportType = $reportType;
        $this->scheduleFrequency = $scheduleFrequency;
        $this->recipients = $recipients;
        $this->downloadUrl = $downloadUrl;
        $this->filePath = $filePath;
        $this->fileSize = $fileSize;
        $this->format = $format;
        $this->period = $period;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('scheduled-reports');
    }

    /**
     * Get human-readable report name.
     *
     * @return string
     */
    public function getReportName(): string
    {
        $names = [
            'work_hour' => 'Work Hour Report',
            'attendance' => 'Attendance Report',
            'staff_summary' => 'Staff Summary Report',
            'export' => 'Data Export',
        ];

        return $names[$this->reportType] ?? ucfirst(str_replace('_', ' ', $this->reportType));
    }

    /**
     * Get human-readable schedule frequency.
     *
     * @return string
     */
    public function getScheduleFrequencyName(): string
    {
        $frequencies = [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
        ];

        return $frequencies[$this->scheduleFrequency] ?? ucfirst($this->scheduleFrequency);
    }

    /**
     * Get formatted file size.
     *
     * @return string
     */
    public function getFormattedFileSize(): string
    {
        if (!$this->fileSize) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->fileSize;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get formatted period.
     *
     * @return string
     */
    public function getFormattedPeriod(): string
    {
        if (empty($this->period)) {
            return 'N/A';
        }

        if (isset($this->period['start']) && isset($this->period['end'])) {
            $start = is_string($this->period['start']) ? $this->period['start'] : $this->period['start']->format('Y-m-d');
            $end = is_string($this->period['end']) ? $this->period['end'] : $this->period['end']->format('Y-m-d');
            
            if ($start === $end) {
                return $start;
            }
            
            return $start . ' to ' . $end;
        }

        return json_encode($this->period);
    }
}