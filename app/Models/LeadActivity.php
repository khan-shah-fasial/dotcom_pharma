<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class LeadActivity extends Model
{
    use PreventDemoModeChanges;

    protected $guarded = [];

    protected $casts = [
        'next_followup' => 'datetime',
        'expected_value' => 'decimal:2',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activityType()
    {
        return $this->belongsTo(LeadActivityType::class, 'activity_type_id');
    }

    public function subStatus()
    {
        return $this->belongsTo(LeadActivitySubStatus::class, 'sub_status_id');
    }

    public function getAttachmentIdsAttribute(): array
    {
        if (empty($this->attachments)) {
            return [];
        }

        return collect(explode(',', $this->attachments))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function getAttachmentFilesAttribute()
    {
        $ids = $this->attachment_ids;

        if (empty($ids)) {
            return collect();
        }

        return Upload::withoutGlobalScope('not_hidden')
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn ($upload) => array_search((int) $upload->id, $ids, true))
            ->values();
    }
}
