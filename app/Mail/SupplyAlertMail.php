<?php

namespace App\Mail;

use App\Models\SupplyAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupplyAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $alert;
    public $severityColor;
    public $appName;

    /**
     * Create a new message instance.
     */
    public function __construct(SupplyAlert $alert)
    {
        $this->alert = $alert;
        $this->appName = config('app.name');
        
        // Set severity color for email styling
        $this->severityColor = match($alert->severity) {
            'critical' => '#dc3545',
            'warning' => '#ffc107',
            'info' => '#0dcaf0',
            default => '#6c757d',
        };
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = "[{$this->alert->severity}] Inventory Alert: " . ucfirst(str_replace('_', ' ', $this->alert->alert_type));
        
        return $this->subject($subject)
                    ->view('emails.supply-alert')
                    ->with([
                        'alert' => $this->alert,
                        'severityColor' => $this->severityColor,
                        'appName' => $this->appName,
                    ]);
    }
}