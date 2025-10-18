<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Template extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'name',
        'content',
        'type',
        'category',
        'variables',
        'buttons',
        'header_type',
        'header_content',
        'footer',
        'status',
        'meta_template_id',
        'usage_count',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'buttons' => 'array',
        'is_active' => 'boolean',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }
}
