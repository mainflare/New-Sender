<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsappSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'session_id',
        'name',
        'type',
        'status',
        'phone_number',
        'qr_code',
        'meta_credentials',
        'is_active',
        'daily_message_count',
        'message_count_date',
        'connected_at',
        'last_activity_at',
    ];

    protected $casts = [
        'meta_credentials' => 'array',
        'is_active' => 'boolean',
        'message_count_date' => 'date',
        'connected_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }
}
