<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookedTo extends Model
{
    protected $table = 'booked_to';

    protected $fillable = [
        'transport_id',
        'location',
        'name',
        'branch_name',
        'branch_code',
        'branch_gst_number',
        'branch_mobile_number',
        'branch_alternate_mobile_number',
        'contact_incharge',
        'branch_email',
        'status',
        'created_by',
    ];

    public function getNameAttribute($value)
    {
        return $value ?? ($this->attributes['location'] ?? null);
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['location'] = $value;
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
