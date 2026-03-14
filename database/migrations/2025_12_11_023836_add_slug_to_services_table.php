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
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'slug')) {
                $table->string('slug')->nullable()->after('is_active');
                
                // Generate slugs for existing services
                $services = \App\Models\Service::all();
                foreach ($services as $service) {
                    $service->update(['slug' => \Illuminate\Support\Str::slug($service->name . '-' . $service->id)]);
                }

                // Make the slug column unique and not nullable after populating values
                $table->string('slug')->nullable(false)->change();
                $table->unique('slug');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};