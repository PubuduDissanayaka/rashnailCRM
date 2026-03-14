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
        Schema::create('supplies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('sku')->unique(); // Stock Keeping Unit
            $table->string('barcode')->nullable()->unique();

            // Categorization
            $table->foreignId('category_id')->nullable()->constrained('supply_categories')->nullOnDelete();
            $table->string('brand')->nullable();
            $table->string('supplier_name')->nullable();

            // Stock Information
            $table->enum('unit_type', ['piece', 'bottle', 'ml', 'oz', 'gram', 'kg', 'liter', 'set'])->default('piece');
            $table->decimal('unit_size', 10, 2)->nullable(); // e.g., 15 for 15ml bottle
            $table->decimal('min_stock_level', 10, 2)->default(0); // Reorder threshold
            $table->decimal('max_stock_level', 10, 2)->nullable(); // Maximum stock capacity
            $table->decimal('current_stock', 10, 2)->default(0); // Current available quantity

            // Costing
            $table->decimal('unit_cost', 10, 2)->default(0); // Cost per unit
            $table->decimal('retail_value', 10, 2)->nullable(); // For reference only

            // Settings
            $table->boolean('is_active')->default(true);
            $table->boolean('track_expiry')->default(false);
            $table->boolean('track_batch')->default(false);
            $table->integer('usage_per_service')->nullable(); // Default usage amount

            // Location (if multi-location)
            $table->string('location')->nullable();
            $table->string('storage_location')->nullable(); // Shelf/bin location

            // Metadata
            $table->json('metadata')->nullable(); // Additional custom fields
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('sku');
            $table->index('category_id');
            $table->index('is_active');
            $table->index(['current_stock', 'min_stock_level']); // For low stock queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplies');
    }
};