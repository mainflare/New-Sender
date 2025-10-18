<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'phone_number',
        'whatsapp_id',
        'name',
        'email',
        'company',
        'address',
        'city',
        'country',
        'tags',
        'custom_fields',
        'is_valid',
        'is_blocked',
        'source',
        'last_message_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'custom_fields' => 'array',
        'is_valid' => 'boolean',
        'is_blocked' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function audiences()
    {
        return $this->belongsToMany(Audience::class, 'audience_contact');
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
}
