<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequiredChannel extends Model
{
    protected $fillable = ['chat_id', 'title', 'url', 'is_active', 'sort'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort');
    }
}
