<?php

namespace App\Models;

use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\Model;

class LeadActivityType extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = ['title', 'status'];

    protected $casts = [
        'status' => 'integer',
    ];

    public function activities()
    {
        return $this->hasMany(LeadActivity::class, 'activity_type_id');
    }

    public function contactActivities()
    {
        return $this->hasMany(DirectoryContactActivity::class, 'activity_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
