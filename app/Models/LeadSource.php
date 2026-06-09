<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class LeadSource extends Model
{
    use PreventDemoModeChanges;

    protected $guarded = [];

    public function leads()
    {
        return $this->hasMany(Lead::class, 'source_id');
    }
}
