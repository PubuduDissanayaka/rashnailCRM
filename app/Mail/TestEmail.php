<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Test Email - ' . config('app.name'))
                    ->html('
                        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                            <h1 style="color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px;">
                                Test Email Successful
                            </h1>
                            <p style="color: #666; font-size: 16px; line-height: 1.6;">
                                This is a test email from your notification system.
                            </p>
                            <p style="color: #666; font-size: 16px; line-height: 1.6;">
                                If you received this email, your email provider is configured correctly!
                            </p>
                            <div style="background-color: #f4f4f4; padding: 15px; border-radius: 5px; margin-top: 20px;">
                                <p style="margin: 0; color: #666; font-size: 14px;">
                                    <strong>Application:</strong> ' . config('app.name') . '<br>
                                    <strong>Time:</strong> ' . now()->format('Y-m-d H:i:s') . '<br>
                                    <strong>Environment:</strong> ' . config('app.env') . '
                                </p>
                            </div>
                        </div>
                    ');
    }
}
