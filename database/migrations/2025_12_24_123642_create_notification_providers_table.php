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
        Schema::create('notification_providers', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 50); // 'email', 'sms'
            $table->string('provider', 50); // 'smtp', 'mailgun', 'sendgrid', 'ses', 'twilio', 'vonage'
            $table->string('name', 255);
            $table->boolean('is_default')->default(false);
            $table->integer('priority')->default(0); // Lower number = higher priority
            $table->json('config'); // Encrypted provider configuration
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_test_at')->nullable();
            $table->string('last_test_status', 20)->nullable();
            $table->integer('daily_limit')->nullable();
            $table->integer('monthly_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->unique(['channel', 'provider'], 'uk_channel_provider');
            $table->index(['channel', 'is_active'], 'idx_channel_active');
            $table->index('priority', 'idx_priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_providers');
    }
};
