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
        Schema::create('provider_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id');
            $table->string('key', 100); // 'api_key', 'secret', 'host', 'port', etc.
            $table->text('value_encrypted'); // Encrypted value
            $table->boolean('is_secret')->default(true);
            $table->timestamps();

            $table->foreign('provider_id')->references('id')->on('notification_providers')->onDelete('cascade');
            $table->index(['provider_id', 'key'], 'idx_provider_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_configurations');
    }
};
