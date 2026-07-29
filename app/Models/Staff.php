<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class Staff extends Model
{
    use PreventDemoModeChanges;

    protected $casts = [
        'status' => 'boolean',
        'date_of_birth' => 'date',
        'anniversary_date' => 'date',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function pick_up_point()
    {
    	return $this->hasOne(PickupPoint::class);
    }

}
