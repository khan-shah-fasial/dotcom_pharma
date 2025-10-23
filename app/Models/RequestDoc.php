<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestDoc extends Model
{
    protected $fillable = [
        'user_id','name','email','note','type','type_input',
        'start_date','expiry_date','status','admin_note'
    ];

    protected $casts = [
        'start_date'  => 'date',
        'expiry_date' => 'date',
    ];

    public function user() {
        return $this->belongsTo(\App\Models\User::class);
    }
}