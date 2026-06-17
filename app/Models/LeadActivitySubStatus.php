<?php

namespace App\Models;

use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\Model;

class LeadActivitySubStatus extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = ['title', 'status'];

    protected $casts = [
        'status' => 'integer',
    ];

    public function activities()
    {
        return $this->hasMany(LeadActivity::class, 'sub_status_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
