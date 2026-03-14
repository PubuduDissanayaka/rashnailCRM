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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->enum('status', ['present', 'late', 'absent', 'leave', 'half_day'])->default('absent');
            $table->decimal('hours_worked', 5, 2)->nullable(); // Calculated field
            $table->text('notes')->nullable();
            $table->boolean('is_manual_entry')->default(false);
            $table->timestamps();

            // Indexes
            $table->unique(['user_id', 'date']); // One record per staff per day
            $table->index(['date', 'status']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};