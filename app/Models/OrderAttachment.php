<?php

namespace App\Models;

use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\Model;

class OrderAttachment extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'order_id',
        'category',
        'original_name',
        'path',
        'mime_type',
        'size',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
