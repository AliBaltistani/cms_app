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
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            
            // User relationships
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            
            // Phone numbers
            $table->string('sender_phone', 20);
            $table->string('recipient_phone', 20);
            
            // Message content and metadata
            $table->text('message_content');
            $table->string('message_sid')->nullable()->unique(); // Twilio message SID
            
            // Status and direction
            $table->enum('status', [
                'pending', 'queued', 'sending', 'sent', 
                'delivered', 'failed', 'undelivered'
            ])->default('pending');
            $table->enum('direction', ['outbound', 'inbound'])->default('outbound');
            $table->enum('message_type', [
                'notification', 'conversation', 'reminder', 'alert'
            ])->default('conversation');
            
            // Error handling
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            
            // Timestamps
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            
            // Additional metadata
            $table->json('metadata')->nullable();
            
            // Standard timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['sender_id', 'recipient_id']);
            $table->index(['status']);
            $table->index(['created_at']);
            $table->index(['message_sid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
