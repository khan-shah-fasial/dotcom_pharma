<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookedTo extends Model
{
    protected $table = 'booked_to';

    protected $fillable = [
        'transport_id',
        'name',
        'branch_name',
        'branch_address',
        'branch_code',
        'branch_gst_number',
        'branch_mobile_number',
        'branch_alternate_mobile_number',
        'contact_incharge',
        'branch_email',
        'status',
        'created_by',
    ];

    public function getLocationAttribute($value)
    {
        return $value ?? ($this->attributes['name'] ?? null);
    }

    public function setLocationAttribute($value): void
    {
        $this->attributes['name'] = $value;
    }

    public function transport()
    {
        return $this->belongsTo(Transport::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
