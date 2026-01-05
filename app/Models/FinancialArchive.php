<?php

namespace App\Models;

use App\Models\Upload;
use App\Models\User;
use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\Model;

class FinancialArchive extends Model
{
    use PreventDemoModeChanges;

    /**
     * Table uses a singular name in the database.
     *
     * @var string
     */
    protected $table = 'financial_archive';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'user_id',
        'upload_id',
    ];

    /**
     * Casts for the model.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'upload_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Uploaded file associated with the archive.
     */
    public function upload()
    {
        return $this->belongsTo(Upload::class);
    }

    /**
     * Customer that owns the archive.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
