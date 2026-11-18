<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Coupon;

/**
 * Validation for creating and updating a coupon.
 *
 * Shared by CouponController::store() and ::update() so both paths enforce the
 * same rules, the same friendly messages, and the same normalisation.
 */
class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorisation is enforced in the controller (create/edit coupons abilities).
        return true;
    }

    /**
     * Normalise the payload before validation: uppercase the code, turn on/off
     * switches into real booleans, map the discount column by coupon type, and
     * convert empty optional inputs into nulls.
     */
    protected function prepareForValidation(): void
    {
        $type = $this->input('type');

        $merge = [
            'code' => strtoupper(trim((string) $this->input('code'))),
            'stackable' => $this->boolean('stackable'),
            'active' => $this->boolean('active'),
            'timezone' => $this->input('timezone') ?: config('app.timezone', 'Asia/Colombo'),
        ];

        foreach (['description', 'discount_value', 'discount_percentage', 'max_discount_amount',
                  'minimum_purchase_amount', 'total_usage_limit', 'per_customer_limit',
                  'end_date', 'metadata', 'batch_id'] as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $merge[$field] = null;
            }
        }

        // The coupon stores one discount_value column; which input feeds it
        // depends on the type chosen in the form.
        if ($type === 'percentage') {
            $merge['discount_value'] = $this->input('discount_percentage');
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        $couponId = $this->route('coupon')?->id;

        return [
            // `coupons.code` is unique across live AND soft-deleted rows (deleted
            // coupons keep their redemption history), so the check has to look
            // at trashed rows too and explain which case it is.
            'code' => [
                'required', 'string', 'max:50',
                function ($attribute, $value, $fail) use ($couponId) {
                    $existing = Coupon::withTrashed()
                        ->where('code', $value)
                        ->when($couponId, fn ($q) => $q->where('id', '!=', $couponId))
                        ->first();

                    if (! $existing) {
                        return;
                    }

                    $fail($existing->trashed()
                        ? 'Coupon code ' . $value . ' belongs to a deleted coupon. Choose a different code.'
                        : 'A coupon with the code ' . $value . ' already exists. Choose a different code.');
                },
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'type' => 'required|in:percentage,fixed,bogo,free_shipping,tiered',
            'discount_percentage' => 'nullable|numeric|min:0.01|max:100|required_if:type,percentage',
            'discount_value' => 'nullable|numeric|min:0.01|required_if:type,fixed',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'minimum_purchase_amount' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'timezone' => 'required|string|max:64',
            'total_usage_limit' => 'nullable|integer|min:1',
            'per_customer_limit' => 'nullable|integer|min:1',
            'stackable' => 'boolean',
            'active' => 'boolean',
            'location_restriction_type' => 'required|in:all,specific',
            'customer_eligibility_type' => 'required|in:all,new,existing,groups',
            'product_restriction_type' => 'required|in:all,specific,categories',
            'metadata' => 'nullable|json',
            'batch_id' => 'nullable|exists:coupon_batches,id',
            'customer_groups' => 'nullable|array',
            'customer_groups.*' => 'exists:customer_groups,id',
            'locations' => 'nullable|array',
            'locations.*' => 'exists:locations,id',
            'products' => 'nullable|array',
            'products.*' => ['string', 'regex:/^(service|package)_\d+$/'],
            'categories' => 'nullable|array',
            'categories.*' => 'exists:service_package_categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Enter a coupon code.',
            'name.required' => 'Enter a coupon name.',
            'type.required' => 'Choose a coupon type.',
            'discount_percentage.required_if' => 'Enter the discount percentage for a percentage coupon.',
            'discount_percentage.max' => 'The discount percentage cannot be more than 100.',
            'discount_value.required_if' => 'Enter the discount amount for a fixed amount coupon.',
            'start_date.required' => 'Choose the date this coupon starts.',
            'end_date.after_or_equal' => 'The end date must be the same as, or later than, the start date.',
            'timezone.required' => 'Choose the timezone this coupon runs in.',
            'total_usage_limit.min' => 'The total usage limit must be at least 1, or left empty for unlimited.',
            'per_customer_limit.min' => 'The per customer limit must be at least 1, or left empty for unlimited.',
            'metadata.json' => 'Metadata must be valid JSON.',
            'batch_id.exists' => 'The selected batch no longer exists.',
            'categories.*.exists' => 'One of the selected categories no longer exists.',
            'locations.*.exists' => 'One of the selected locations no longer exists.',
            'customer_groups.*.exists' => 'One of the selected customer groups no longer exists.',
        ];
    }
}
