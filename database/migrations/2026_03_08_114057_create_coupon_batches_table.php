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
        Schema::create('coupon_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('pattern'); // e.g., "SUMMER-{RANDOM6}"
            $table->integer('count');
            $table->integer('generated_count')->default(0);
            $table->enum('status', ['pending', 'generating', 'completed', 'failed'])->default('pending');
            $table->json('settings')->nullable(); // common coupon attributes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_batches');
    }
};
