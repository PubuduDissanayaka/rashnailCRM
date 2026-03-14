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
        // Only create the settings table if it doesn't exist
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 191)->unique(); // e.g., 'business.name' - reduced size to prevent index length issues
                $table->text('value')->nullable(); // Supports JSON for complex values
                $table->enum('type', ['string', 'integer', 'boolean', 'json', 'text', 'file'])
                      ->default('string');
                $table->string('group', 50); // business, appointment, notification, payment - reduced size
                $table->text('description')->nullable(); // Help text for admins
                $table->integer('order')->default(0); // Display ordering
                $table->boolean('encrypted')->default(false); // Future: API keys, passwords
                $table->timestamps();

                $table->index(['group', 'key']);
                $table->index('key');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('settings')) {
            Schema::dropIfExists('settings');
        }
    }
};
