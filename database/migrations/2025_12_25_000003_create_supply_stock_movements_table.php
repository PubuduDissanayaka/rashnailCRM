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
        Schema::create('supply_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_id')->constrained('supplies')->cascadeOnDelete();

            // Movement Details
            $table->enum('movement_type', [
                'purchase',      // Stock received
                'usage',         // Used in service
                'adjustment',    // Manual correction
                'waste',         // Expired/damaged
                'transfer',      // Location transfer
                'return'         // Return to supplier
            ]);
            $table->decimal('quantity', 10, 2); // Positive or negative
            $table->decimal('quantity_before', 10, 2); // Stock before movement
            $table->decimal('quantity_after', 10, 2); // Stock after movement

            // Reference
            $table->morphs('reference'); // appointment_id, purchase_order_id, etc.
            $table->string('reference_number')->nullable(); // Human-readable reference

            // Cost Tracking
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();

            // Batch/Expiry (if tracked)
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();

            // Location
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();

            // User & Timestamp
            $table->foreignId('created_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamp('movement_date')->useCurrent();

            $table->timestamps();

            // Indexes
            $table->index('supply_id');
            $table->index('movement_type');
            $table->index('movement_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supply_stock_movements');
    }
};