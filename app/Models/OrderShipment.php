<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class OrderShipment extends Model
    {
    use PreventDemoModeChanges;
    protected $fillable = [
        'order_id','shipping_method_id','shipping_id', 'tracking_url', 'shipping_type','raw_response','status'
    ];
}
