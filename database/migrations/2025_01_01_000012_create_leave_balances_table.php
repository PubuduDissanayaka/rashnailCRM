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
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('year'); // e.g., 2025
            $table->enum('leave_type', ['sick', 'vacation', 'personal']);
            $table->integer('total_days')->default(0);   // Annual allocation
            $table->integer('used_days')->default(0);    // Days taken
            $table->integer('remaining_days')->default(0); // total - used
            $table->timestamps();

            // Unique constraint
            $table->unique(['user_id', 'year', 'leave_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};