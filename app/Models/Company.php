<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'code',
        'company_name',
        'full_address',
        'contact_person',
        'designation',
        'mobile',
        'whatsapp',
        'email',
        'company_type',
        'logo',
        'stamp',
        'sign',
        'created_by',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'company_category')->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function brands()
    {
        return $this->hasMany(Brand::class);
    }
}
