<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'logo',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Relationships
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function members()
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function whatsappSessions()
    {
        return $this->hasMany(WhatsappSession::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function audiences()
    {
        return $this->hasMany(Audience::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function templates()
    {
        return $this->hasMany(Template::class);
    }

    public function chatbots()
    {
        return $this->hasMany(Chatbot::class);
    }

    public function automationRules()
    {
        return $this->hasMany(AutomationRule::class);
    }

    public function conversationLabels()
    {
        return $this->hasMany(ConversationLabel::class);
    }

    public function quickReplies()
    {
        return $this->hasMany(QuickReply::class);
    }
}
