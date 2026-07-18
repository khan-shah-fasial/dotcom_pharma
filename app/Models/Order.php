<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class Order extends Model
{
    use PreventDemoModeChanges;

    protected $casts = [
        'freight_paid' => 'boolean',
        'free_shipping' => 'boolean',
        'lr_date' => 'date',
        'order_date' => 'date',
        'weight_grams' => 'decimal:3',
        'weight_kg' => 'decimal:6',
    ];

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function refund_requests()
    {
        return $this->hasMany(RefundRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shop()
    {
        return $this->hasOne(Shop::class, 'user_id', 'seller_id');
    }

    public function pickup_point()
    {
        return $this->belongsTo(PickupPoint::class);
    }

    public function carrier()
    {
        return $this->belongsTo(Carrier::class);
    }

    public function transport()
    {
        return $this->belongsTo(Transport::class);
    }

    public function bookedTo()
    {
        return $this->belongsTo(BookedTo::class, 'booked_to_id');
    }

    public function localDeliveryPartner()
    {
        return $this->belongsTo(LocalDeliveryPartner::class);
    }

    public function affiliate_log()
    {
        return $this->hasMany(AffiliateLog::class);
    }

    public function club_point()
    {
        return $this->hasMany(ClubPoint::class);
    }

    public function delivery_boy()
    {
        return $this->belongsTo(User::class, 'assign_delivery_boy', 'id');
    }

    public function salesPerson()
    {
        return $this->belongsTo(User::class, 'sales_person_id');
    }

    public function salesExecutive()
    {
        return $this->belongsTo(User::class, 'sales_executive_id');
    }

    public function packedByStaff()
    {
        return $this->belongsTo(User::class, 'packed_by');
    }

    public function checkedByStaff()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function billingByStaff()
    {
        return $this->belongsTo(User::class, 'billing_by');
    }

    public function attachments()
    {
        return $this->hasMany(OrderAttachment::class);
    }

    public function proxy_cart_reference_id()
    {
        return $this->hasMany(ProxyPayment::class)->select('reference_id');
    }

    public function commissionHistory()
    {
        return $this->hasOne(CommissionHistory::class);
    }

    /**
     * One-to-one relation to order_shipments table
     */
    public function shipment()
    {
        return $this->hasOne(OrderShipment::class, 'order_id', 'id');
    }
}
