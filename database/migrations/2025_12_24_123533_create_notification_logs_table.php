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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_id')->nullable();
            $table->string('channel', 50); // 'email', 'sms', 'in_app'
            $table->string('provider', 50); // 'smtp', 'mailgun', 'sendgrid', 'ses', 'twilio'
            $table->string('recipient', 255); // email, phone, user_id
            $table->string('subject', 255)->nullable();
            $table->text('content')->nullable();
            $table->string('status', 20); // 'pending', 'processing', 'sent', 'failed', 'delivered'
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('notification_id')->references('id')->on('notifications')->onDelete('set null');
            $table->index(['channel', 'status'], 'idx_channel_status');
            $table->index('created_at', 'idx_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
