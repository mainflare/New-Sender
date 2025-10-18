<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'whatsapp_session_id',
        'template_id',
        'name',
        'description',
        'type',
        'status',
        'message_content',
        'media_attachments',
        'target_audiences',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'read_count',
        'failed_count',
        'delay_between_messages',
        'scheduled_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'media_attachments' => 'array',
        'target_audiences' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function whatsappSession()
    {
        return $this->belongsTo(WhatsappSession::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
