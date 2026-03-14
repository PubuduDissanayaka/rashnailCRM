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

class ReportGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The report type.
     *
     * @var string
     */
    public $reportType;

    /**
     * The user who generated the report.
     *
     * @var \App\Models\User
     */
    public $generatedBy;

    /**
     * The report data.
     *
     * @var array
     */
    public $reportData;

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
     * Create a new event instance.
     *
     * @param string $reportType
     * @param \App\Models\User $generatedBy
     * @param array $reportData
     * @param string|null $downloadUrl
     * @param string|null $filePath
     * @param int|null $fileSize
     * @param string $format
     * @return void
     */
    public function __construct(
        string $reportType,
        User $generatedBy,
        array $reportData = [],
        ?string $downloadUrl = null,
        ?string $filePath = null,
        ?int $fileSize = null,
        string $format = 'csv'
    ) {
        $this->reportType = $reportType;
        $this->generatedBy = $generatedBy;
        $this->reportData = $reportData;
        $this->downloadUrl = $downloadUrl;
        $this->filePath = $filePath;
        $this->fileSize = $fileSize;
        $this->format = $format;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('reports');
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
}