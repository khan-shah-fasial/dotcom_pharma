<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class Lead extends Model
{
    use PreventDemoModeChanges;

    protected $guarded = [];

    protected $casts = [
        'expected_value' => 'decimal:2',
        'social_media_ids' => 'array',
    ];

    public function source()
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    public function status()
    {
        return $this->belongsTo(LeadStatus::class, 'status_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function photoUpload()
    {
        return $this->belongsTo(Upload::class, 'photo');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activities()
    {
        return $this->hasMany(LeadActivity::class);
    }

    public function latestActivity()
    {
        return $this->hasOne(LeadActivity::class)->latestOfMany();
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
