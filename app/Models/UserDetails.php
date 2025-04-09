<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDetails extends Model
{

    protected $fillable = [
        'user_id',
        'type_option',
        'gst_no',
        'gst_no_file',
        'iec_no',
        'iec_no_file',
        'registration_date',
        'const_of_business',
        'gstin_uin_current_status',
        'con_person_name',
        'company_name',
        'street_add_first_business',
        'street_add_sec_business',
        'locality_land_mark_business',
        'village_business',
        'post_business',
        'city_id_business',
        'district_business',
        'state_id_business',
        'pincode_business',
        'country_id_business',
        'country_code_business',

        'prim_mobile_no_business',
        'prim_mobile_no_business_meta',
        'alt_mobile_no_business',
        'alt_mobile_no_business_meta',
        'prim_whats_app_no_business',
        'prim_whats_app_no_business_meta',

        'prim_email_business',
        'alt_email_business',
        'website_business',
        'bank_name_business',
        'account_no_business',
        'account_name_business',
        'branch_code_business',
        'branch_name_business',
        'branch_address_business',
        'ifsc_code_business',
        'micr_code_business',
        'ad_code_business',


        'aadhaar_no',
        'aadhaar_no_file',
        'pan_no',
        'pan_no_file',
        'passport_no',
        'passport_no_file',
        'photo_file',
        'name',
        'father_name',
        'dob',
        'street_add_first',
        'street_add_sec',
        'locality_land_mark',
        'village',
        'post',
        'city_id',
        'district',
        'state_id',
        'pincode',
        'country_id',
        'country_code',

        'prim_mobile_no',
        'prim_mobile_no_meta',
        'alt_mobile_no',
        'alt_mobile_no_meta',
        'prim_whats_app_no',
        'prim_whats_app_no_meta',
        'alt_whats_app_no',
        'alt_mobile_no_meta',

        'prim_email_business',
        'alt_email_business',
        'website_business',
        'bank_name_business',
        'account_no_business',
        'account_name_business',
        'branch_code_business',
        'branch_name_business',
        'branch_address_business',
        'ifsc_code_business',
        'micr_code_business',
        'ad_code_business',


        'cc_no',
        'd_l_no_1',
        'd_l_no_2',
        'd_l_no_3',
        'd_l_exp_Date',
        'transport',
        'cargo',
    ];


    public function details()
    {
        return $this->hasOne(UserDetails::class);
    }
}
