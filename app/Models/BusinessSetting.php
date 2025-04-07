<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class BusinessSetting extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = ['type', 'value', 'lang'];
}
