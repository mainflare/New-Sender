<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'whatsapp_session_id',
        'contact_id',
        'assigned_to',
        'status',
        'notes',
        'unread_count',
        'is_archived',
        'last_message_at',
    ];

    protected $casts = [
        'unread_count' => 'integer',
        'is_archived' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function whatsappSession()
    {
        return $this->belongsTo(WhatsappSession::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function labels()
    {
        return $this->belongsToMany(ConversationLabel::class, 'conversation_label_pivot');
    }
}
