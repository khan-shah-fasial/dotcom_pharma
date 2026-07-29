<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class Country extends Model
{
    use PreventDemoModeChanges;

    protected $casts = [
        'regional_language' => 'array',
        'forex_rate' => 'decimal:8',
        'forex_rate_updated_at' => 'datetime',
    ];

    /**
     * Get the Zone that owns the Country
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function scopeIsEnabled($query)
    {
        return $query->where('status', '1');
    }

    public function defaultCurrency()
    {
        return $this->belongsTo(Currency::class, 'default_currency_id');
    }

    public function defaultLanguage()
    {
        return $this->belongsTo(Language::class, 'default_language_id');
    }

    public function localDateTime(): ?Carbon
    {
        if (!$this->timezone || !in_array($this->timezone, \DateTimeZone::listIdentifiers(), true)) {
            return null;
        }

        return now($this->timezone);
    }
    
}
