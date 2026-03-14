<?php

namespace App\Observers;

use App\Models\Appointment;

class AppointmentObserver
{
    /**
     * Handle the Appointment "updated" event.
     */
    public function updated(Appointment $appointment): void
    {
        // Check if status changed to 'completed'
        if ($appointment->isDirty('status') && $appointment->status === 'completed') {
            // Auto-deduct supplies for the appointment
            $appointment->deductSupplies();
        }
    }

    /**
     * Handle the Appointment "deleted" event.
     */
    public function deleted(Appointment $appointment): void
    {
        // If appointment is deleted, we might want to restore stock
        // This is optional and can be implemented later
    }

    /**
     * Handle the Appointment "restored" event.
     */
    public function restored(Appointment $appointment): void
    {
        // If appointment is restored, we might want to deduct stock again
        // This is optional and can be implemented later
    }
}