<?php

namespace App\Models;

use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = ['category_id', 'name', 'status'];

    protected $casts = [
        'status' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(DepartmentCategory::class, 'category_id');
    }

    public function leads()
    {
        return $this->hasMany(Lead::class, 'department_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
