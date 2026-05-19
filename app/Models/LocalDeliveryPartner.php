<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocalDeliveryPartner extends Model
{
    protected $fillable = ['name', 'status', 'created_by'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
