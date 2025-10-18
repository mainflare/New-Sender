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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('whatsapp_session_id')->constrained()->onDelete('cascade');
            $table->string('message_id')->unique();
            $table->enum('type', ['text', 'image', 'video', 'audio', 'document', 'location', 'contact', 'sticker'])->default('text');
            $table->text('body')->nullable();
            $table->string('media_url')->nullable();
            $table->string('media_mime_type')->nullable();
            $table->string('caption')->nullable();
            $table->enum('direction', ['inbound', 'outbound'])->default('outbound');
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->string('from_number');
            $table->string('to_number');
            $table->boolean('from_me')->default(false);
            $table->foreignId('campaign_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('is_ai_response')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['conversation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
