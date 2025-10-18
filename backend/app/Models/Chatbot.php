<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chatbot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'name',
        'description',
        'ai_provider',
        'model',
        'system_prompt',
        'keywords',
        'keyword_responses',
        'use_nlp',
        'is_active',
        'temperature',
        'max_tokens',
        'settings',
        'conversations_handled',
    ];

    protected $casts = [
        'keywords' => 'array',
        'keyword_responses' => 'array',
        'use_nlp' => 'boolean',
        'is_active' => 'boolean',
        'temperature' => 'float',
        'settings' => 'array',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function trainingData()
    {
        return $this->hasMany(ChatbotTrainingData::class);
    }
}
