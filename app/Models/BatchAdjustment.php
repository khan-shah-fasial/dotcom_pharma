<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchAdjustment extends Model
{
    protected $table = 'batch_adjustments';

    protected $fillable = [
        'batch_master_id',
        'new_batch_master_id',
        'action',
        'qty',
        'reason',
        'biowaste_upload_id',
        'user_id',
    ];

    protected $casts = [
        'qty' => 'float',
    ];

    public function batch()
    {
        return $this->belongsTo(BatchMaster::class, 'batch_master_id');
    }

    public function newBatch()
    {
        return $this->belongsTo(BatchMaster::class, 'new_batch_master_id');
    }
}
