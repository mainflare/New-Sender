<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'plan_name',
        'plan_type',
        'price',
        'billing_cycle',
        'status',
        'payment_method',
        'payment_id',
        'max_workspaces',
        'max_contacts',
        'max_campaigns_per_month',
        'max_messages_per_day',
        'ai_chatbot_enabled',
        'google_maps_scraper_enabled',
        'api_access_enabled',
        'trial_ends_at',
        'expires_at',
    ];

    protected $casts = [
        'ai_chatbot_enabled' => 'boolean',
        'google_maps_scraper_enabled' => 'boolean',
        'api_access_enabled' => 'boolean',
        'trial_ends_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && 
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function hasFeature(string $feature): bool
    {
        return match($feature) {
            'ai_chatbot' => $this->ai_chatbot_enabled,
            'google_maps' => $this->google_maps_scraper_enabled,
            'api_access' => $this->api_access_enabled,
            default => false,
        };
    }
}
