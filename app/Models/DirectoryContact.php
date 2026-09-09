<?php

namespace App\Models;

use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\Model;

class DirectoryContact extends Model
{
    use PreventDemoModeChanges;

    protected $guarded = [];

    protected $casts = [
        'social_media_ids' => 'array',
        'tags' => 'array',
    ];

    public function photoUpload()
    {
        return $this->belongsTo(Upload::class, 'photo');
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function group()
    {
        return $this->belongsTo(ContactClassification::class, 'group_id');
    }

    public function category()
    {
        return $this->belongsTo(ContactClassification::class, 'category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(ContactClassification::class, 'subcategory_id');
    }

    public function type()
    {
        return $this->belongsTo(ContactClassification::class, 'type_id');
    }

    public function subject()
    {
        return $this->belongsTo(ContactClassification::class, 'subject_id');
    }

    public function industry()
    {
        return $this->belongsTo(ContactClassification::class, 'industry_id');
    }

    public function workProfile()
    {
        return $this->belongsTo(ContactClassification::class, 'work_profile_id');
    }

    public function department()
    {
        return $this->belongsTo(ContactClassification::class, 'department_id');
    }

    public function purpose()
    {
        return $this->belongsTo(ContactClassification::class, 'purpose_id');
    }
}
