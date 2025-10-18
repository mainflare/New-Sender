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
        Schema::create('conversation_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('color')->default('#000000');
            $table->text('description')->nullable();
            $table->timestamps();
        });
        
        // Pivot table for conversations and labels
        Schema::create('conversation_label_pivot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('conversation_label_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['conversation_id', 'conversation_label_id'], 'conv_label_unique');
        });
        
        // Conversation comments
        Schema::create('conversation_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('comment');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_comments');
        Schema::dropIfExists('conversation_label_pivot');
        Schema::dropIfExists('conversation_labels');
    }
};
