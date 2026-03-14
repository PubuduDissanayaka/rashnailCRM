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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // NULL = system default
            $table->string('notification_type', 100);
            $table->string('channel', 50); // 'email', 'sms', 'in_app', 'push'
            $table->boolean('is_enabled')->default(true);
            $table->json('preferences')->nullable(); // Channel-specific preferences
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'notification_type', 'channel'], 'uk_user_type_channel');
            $table->index(['notification_type', 'channel'], 'idx_type_channel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
