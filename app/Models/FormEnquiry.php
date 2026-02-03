<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\State;
use App\Models\Country;

class FormEnquiry extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'form_date'       => 'date',
        'gov_start_date'  => 'date',
        'gov_end_date'    => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function govState()
    {
        return $this->belongsTo(State::class, 'gov_state_id');
    }

    public function companyState()
    {
        return $this->belongsTo(State::class, 'company_state_id');
    }

    public function exportCountry()
    {
        return $this->belongsTo(Country::class, 'export_country_id');
    }

    public function companyCountry()
    {
        return $this->belongsTo(Country::class, 'company_country_id');
    }
}
