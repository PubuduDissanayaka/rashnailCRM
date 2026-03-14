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
        Schema::create('attendance_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade');
            $table->enum('break_type', ['lunch', 'coffee', 'personal', 'meeting', 'other'])->default('lunch');
            $table->time('break_start');
            $table->time('break_end')->nullable();
            $table->integer('duration_minutes')->nullable()->comment('Calculated duration in minutes');
            $table->boolean('is_paid')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('attendance_id');
            $table->index('break_type');
            $table->index(['break_start', 'break_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_breaks');
    }
};