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
        Schema::create('audiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('filter_criteria')->nullable();
            $table->integer('contacts_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Pivot table for audiences and contacts
        Schema::create('audience_contact', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audience_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['audience_id', 'contact_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audience_contact');
        Schema::dropIfExists('audiences');
    }
};
