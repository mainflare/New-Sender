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
        Schema::create('whatsapp_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique();
            $table->string('name')->nullable();
            $table->enum('type', ['web', 'cloud'])->default('web');
            $table->enum('status', ['initializing', 'qr_ready', 'connected', 'disconnected', 'failed'])->default('initializing');
            $table->string('phone_number')->nullable();
            $table->text('qr_code')->nullable();
            $table->json('meta_credentials')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('daily_message_count')->default(0);
            $table->date('message_count_date')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_sessions');
    }
};
