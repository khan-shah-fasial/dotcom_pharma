<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gift extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_gifts';

    protected $fillable = [
        'name',
        'description',
        'cost',
        'stock',
        'is_active',
        'created_by',
        'updated_by',
        'photos',
        'thumbnail_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cost' => 'float',
        'photos' => 'array',
    ];

    public function requests()
    {
        return $this->hasMany(GiftRequest::class, 'gift_id');
    }
}
