<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    protected $guarded = [];

    protected $casts = [
        'status' => 'boolean',
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
