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

class ReportGenerationFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The report type.
     *
     * @var string
     */
    public $reportType;

    /**
     * The user who attempted to generate the report.
     *
     * @var \App\Models\User|null
     */
    public $generatedBy;

    /**
     * The error message.
     *
     * @var string
     */
    public $errorMessage;

    /**
     * The error code.
     *
     * @var string|null
     */
    public $errorCode;

    /**
     * The stack trace or additional error details.
     *
     * @var array|null
     */
    public $errorDetails;

    /**
     * The timestamp when the failure occurred.
     *
     * @var \Illuminate\Support\Carbon
     */
    public $failedAt;

    /**
     * Create a new event instance.
     *
     * @param string $reportType
     * @param \App\Models\User|null $generatedBy
     * @param string $errorMessage
     * @param string|null $errorCode
     * @param array|null $errorDetails
     * @return void
     */
    public function __construct(
        string $reportType,
        ?User $generatedBy,
        string $errorMessage,
        ?string $errorCode = null,
        ?array $errorDetails = null
    ) {
        $this->reportType = $reportType;
        $this->generatedBy = $generatedBy;
        $this->errorMessage = $errorMessage;
        $this->errorCode = $errorCode;
        $this->errorDetails = $errorDetails;
        $this->failedAt = now();
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
     * Get formatted error details.
     *
     * @return string
     */
    public function getFormattedErrorDetails(): string
    {
        if (empty($this->errorDetails)) {
            return $this->errorMessage;
        }

        $details = $this->errorMessage . "\n\n";

        if ($this->errorCode) {
            $details .= "Error Code: {$this->errorCode}\n";
        }

        if (isset($this->errorDetails['exception'])) {
            $details .= "Exception: {$this->errorDetails['exception']}\n";
        }

        if (isset($this->errorDetails['file'])) {
            $details .= "File: {$this->errorDetails['file']}\n";
        }

        if (isset($this->errorDetails['line'])) {
            $details .= "Line: {$this->errorDetails['line']}\n";
        }

        return $details;
    }
}