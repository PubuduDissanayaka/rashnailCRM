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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed', 'bogo', 'free_shipping', 'tiered'])->default('fixed');
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->decimal('max_discount_amount', 10, 2)->nullable();
            $table->decimal('minimum_purchase_amount', 10, 2)->default(0);
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->string('timezone')->default('UTC');
            $table->integer('total_usage_limit')->nullable();
            $table->integer('per_customer_limit')->default(1);
            $table->boolean('stackable')->default(false);
            $table->boolean('active')->default(true);
            $table->enum('location_restriction_type', ['all', 'specific'])->default('all');
            $table->enum('customer_eligibility_type', ['all', 'new', 'existing', 'groups'])->default('all');
            $table->enum('product_restriction_type', ['all', 'specific', 'categories'])->default('all');
            $table->json('metadata')->nullable();
            $table->foreignId('batch_id')->nullable()->constrained('coupon_batches')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['active', 'start_date', 'end_date']);
            $table->index('type');
            $table->index('batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
