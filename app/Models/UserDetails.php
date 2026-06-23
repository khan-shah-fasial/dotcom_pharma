<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDetails extends Model
{
    public const CURRENT_STATUSES = [
        'Black List',
        'Ban',
        'Good',
        'Excellent',
        'OK',
        'Credit Allow',
        'Advance Payment Only',
        'Do Not Call',
        'Fruad',
        'Spam',
        'Party Not Intersted',
        'We Are Not Intersted',
        'On Time Payment',
        'Delay payment',
    ];

    protected $fillable = [
        'user_id',
        'type_option',
        'transport_id',
        'booked_to_id',
        'booked_to',
        'salesman',
        'dl_expiry',
        'dl1',
        'dl2',
        'gst_no',
        'gst_no_file',
        'iec_no',
        'iec_no_file',
        'registration_date',
        'const_of_business',
        'gstin_current_status',
        'uin_current_status',
        'con_person_name',
        'company_name',
        'current_status',
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
        'alternate_whats_app_no_business',
        'alternate_whats_app_no_business_meta',

        'prim_email_business',
        'alt_email_business',
        'website_business',
        'business_instagram_id',
        'business_facebook_id',
        'business_linkedin_id',
        'bank_name_business',
        'account_no_business',
        'account_name_business',
        'branch_code_business',
        'branch_name_business',
        'branch_address_business',
        'ifsc_code_business',
        'micr_code_business',
        'ad_code_business',
        'crm_id',
        'transport',


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
        'religion',
        'anniversary',
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
        'alt_whats_app_no_meta',

        'prim_email_personal',
        'alt_email_personal',

        'bank_name_personal',
        'account_no_personal',
        'account_name_personal',
        'branch_code_personal',
        'branch_name_personal',
        'branch_address_personal',
        'ifsc_code_personal',
        'micr_code_personal',
        'ad_code_personal',

        'd_l_no_1',
        'd_l_no_1_file',
        
        'doctor_hospital_reg_no',
        'doctor_hospital_reg_no_file',

        'd_l_no_2',
        'd_l_no_2_file',

        'dairy_trust_ngo_reg_no',
        'dairy_trust_ngo_reg_no_file',

        'd_l_no_3',
        'd_l_no_3_file',

        'cc_mdl_reg_no',
        'cc_mdl_reg_no_file',

    ];


    public function details()
    {
        return $this->hasOne(UserDetails::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transportMaster()
    {
        return $this->belongsTo(Transport::class, 'transport_id');
    }

    public function bookedToMaster()
    {
        return $this->belongsTo(BookedTo::class, 'booked_to_id');
    }
}
