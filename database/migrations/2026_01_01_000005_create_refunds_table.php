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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_number')->unique(); // REF-2025-00001
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users'); // Staff who processed refund
            $table->decimal('refund_amount', 10, 2);
            $table->enum('refund_method', ['cash', 'card', 'store_credit', 'original_payment']);
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('refund_date');
            $table->timestamps();

            $table->index('sale_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};