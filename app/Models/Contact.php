<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $guarded = [];

    protected $casts = [
        'data'   => 'array',
        'review' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
