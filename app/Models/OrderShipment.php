<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class OrderShipment extends Model
    {
    use PreventDemoModeChanges;
    protected $fillable = [
        'order_id','shipping_method_id','shipping_id', 'tracking_url', 'label_path', 'shipping_type','raw_response','status',
        'courier_status', 'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    /**
     * Inverse of the relation
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
