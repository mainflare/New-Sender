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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('plan_name');
            $table->enum('plan_type', ['free', 'starter', 'professional', 'enterprise'])->default('free');
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->enum('status', ['active', 'cancelled', 'expired', 'suspended'])->default('active');
            $table->enum('payment_method', ['stripe', 'paypal', 'coingate'])->nullable();
            $table->string('payment_id')->nullable();
            $table->integer('max_workspaces')->default(1);
            $table->integer('max_contacts')->default(100);
            $table->integer('max_campaigns_per_month')->default(10);
            $table->integer('max_messages_per_day')->default(100);
            $table->boolean('ai_chatbot_enabled')->default(false);
            $table->boolean('google_maps_scraper_enabled')->default(false);
            $table->boolean('api_access_enabled')->default(false);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
