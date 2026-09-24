<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeriesMaster extends Model
{
    protected $table = 'series_masters';

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
    ];
}
