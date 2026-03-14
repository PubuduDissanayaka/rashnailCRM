<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Populate slugs for customers that don't have them
        $customers = \App\Models\Customer::whereNull('slug')->get();

        foreach ($customers as $customer) {
            $baseSlug = \Illuminate\Support\Str::slug($customer->first_name . '-' . $customer->last_name);
            $slug = $baseSlug;

            // Ensure uniqueness by checking for existing slugs
            $counter = 1;
            while (\App\Models\Customer::where('slug', $slug)->where('id', '!=', $customer->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $customer->update(['slug' => $slug]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove slugs from customers that were added by this migration
        \App\Models\Customer::whereNotNull('slug')->update(['slug' => null]);
    }
};
