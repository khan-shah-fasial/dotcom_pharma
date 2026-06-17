<?php

namespace App\Models;

use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\Model;

class DepartmentCategory extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = ['name', 'status'];

    protected $casts = [
        'status' => 'integer',
    ];

    public function departments()
    {
        return $this->hasMany(Department::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
