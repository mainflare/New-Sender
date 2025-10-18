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
        Schema::create('chatbots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('ai_provider', ['openai', 'gemini'])->default('openai');
            $table->string('model')->default('gpt-4');
            $table->text('system_prompt')->nullable();
            $table->json('keywords')->nullable();
            $table->json('keyword_responses')->nullable();
            $table->boolean('use_nlp')->default(true);
            $table->boolean('is_active')->default(true);
            $table->float('temperature')->default(0.7);
            $table->integer('max_tokens')->default(500);
            $table->json('settings')->nullable();
            $table->integer('conversations_handled')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbots');
    }
};
