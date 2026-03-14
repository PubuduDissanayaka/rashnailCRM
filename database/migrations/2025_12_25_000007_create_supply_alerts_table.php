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
        Schema::create('supply_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_id')->constrained('supplies')->cascadeOnDelete();

            $table->enum('alert_type', [
                'low_stock',
                'out_of_stock',
                'expiring_soon',
                'expired'
            ]);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->text('message');

            // Alert Status
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');

            // Reference Values
            $table->decimal('current_stock', 10, 2)->nullable();
            $table->decimal('min_stock_level', 10, 2)->nullable();
            $table->date('expiry_date')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('supply_id');
            $table->index('alert_type');
            $table->index('is_resolved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supply_alerts');
    }
};