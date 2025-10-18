<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'whatsapp_session_id',
        'message_id',
        'type',
        'body',
        'media_url',
        'media_mime_type',
        'caption',
        'direction',
        'status',
        'from_number',
        'to_number',
        'from_me',
        'campaign_id',
        'is_ai_response',
        'error_message',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'from_me' => 'boolean',
        'is_ai_response' => 'boolean',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function whatsappSession()
    {
        return $this->belongsTo(WhatsappSession::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
