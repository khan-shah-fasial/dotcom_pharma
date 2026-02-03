<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\SoftDeletes;

class Upload extends Model
{
    use SoftDeletes,PreventDemoModeChanges;

    /**
    * The attributes that are mass assignable.
    *
    * @var array
    */
    protected $fillable = [
        'file_original_name', 'file_name', 'user_id', 'extension', 'type', 'file_size',
    ];

    protected static function booted()
    {
        static::addGlobalScope('not_hidden', function (Builder $builder) {
            $builder->where('is_hidden', false);
        });
    }

    public function user()
    {
    	return $this->belongsTo(User::class);
    }
}
