<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Geo extends Model
{
    protected $fillable = ['code', 'name', 'flag', 'price_per_1000', 'is_active', 'sort'];

    protected $casts = [
        'price_per_1000' => 'decimal:5',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
