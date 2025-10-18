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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->string('phone_number');
            $table->string('whatsapp_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('company')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
            $table->boolean('is_valid')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->string('source')->nullable(); // 'manual', 'import', 'google_maps', 'api'
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['workspace_id', 'phone_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
