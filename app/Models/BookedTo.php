<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookedTo extends Model
{
    protected $table = 'booked_to';

    protected $fillable = ['transport_id', 'name', 'status', 'created_by'];

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
