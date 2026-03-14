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
        Schema::table('service_packages', function (Blueprint $table) {
            if (!Schema::hasColumn('service_packages', 'slug')) {
                $table->string('slug')->unique()->after('name');
            }
        });

        // Generate slugs for existing packages
        if (Schema::hasTable('service_packages')) {
            $packages = \App\Models\ServicePackage::all();
            foreach ($packages as $package) {
                $package->update(['slug' => \Illuminate\Support\Str::slug($package->name)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};