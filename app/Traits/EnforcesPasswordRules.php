<?php

namespace App\Traits;

use App\Models\Setting;

trait EnforcesPasswordRules
{
    /**
     * Build password validation rules based on Security settings.
     *
     * @param  bool  $required  Whether password is required (true) or nullable/optional (false)
     * @return array<int, string>
     */
    private function getPasswordRules(bool $required = true): array
    {
        // Read settings with safe defaults, wrapped in try/catch
        // to handle missing settings table during early lifecycle.
        try {
            $minLength = (int) Setting::get('security.password_min_length', 8);
            $requireUppercase = Setting::get('security.password_require_uppercase', true);
            $requireNumbers = Setting::get('security.password_require_numbers', true);
            $requireSpecial = Setting::get('security.password_require_special', false);
        } catch (\Exception $e) {
            $minLength = 8;
            $requireUppercase = true;
            $requireNumbers = true;
            $requireSpecial = false;
        }

        $rules = $required
            ? ['required', 'string', "min:{$minLength}", 'confirmed']
            : ['nullable', 'string', "min:{$minLength}", 'confirmed'];

        if (filter_var($requireUppercase, FILTER_VALIDATE_BOOLEAN)) {
            $rules[] = 'regex:/[A-Z]/';
        }
        if (filter_var($requireNumbers, FILTER_VALIDATE_BOOLEAN)) {
            $rules[] = 'regex:/[0-9]/';
        }
        if (filter_var($requireSpecial, FILTER_VALIDATE_BOOLEAN)) {
            $rules[] = 'regex:/[^a-zA-Z0-9]/';
        }

        return $rules;
    }
}
