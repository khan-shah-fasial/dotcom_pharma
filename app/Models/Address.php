<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class Address extends Model
{
    use PreventDemoModeChanges;

    public const TYPE_BILLING  = 'billing';
    public const TYPE_SHIPPING = 'shipping';

    protected $fillable = [
        'user_id',
        'type',
        'address',
        'country_id',
        'state_id',
        'city_id',
        'longitude',
        'latitude',
        'postal_code',
        'phone',
        'set_default',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    
    public function state()
    {
        return $this->belongsTo(State::class);
    }
    
    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
