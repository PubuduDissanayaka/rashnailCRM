<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['appointment_id', 'service_id']);
        });

        // Migrate existing single-service appointments to the pivot table
        DB::table('appointments')->whereNotNull('service_id')->orderBy('id')->each(function ($apt) {
            DB::table('appointment_service')->insert([
                'appointment_id' => $apt->id,
                'service_id' => $apt->service_id,
                'quantity' => 1,
                'unit_price' => DB::table('services')->where('id', $apt->service_id)->value('price'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_service');
    }
};
