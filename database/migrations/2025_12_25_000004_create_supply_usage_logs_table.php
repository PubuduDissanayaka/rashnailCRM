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
        Schema::create('supply_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_id')->constrained('supplies')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();

            // Usage Details
            $table->decimal('quantity_used', 10, 2);
            $table->decimal('unit_cost', 10, 2)->nullable(); // Cost at time of use
            $table->decimal('total_cost', 10, 2)->nullable();

            // Who used it
            $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();

            // Batch tracking
            $table->string('batch_number')->nullable();

            $table->text('notes')->nullable();
            $table->timestamp('used_at')->useCurrent();
            $table->timestamps();

            // Indexes
            $table->index('supply_id');
            $table->index('appointment_id');
            $table->index('service_id');
            $table->index('used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supply_usage_logs');
    }
};