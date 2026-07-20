<?php

namespace App\Helpers;

use App\Models\Setting;

class PhoneHelper
{
    /**
     * Format a phone number for WhatsApp-compatible storage.
     *
     * - Strips all non-numeric characters (+, spaces, dashes, parens)
     * - If number starts with 0, replaces leading 0 with country code from settings
     * - Returns pure digits only (e.g. 94771234567 for Sri Lanka)
     *
     * @param string|null $phoneNumber
     * @return string|null
     */
    public static function formatForStorage(?string $phoneNumber): ?string
    {
        if (!$phoneNumber) {
            return $phoneNumber;
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);

        // If phone starts with 0, replace leading 0 with country code
        if (strlen($phone) > 0 && $phone[0] === '0') {
            $countryCode = Setting::get('business.country_code', '94');
            $phone = $countryCode . substr($phone, 1);
        }

        // Return pure numbers only (WhatsApp compatible — no + prefix)
        return $phone;
    }
}
