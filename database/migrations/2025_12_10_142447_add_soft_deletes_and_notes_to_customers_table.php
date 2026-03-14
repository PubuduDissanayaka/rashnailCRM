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
        Schema::table('customers', function (Blueprint $table) {
            $table->softDeletes(); // Add soft deletes
            $table->text('notes')->nullable()->after('gender'); // Add notes column
            $table->string('slug')->nullable()->after('gender'); // Add slug column for URL-friendly identification
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropSoftDeletes(); // Remove soft deletes
            $table->dropColumn('notes'); // Remove notes column
        });
    }
};