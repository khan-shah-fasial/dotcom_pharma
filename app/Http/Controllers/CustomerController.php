<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Country;
use App\Models\UserDetails;
use App\Models\State;
use App\Models\City;
use App\Models\Transport;
use App\Models\BookedTo;
use App\Utility\EmailUtility;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CustomerController extends Controller
{
    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:view_all_customers'])->only('index');
        $this->middleware(['permission:add_customer'])->only('create', 'createBusiness', 'storeBusiness');
        $this->middleware(['permission:login_as_customer'])->only('login');
        $this->middleware(['permission:ban_customer'])->only('ban');
        $this->middleware(['permission:delete_customer'])->only('destroy');
    }

    public function gstDetails(Request $request)
    {
        $validated = $request->validate([
            'gst_no' => ['required', 'regex:/^[0-9A-Z]{15}$/i'],
        ]);

        if (!function_exists('fetchGstinDetails')) {
            return response()->json([
                'status' => 'error',
                'message' => translate('GST verification is not configured.'),
            ], 500);
        }

        $gstNo = strtoupper((string) $validated['gst_no']);
        $response = fetchGstinDetails($gstNo);
        $decoded = json_decode((string) $response, true);

        if (!is_array($decoded)) {
            return response()->json([
                'status' => 'error',
                'message' => translate('Invalid GST response payload.'),
            ], 502);
        }

        if (($decoded['message_code'] ?? null) !== 'success') {
            return response()->json([
                'status' => 'error',
                'message' => (string) ($decoded['message'] ?? translate('GST details not found.')),
            ], 422);
        }

        $data = $decoded['data'] ?? [];
        $principal = $data['contact_details']['principal'] ?? [];

        return response()->json([
            'status' => 'success',
            'message' => translate('GST details fetched successfully.'),
            'data' => [
                'gst_no' => $gstNo,
                'pan_no' => $data['pan_number'] ?? substr($gstNo, 2, 10),
                'registration_date' => $data['date_of_registration'] ?? null,
                'const_of_business' => $data['constitution_of_business'] ?? null,
                'gstin_current_status' => $data['gstin_status'] ?? null,
                'company_name' => $data['business_name'] ?? $data['legal_name'] ?? null,
                'street_add_first_business' => $principal['address'] ?? null,
                'phone_business' => $principal['mobile'] ?? null,
                'whats_app_no_business' => $principal['mobile'] ?? null,
                'prim_email_business' => $principal['email'] ?? null,
            ],
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sort_search = $request->search ?? null;
        $verification_status =  $request->verification_status ?? null;

        $users = User::where('user_type', 'customer')->whereNull('user_subtype')->orderBy('created_at', 'desc');
        if($verification_status != null){
            $users = $verification_status == 'verified' ? $users->where('email_verified_at', '!=', null) : $users->where('email_verified_at', null);
        }
        if($verification_status != null){
            $users = $verification_status == 'verified' ? $users->where('approval_Status', 1) : $users->where('approval_Status', 0);
        }
        if ($sort_search != null){
            $sort_search = $request->search;
            $users->where(function ($q) use ($sort_search){
                $q->where('name', 'like', '%'.$sort_search.'%')->orWhere('email', 'like', '%'.$sort_search.'%')->orWhere('phone', 'like', '%'.$sort_search.'%');
            });
        }

        $users = $users->paginate(15);
        return view('backend.customer.customers.index', compact('users', 'sort_search','verification_status'));
    }

    // public function business_index(Request $request)
    // {
    //     $sort_search = $request->search ?? null;
    //     $company_name = $request->company_name ?? null;
    //     $verification_status =  $request->verification_status ?? null;
    //     $bank_details =  $request->bank_details ?? null;
    //     $license_details =  $request->license_details ?? null;
    //     $dl_expiry_Data =  $request->dl_expiry_Data ?? null;
    //     $gst_no =  $request->gst_no ?? null;
    //     $transport_Details =  $request->transport_Details ?? null;

    //     $users = User::with('details')->where('user_type', 'customer')->whereNotNull('step')->orderBy('created_at', 'desc');
    //     // if($verification_status != null){
    //     //     $users = $verification_status == 'verified' ? $users->where('email_verified_at', '!=', null) : $users->where('email_verified_at', null);
    //     // }
    //     if($verification_status != null){
    //         $users = $verification_status == 'verified' ? $users->where('approval_Status', 1) : $users->where('approval_Status', 0);
    //     }
    //     if ($sort_search != null){
    //         $sort_search = $request->search;
    //         $users->where(function ($q) use ($sort_search){
    //             $q->where('name', 'like', '%'.$sort_search.'%')->orWhere('email', 'like', '%'.$sort_search.'%')->orWhere('phone', 'like', '%'.$sort_search.'%')->orWhere('tel_number', 'like', '%'.$sort_search.'%');
    //         });
    //     }
    //     if ($company_name != null){
    //         $company_name = $request->company_name;
    //         $users->whereHas('details',function ($q) use ($company_name){
    //             $q->where('company_name', 'like', '%'.$company_name.'%');
    //         });
    //     }

    //     if ($gst_no != null){
    //         $gst_no = $request->gst_no;
    //         $users->where(function ($q) use ($gst_no){
    //             $q->where('gst_no', 'like', '%'.$gst_no.'%')
    //             ->orWhere('iec_no','like', '%'.$gst_no.'%')
    //             ->orWhere('aadhaar_no','like', '%'.$gst_no.'%')
    //             ->orWhere('pan_no','like', '%'.$gst_no.'%')
    //             ->orWhere('passport_no','like', '%'.$gst_no.'%');
    //         });
    //     }
    //     // if ($bank_details != null){
    //     //     $bank_details = $request->bank_details;
    //     //     $users->whereHas('details', function ($q) use ($bank_details){
    //     //         $q->where('bank_name_business', 'like', '%'.$bank_details.'%')->orWhere('bank_name_personal', 'like', '%'.$bank_details.'%')->orWhere('account_no_business', 'like', '%'.$bank_details.'%')->orWhere('account_no_personal', 'like', '%'.$bank_details.'%')->orWhere('branch_code_business', 'like', '%'.$bank_details.'%')->orWhere('branch_code_personal', 'like', '%'.$bank_details.'%')->orwhere('ifsc_code_business', 'like', '%'.$bank_details.'%')->orwhere('ifsc_code_personal', 'like', '%'.$bank_details.'%')->orwhere('micr_code_business', 'like', '%'.$bank_details.'%')->orwhere('micr_code_personal', 'like', '%'.$bank_details.'%');
    //     //     });
    //     // }
    //     // if ($license_details != null){
    //     //     $license_details = $request->license_details;
    //     //     $users->whereHas('details', function ($q) use ($license_details){
    //     //         $q->where('cc_no', 'like', '%'.$license_details.'%')->orWhere('d_l_no_1', 'like', '%'.$license_details.'%')->orWhere('d_l_no_2', 'like', '%'.$license_details.'%')->orWhere('d_l_no_3', 'like', '%'.$license_details.'%');
    //     //     });
    //     // }
    //     $users = $users->paginate(15);
    //     return view('backend.customer.customers.businessindex', compact('users', 'sort_search','company_name','bank_details','license_details','gst_no','verification_status'));
    // }

    public function business_index(Request $request)
    {
        $sort_search         = $request->search ?? null;
        $company_name        = $request->company_name ?? null;
        $verification_status = $request->verification_status ?? null;
        $bank_details        = $request->bank_details ?? null;
        $license_details     = $request->license_details ?? null;
        $dl_expiry_Data      = $request->dl_expiry_Data ?? null;
        $gst_no              = $request->gst_no ?? null;
        $account_number      = $request->account_number ?? null;
        $sortBy              = $request->get('sort_by');
        $sortOrder           = $request->get('sort_order', 'asc');

        // NEW: location filters (request) - separated for business vs personal
        $businessPincode   = $request->input('pincode_business');
        $businessCountryId = $request->input('business_country_id');
        $businessStateId   = $request->input('business_state_id');
        $businessCityId    = $request->input('business_city_id');
        $businessDistrict  = $request->input('business_district');
        $businessPost      = $request->input('business_post');
        $businessVillage   = $request->input('business_village');

        $personalPincode   = $request->input('pincode');
        $personalCountryId = $request->input('personal_country_id');
        $personalStateId   = $request->input('personal_state_id');
        $personalCityId    = $request->input('personal_city_id');
        $personalDistrict  = $request->input('personal_district');
        $personalPost      = $request->input('personal_post');
        $personalVillage   = $request->input('personal_village');

        $filter_transport  = $request->transport ?? null;
        $staffAreaAssignments = $this->currentStaffAreaAssignments();

        // Base query
        $users = User::with('details')
            ->where('user_type', 'customer')
            ->whereNotNull('step');
            // ->orderBy(
            //     UserDetails::select('crm_id')
            //         ->whereColumn('user_details.user_id', 'users.id'),
            //     'ASC'
            // );

        if ($staffAreaAssignments !== null) {
            $this->applyStaffAreaScope($users, $staffAreaAssignments);

            if (!$this->locationSelectionIsAllowed($businessCountryId, $businessStateId, $businessCityId, $staffAreaAssignments)
                || !$this->locationSelectionIsAllowed($personalCountryId, $personalStateId, $personalCityId, $staffAreaAssignments)) {
                abort(403, translate('You cannot filter customers outside your assigned area.'));
            }
        }

        // Approval filter
        if ($verification_status !== null) {
            $users = $verification_status === 'verified'
                ? $users->where('approval_Status', 1)
                : $users->where('approval_Status', 0);
        }

        // Text search
        if ($sort_search !== null) {
            $users->where(function ($q) use ($sort_search) {
                $q->where('name', 'like', '%'.$sort_search.'%')
                ->orWhere('email', 'like', '%'.$sort_search.'%')
                ->orWhere('phone', 'like', '%'.$sort_search.'%');
                // ->orWhere('tel_number', 'like', '%'.$sort_search.'%');
            });
        }

        // Company filter
        if ($company_name !== null) {
            $users->whereHas('details', function ($q) use ($company_name) {
                $q->where('company_name', 'like', '%'.$company_name.'%');
            });
        }

        if ($account_number !== null) {
            $users->whereHas('details', function ($q) use ($account_number) {
                $q->where('crm_id', 'like', '%'.$account_number.'%');
            });
        }

        // IDs (GST/Aadhaar/PAN/Passport/IEC) filter
        if ($gst_no !== null) {
            $users->where(function ($q) use ($gst_no) {
                $q->where('gst_no', 'like', '%'.$gst_no.'%')
                ->orWhere('iec_no', 'like', '%'.$gst_no.'%')
                ->orWhere('aadhaar_no', 'like', '%'.$gst_no.'%')
                ->orWhere('pan_no', 'like', '%'.$gst_no.'%')
                ->orWhere('passport_no', 'like', '%'.$gst_no.'%');
            });
        }

        // Location filters with AND conditions per context
        $businessLocationFilters = collect([
            'pincode_business'    => $businessPincode,
            'country_id_business' => $businessCountryId,
            'state_id_business'   => $businessStateId,
            'city_id_business'    => $businessCityId,
            'district_business'   => $businessDistrict,
            'post_business'       => $businessPost,
            'village_business'    => $businessVillage,
        ])->map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        })->toArray();

        $personalLocationFilters = collect([
            'pincode'    => $personalPincode,
            'country_id' => $personalCountryId,
            'state_id'   => $personalStateId,
            'city_id'    => $personalCityId,
            'district'   => $personalDistrict,
            'post'       => $personalPost,
            'village'    => $personalVillage,
        ])->map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        })->toArray();

        $hasBusinessLocationFilters = collect($businessLocationFilters)->filter(function ($value) {
            return $value !== null && $value !== '';
        })->isNotEmpty();

        $hasPersonalLocationFilters = collect($personalLocationFilters)->filter(function ($value) {
            return $value !== null && $value !== '';
        })->isNotEmpty();

        if ($hasBusinessLocationFilters || $hasPersonalLocationFilters) {
            $users->whereHas('details', function ($q) use ($businessLocationFilters, $personalLocationFilters) {
                foreach ($businessLocationFilters as $column => $value) {
                    if ($value === null || $value === '') {
                        continue;
                    }
                    if (in_array($column, ['country_id_business', 'state_id_business', 'city_id_business'], true)) {
                        $q->where($column, (int) $value);
                    } else {
                        $q->whereRaw('LOWER(TRIM(' . $column . ')) = ?', [strtolower(trim((string) $value))]);
                    }
                }

                foreach ($personalLocationFilters as $column => $value) {
                    if ($value === null || $value === '') {
                        continue;
                    }
                    if (in_array($column, ['country_id', 'state_id', 'city_id'], true)) {
                        $q->where($column, (int) $value);
                    } else {
                        $q->whereRaw('LOWER(TRIM(' . $column . ')) = ?', [strtolower(trim((string) $value))]);
                    }
                }
            });
        }

        if ($filter_transport !== null && $filter_transport !== '') {
            $users->whereHas('details', function ($q) use ($filter_transport) {
                $q->where('transport', 'like', '%'.$filter_transport.'%');
            });
        }

        // TRANSPORT list
        $transportList = UserDetails::whereNotNull('transport')
            ->where('transport', '!=', '')
            ->pluck('transport')
            ->map(function ($value) {
                // Trim spaces and convert to lowercase
                return Str::lower(trim($value));
            })
            ->unique()
            ->sort()
            ->values();

        // Administrators can filter by every enabled country. Staff only see countries
        // included in their assigned area records.
        $countryOptionsQuery = Country::query()->isEnabled()->orderBy('name');
        if ($staffAreaAssignments !== null) {
            $countryOptionsQuery->whereIn('id', collect($staffAreaAssignments)->pluck('country_id')->unique()->all());
        }
        $countryOptions = $countryOptionsQuery->get(['id', 'name']);
        $businessCountryOptions = $countryOptions;
        $personalCountryOptions = $countryOptions;

        // Sorting
        $sortOrder = strtolower($sortOrder) === 'desc' ? 'desc' : 'asc';
        if ($sortBy === 'crm_id') {
            $users = $users->orderBy(
                UserDetails::select('crm_id')->whereColumn('user_details.user_id', 'users.id'),
                $sortOrder
            );
        } elseif ($sortBy === 'company_name') {
            $users = $users->orderBy(
                UserDetails::select('company_name')->whereColumn('user_details.user_id', 'users.id'),
                $sortOrder
            );
        } else {
            $users = $users->orderBy(
                UserDetails::select('crm_id')->whereColumn('user_details.user_id', 'users.id'),
                'desc'
            );
        }

        $users = $users->paginate(15)->appends($request->query());

        // Preload state/city names for display to avoid repeated lookups.
        $stateIds = collect($users->pluck('details'))->filter()->flatMap(function ($detail) {
            return [$detail->state_id_business, $detail->state_id];
        })->filter()->unique()->values();

        $cityIds = collect($users->pluck('details'))->filter()->flatMap(function ($detail) {
            return [$detail->city_id_business, $detail->city_id];
        })->filter()->unique()->values();

        $stateNames = $stateIds->isNotEmpty()
            ? State::whereIn('id', $stateIds)->pluck('name', 'id')
            : collect();
        $cityNames = $cityIds->isNotEmpty()
            ? City::whereIn('id', $cityIds)->pluck('name', 'id')
            : collect();

        return view('backend.customer.customers.businessindex', compact(
            'users',
            'sort_search',
            'company_name',
            'bank_details',
            'license_details',
            'gst_no',
            'verification_status',
            'filter_transport',
            'account_number',
            'sortBy',
            'sortOrder',
            'transportList',
            'businessPincode',
            'businessCountryId',
            'businessStateId',
            'businessCityId',
            'businessDistrict',
            'businessPost',
            'businessVillage',
            'personalPincode',
            'personalCountryId',
            'personalStateId',
            'personalCityId',
            'personalDistrict',
            'personalPost',
            'personalVillage',
            'businessCountryOptions',
            'personalCountryOptions',
            'hasBusinessLocationFilters',
            'hasPersonalLocationFilters',
            'stateNames',
            'cityNames'
        ));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('backend.customer.customers.create');
    }

    public function createBusiness()
    {
        $user = new User([
            'type_option' => old('type_option', 'domestic'),
        ]);
        $details = new UserDetails();
        $nextCrmId = ((int) UserDetails::query()
            ->pluck('crm_id')
            ->filter(function ($value) {
                return is_numeric($value);
            })
            ->map(function ($value) {
                return (int) $value;
            })
            ->max()) + 1;

        $countries = Cache::remember('countries_for_customer_edit', 86400, function () {
            return Country::select('id', 'name', 'code')->orderBy('name')->get();
        });
        $transports = $this->activeTransportsWithBookedTo();
        $currentStatuses = UserDetails::CURRENT_STATUSES;
        $customerTypes = UserDetails::CUSTOMER_TYPES;

        return view('backend.customer.customers.create_business', compact(
            'user',
            'details',
            'countries',
            'nextCrmId',
            'transports',
            'currentStatuses',
            'customerTypes',
        ));
    }

    private function activeTransportsWithBookedTo()
    {
        return Transport::active()
            ->with(['bookedTo' => function ($query) {
                $query->active()->orderBy('name');
            }])
            ->orderBy('name')
            ->get();
    }

    private function resolveTransportSelection(Request $request, ?UserDetails $details = null): array
    {
        $transportId = $request->filled('transport_id')
            ? (int) $request->input('transport_id')
            : (int) ($details->transport_id ?? 0);
        $bookedToId = $request->filled('booked_to_id')
            ? (int) $request->input('booked_to_id')
            : (int) ($details->booked_to_id ?? 0);

        $transport = $transportId > 0 ? Transport::find($transportId) : null;
        $bookedTo = ($transport && $bookedToId > 0)
            ? BookedTo::where('transport_id', $transport->id)->where('id', $bookedToId)->first()
            : null;

        return [
            'transport_id' => optional($transport)->id,
            'booked_to_id' => optional($bookedTo)->id,
            'transport' => optional($transport)->name ?? $request->input('transport', $details->transport ?? null),
            'booked_to' => optional($bookedTo)->name ?? $request->input('booked_to', $details->booked_to ?? null),
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate(
            ['name' => 'required|max:255',],
            ['name.required' => translate('Name is required'),'name.max' => translate('Max 255 Character'),]
        );

        // Phone & email both can't be null
        if($request->email == null && $request->phone == null){
            flash(translate('Email and phone number both can not be null.'))->error();
                return back();
        }

        if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            if(User::where('email', $request->email)->first() != null){
                flash(translate('Email already exists.'))->error();
                return back();
            }
        }
        elseif (User::where('phone', '+'.$request->country_code.$request->phone)->first() != null) {
            flash(translate('Phone already exists.'))->error();
            return back();
        }

        $password = '12345678';
        // $password = substr(hash('sha512', rand()), 0, 8);
        $email = null;
        $phone = null;
        
        // Register By email
        if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            $email = $request->email;
            $user = User::create([
                'name' => $request->name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            // Account Opening Email to customer
            try {
                EmailUtility::customer_registration_email('registration_from_system_email_to_customer', $user, $password);
            } catch (\Exception $e) {
                $user->delete();
                flash(translate('Registration failed. Please try again later.'))->error();
                return back();
            }

            // Email Verification mail to Customer
            if(get_setting('email_verification') != 1){
                $user->email_verified_at = date('Y-m-d H:m:s');
                $user->save();
                offerUserWelcomeCoupon();
            }
            else {
                EmailUtility::email_verification($user, 'customer');
            }
            flash(translate('Registration successful.'))->success();

        }
        // Register by phone
        else {
            if (addon_is_activated('otp_system')){
                $phone = '+'.$request->country_code.$request->phone;
                $user = User::create([
                    'name' => $request->name,
                    'phone' => $phone,
                    'password' => Hash::make($password),
                    'verification_code' => rand(100000, 999999)
                ]);

                $otpController = new OTPVerificationController;
                $otpController->account_opening($user, $password);
                flash(translate('Registration successful.'))->success();
            }
        }

        // Customer Account Opening Email to Admin
        if ((get_email_template_data('customer_reg_email_to_admin', 'status') == 1)) {
            try {
                EmailUtility::customer_registration_email('customer_reg_email_to_admin', $user, null);
            } catch (\Exception $e) {}
        }

        if (isset($user) && !empty($user->id) && function_exists('storeIPLocation')) {
            storeIPLocation('users', $user->id);
        }

        return back();
    }

    public function storeBusiness(Request $request)
    {
        $typeOption = $request->input('type_option', 'domestic');
        $domesticChoice = $request->input('domestic_identity_selection', 'gst');
        $internationalChoice = $request->input('international_identity_selection', 'iec');
        $businessRequired = ($typeOption === 'domestic' && $domesticChoice === 'gst')
            || ($typeOption === 'international' && $internationalChoice === 'iec');

        $validator = \Validator::make($request->all(), [
            'type_option' => ['required', 'in:domestic,international'],
            'crm_id' => ['required', 'string', 'max:255', 'unique:user_details,crm_id'],
            'domestic_identity_selection' => ['nullable', 'in:gst,aadhaar_pan'],
            'international_identity_selection' => ['nullable', 'in:iec,passport'],

            'registration_date' => ['nullable'],
            'const_of_business' => ['nullable', 'string', 'max:255'],
            'con_person_name' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'customer_type' => ['nullable', Rule::in(UserDetails::CUSTOMER_TYPES)],
            'current_status' => ['nullable', Rule::in(UserDetails::CURRENT_STATUSES)],
            'street_add_first_business' => ['nullable', 'string', 'max:150'],
            'street_add_sec_business' => ['nullable', 'string', 'max:150'],
            'locality_land_mark_business' => ['nullable', 'string', 'max:150'],
            'village_business' => ['nullable', 'string', 'max:150'],
            'post_business' => ['nullable', 'string', 'max:150'],
            'district_business' => ['nullable', 'string', 'max:150'],
            'country_code_business' => ['nullable', 'string', 'max:150'],
            'pincode_business' => ['nullable', 'regex:/^\d{6}$/'],
            'city_id_business' => ['nullable', 'integer', 'exists:cities,id'],
            'state_id_business' => ['nullable', 'integer', 'exists:states,id'],
            'country_id_business' => ['nullable', 'integer'],
            'phone_business' => ['nullable', 'regex:/^[\d\s\-\+]+$/', 'min:5', 'max:15'],
            'alternate_mob_no_business' => ['nullable', 'regex:/^[\d\s\-\+]+$/', 'min:5', 'max:15'],
            'whats_app_no_business' => ['nullable', 'regex:/^[\d\s\-\+]+$/', 'min:5', 'max:15'],
            'alternate_whats_app_no_business' => ['nullable', 'regex:/^[\d\s\-\+]+$/', 'min:5', 'max:15'],
            'prim_email_business' => ['nullable', 'email'],
            'alt_email_business' => ['nullable', 'email'],
            'website_business' => ['nullable'],
            'business_instagram_id' => ['nullable', 'string', 'max:255'],
            'business_facebook_id' => ['nullable', 'string', 'max:255'],
            'business_linkedin_id' => ['nullable', 'string', 'max:255'],
            'bank_name_business' => ['nullable', 'string', 'max:255'],
            'account_no_business' => ['nullable', 'regex:/^\d+$/', 'max:20'],
            'account_name_business' => ['nullable', 'string', 'max:255'],
            'branch_code_business' => ['nullable', 'string', 'max:50'],
            'branch_name_business' => ['nullable', 'string', 'max:255'],
            'branch_address_business' => ['nullable', 'string', 'max:255'],
            'ifsc_code_business' => ['nullable', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'gst_no' => ['nullable', 'regex:/^[0-9A-Z]{15}$/i'],
            'gst_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf,doc,docx', 'max:5120'],
            'gstin_current_status' => ['nullable', 'string'],
            'iec_no' => ['nullable', 'regex:/^[0-9A-Z]{10}$/i'],
            'iec_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf,doc,docx', 'max:5120'],
            'uin_current_status' => ['nullable', 'string'],
            'passport_no' => ['nullable', 'regex:/^[0-9A-Z]{1,15}$/i'],
            'passport_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf,doc,docx', 'max:5120'],
            'micr_code_business' => ['nullable', 'regex:/^\d{9}$/'],
            'ad_code_business' => ['nullable', 'string', 'max:255'],

            'photo_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'name_personal' => ['required', 'string', 'min:1', 'max:150'],
            'father_name' => ['nullable', 'string', 'min:1', 'max:150'],
            'dob' => ['nullable'],
            'religion' => ['nullable', 'string', 'max:150'],
            'anniversary' => ['nullable', 'date'],
            'street_add_first_personal' => ['nullable', 'string', 'min:1', 'max:150'],
            'street_add_sec_personal' => ['nullable', 'string', 'max:150'],
            'locality_land_mark_personal' => ['nullable', 'string', 'min:1', 'max:150'],
            'village_personal' => ['nullable', 'string', 'min:1', 'max:150'],
            'post_personal' => ['nullable', 'string', 'min:1', 'max:150'],
            'district_personal' => ['nullable', 'string', 'min:1', 'max:150'],
            'country_code_personal' => ['nullable', 'string', 'min:1', 'max:150'],
            'pincode_personal' => ['nullable', 'regex:/^\d{6}$/'],
            'city_id_personal' => ['nullable', 'integer', 'exists:cities,id'],
            'state_id_personal' => ['nullable', 'integer', 'exists:states,id'],
            'country_id_personal' => ['nullable', 'integer'],
            'phone_personal' => ['required', 'regex:/^[\d\s\-\+]+$/', 'min:5', 'max:15'],
            'alternate_mob_no_personal' => ['nullable', 'regex:/^[\d\s\-\+]+$/', 'min:5', 'max:15'],
            'whats_app_no_personal' => ['nullable', 'regex:/^[\d\s\-\+]+$/', 'min:5', 'max:15'],
            'alternate_whats_app_no_personal' => ['nullable', 'regex:/^[\d\s\-\+]+$/', 'min:5', 'max:15'],
            'prim_email_personal' => ['required', 'email'],
            'alt_email_personal' => ['nullable', 'email'],
            'bank_name_personal' => ['nullable', 'string', 'max:255'],
            'account_no_personal' => ['nullable', 'regex:/^\d+$/', 'max:20'],
            'account_name_personal' => ['nullable', 'string', 'max:255'],
            'branch_code_personal' => ['nullable', 'string', 'max:50'],
            'branch_name_personal' => ['nullable', 'string', 'max:255'],
            'branch_address_personal' => ['nullable', 'string', 'max:255'],
            'ifsc_code_personal' => ['nullable', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'aadhaar_no' => ['nullable', 'regex:/^[0-9]{12}$/i'],
            'aadhaar_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf,doc,docx', 'max:5120'],
            'pan_no' => ['nullable', 'regex:/^[0-9A-Z]{10}$/i'],
            'pan_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf,doc,docx', 'max:5120'],
            'micr_code_personal' => ['nullable', 'regex:/^\d{9}$/'],
            'ad_code_personal' => ['nullable', 'string', 'max:255'],

            'd_l_no_1' => ['nullable', 'string', 'max:255'],
            'd_l_no_1_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf,doc,docx', 'max:5120'],
            'd_l_no_2' => ['nullable', 'string', 'max:255'],
            'd_l_no_2_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf,doc,docx', 'max:5120'],
            'd_l_no_3' => ['nullable', 'string', 'max:255'],
            'd_l_no_3_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf,doc,docx', 'max:5120'],
            'doctor_hospital_reg_no' => ['nullable', 'string', 'max:255'],
            'doctor_hospital_reg_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf,doc,docx', 'max:5120'],
            'dairy_trust_ngo_reg_no' => ['nullable', 'string', 'max:255'],
            'dairy_trust_ngo_reg_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf,doc,docx', 'max:5120'],
            'cc_mdl_reg_no' => ['nullable', 'string', 'max:255'],
            'cc_mdl_reg_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf,doc,docx', 'max:5120'],
            'other_reg_no' => ['nullable', 'string', 'max:255'],
            'other_reg_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf,doc,docx', 'max:5120'],

            'transport_id' => ['nullable', 'integer', 'exists:transports,id'],
            'booked_to_id' => ['nullable', 'integer', 'exists:booked_to,id'],
            'transport' => ['nullable', 'string', 'max:255'],
            'booked_to' => ['nullable', 'string', 'max:255'],
            'salesman' => ['nullable', 'string', 'max:255'],
            'dl_expiry' => ['nullable', 'string', 'max:255'],
            'dl1' => ['nullable', 'string', 'max:255'],
            'dl2' => ['nullable', 'string', 'max:255'],
        ]);

        $validator->after(function ($validator) use ($request, $typeOption, $domesticChoice, $internationalChoice) {
            $personalEmail = trim((string) $request->input('prim_email_personal'));
            if ($personalEmail !== '' && User::where('email', $personalEmail)->exists()) {
                $validator->errors()->add('prim_email_personal', translate('Email already exists.'));
            }

            $personalPrimaryCode = $request->input('country_code_phone_code_personal', '');
            $personalPhone = trim((string) $request->input('phone_personal', ''));
            $personalUserPhone = $personalPhone !== '' ? '+' . $personalPrimaryCode . '-' . $personalPhone : null;
            if ($personalUserPhone && User::where('phone', $personalUserPhone)->exists()) {
                $validator->errors()->add('phone_personal', translate('Phone already exists.'));
            }

            // if ($typeOption === 'domestic' && $domesticChoice === 'gst') {
            //     $gstNo = trim((string) $request->input('gst_no'));
            //     if ($gstNo === '') {
            //         $validator->errors()->add('gst_no', translate('GST No is required.'));
            //     }
            // }

            // if ($typeOption === 'international' && $internationalChoice === 'iec') {
            //     $iecNo = trim((string) $request->input('iec_no'));
            //     if ($iecNo === '') {
            //         $validator->errors()->add('iec_no', translate('IEC No is required.'));
            //     }
            // }

            // if ($typeOption === 'international' && $internationalChoice === 'passport') {
            //     $passportNo = trim((string) $request->input('passport_no'));
            //     if ($passportNo === '') {
            //         $validator->errors()->add('passport_no', translate('Passport No is required.'));
            //     }
            // }

            // if ($request->filled('transport_id') && $request->filled('booked_to_id')) {
            //     $bookedToExists = BookedTo::where('id', $request->input('booked_to_id'))
            //         ->where('transport_id', $request->input('transport_id'))
            //         ->exists();
            //     if (!$bookedToExists) {
            //         $validator->errors()->add('booked_to_id', translate('Booked To must belong to selected transport.'));
            //     }
            // }
            // Temporary create flow: only account number, name, email, and phone are required.
        });

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            throw new ValidationException($validator);
        }

        $validated = array_merge($request->all(), $validator->validated());
        $documentPath = $this->ensureCustomerDocumentDirectory();

        DB::beginTransaction();

        try {
            $photoFile = $this->moveCustomerDocument($request, 'photo_file', $documentPath);
            $gstFile = $this->moveCustomerDocument($request, 'gst_no_file', $documentPath);
            $iecFile = $this->moveCustomerDocument($request, 'iec_no_file', $documentPath);
            $aadhaarFile = $this->moveCustomerDocument($request, 'aadhaar_no_file', $documentPath);
            $panFile = $this->moveCustomerDocument($request, 'pan_no_file', $documentPath);
            $passportFile = $this->moveCustomerDocument($request, 'passport_no_file', $documentPath);

            $licenseFiles = [];
            foreach ([
                'd_l_no_1_file',
                'd_l_no_2_file',
                'd_l_no_3_file',
                'doctor_hospital_reg_no_file',
                'dairy_trust_ngo_reg_no_file',
                'cc_mdl_reg_no_file',
                'other_reg_no_file',
            ] as $field) {
                $licenseFiles[$field] = $this->moveCustomerDocument($request, $field, $documentPath);
            }

            $businessPrimaryCode = $request->input('country_code_phone_code_business', '') ?: '91';
            $businessAltCode = $request->input('country_code_alternate_mob_no_business', '');
            $businessWhatsCode = $request->input('country_code_whats_app_no_business', '') ?: '91';
            $businessAltWhatsCode = $request->input('country_code_alternate_whats_app_no_business', '');

            $personalPrimaryCode = $request->input('country_code_phone_code_personal', '') ?: '91';
            $personalAltCode = $request->input('country_code_alternate_mob_no_personal', '');
            $personalWhatsCode = $request->input('country_code_whats_app_no_personal', '') ?: $personalPrimaryCode;
            $personalAltWhatsCode = $request->input('country_code_alternate_whats_app_no_personal', '');

            $password = substr(hash('sha512', rand()), 0, 8);

            $user = User::create([
                'user_type' => 'customer',
                'type_option' => $typeOption,
                'name' => $validated['name_personal'],
                'email' => $validated['prim_email_personal'],
                'email_verified_at' => date('Y-m-d H:m:s'),
                'phone' => '+' . $personalPrimaryCode . '-' . $validated['phone_personal'],
                'password' => Hash::make($password),
                'address' => implode(',', array_filter([
                    $validated['street_add_first_personal'] ?? null,
                    $validated['street_add_sec_personal'] ?? null,
                    $validated['locality_land_mark_personal'] ?? null,
                    $validated['village_personal'] ?? null,
                ])),
                'postal_code' => $businessRequired ? ($validated['pincode_business'] ?? null) : ($validated['pincode_personal'] ?? null),
                'city' => $businessRequired ? ($validated['city_id_business'] ?? null) : ($validated['city_id_personal'] ?? null),
                'state' => $businessRequired ? ($validated['state_id_business'] ?? null) : ($validated['state_id_personal'] ?? null),
                'country' => $businessRequired ? ($validated['country_id_business'] ?? null) : ($validated['country_id_personal'] ?? null),
                'avatar' => $photoFile,
                'avatar_original' => $photoFile,
                'gst_no' => $validated['gst_no'] ?? null,
                'iec_no' => $typeOption === 'international' ? ($validated['iec_no'] ?? null) : null,
                'aadhaar_no' => $validated['aadhaar_no'] ?? null,
                'pan_no' => $validated['pan_no'] ?? null,
                'passport_no' => $validated['passport_no'] ?? null,
                'step' => '8',
            ]);

            $user->approval_status = 0;
            $user->save();

            $details = new UserDetails(['user_id' => $user->id]);
            $transportSelection = $this->resolveTransportSelection($request);
            $details->fill([
                'type_option' => $typeOption,
                'crm_id' => $validated['crm_id'],
                'transport_id' => $transportSelection['transport_id'],
                'booked_to_id' => $transportSelection['booked_to_id'],
                'transport' => $transportSelection['transport'],
                'booked_to' => $transportSelection['booked_to'],
                'salesman' => $request->input('salesman'),
                'dl_expiry' => $request->input('dl_expiry'),
                'dl1' => $request->input('dl1'),
                'dl2' => $request->input('dl2'),

                'gst_no' => $validated['gst_no'] ?? null,
                'gst_no_file' => $gstFile,
                'iec_no' => $typeOption === 'international' ? ($validated['iec_no'] ?? null) : null,
                'iec_no_file' => $iecFile,
                'registration_date' => $businessRequired ? ($validated['registration_date'] ?? null) : null,
                'const_of_business' => $businessRequired ? ($validated['const_of_business'] ?? null) : null,
                'gstin_current_status' => $typeOption === 'domestic' ? ($validated['gstin_current_status'] ?? null) : null,
                'uin_current_status' => $typeOption === 'international' ? ($validated['uin_current_status'] ?? null) : null,
                'con_person_name' => $businessRequired ? ($validated['con_person_name'] ?? null) : null,
                'company_name' => $businessRequired ? ($validated['company_name'] ?? null) : null,
                'customer_type' => $businessRequired ? ($validated['customer_type'] ?? null) : null,
                'current_status' => $validated['current_status'] ?? null,
                'street_add_first_business' => $businessRequired ? ($validated['street_add_first_business'] ?? null) : null,
                'street_add_sec_business' => $businessRequired ? ($validated['street_add_sec_business'] ?? null) : null,
                'locality_land_mark_business' => $businessRequired ? ($validated['locality_land_mark_business'] ?? null) : null,
                'village_business' => $businessRequired ? ($validated['village_business'] ?? null) : null,
                'post_business' => $businessRequired ? ($validated['post_business'] ?? null) : null,
                'city_id_business' => $businessRequired ? ($validated['city_id_business'] ?? null) : null,
                'district_business' => $businessRequired ? ($validated['district_business'] ?? null) : null,
                'state_id_business' => $businessRequired ? ($validated['state_id_business'] ?? null) : null,
                'pincode_business' => $businessRequired ? ($validated['pincode_business'] ?? null) : null,
                'country_id_business' => $businessRequired ? ($validated['country_id_business'] ?? null) : null,
                'country_code_business' => $businessRequired ? ($validated['country_code_business'] ?? null) : null,
                'prim_mobile_no_business' => $businessRequired && !empty($validated['phone_business'])
                    ? $businessPrimaryCode . '-' . $validated['phone_business']
                    : null,
                'prim_mobile_no_business_meta' => $businessRequired ? $request->input('phone_code_meta', '') : '',
                'alt_mobile_no_business' => ($businessRequired && $businessAltCode && $request->filled('alternate_mob_no_business'))
                    ? $businessAltCode . '-' . ($validated['alternate_mob_no_business'] ?? '')
                    : null,
                'alt_mobile_no_business_meta' => $businessRequired ? $request->input('alternate_mob_no_business_meta', '') : '',
                'prim_whats_app_no_business' => $businessRequired && !empty($validated['whats_app_no_business'])
                    ? $businessWhatsCode . '-' . $validated['whats_app_no_business']
                    : null,
                'prim_whats_app_no_business_meta' => $businessRequired ? $request->input('whats_app_no_business_meta', '') : '',
                'alternate_whats_app_no_business' => ($businessRequired && $businessAltWhatsCode && $request->filled('alternate_whats_app_no_business'))
                    ? $businessAltWhatsCode . '-' . ($validated['alternate_whats_app_no_business'] ?? '')
                    : null,
                'alternate_whats_app_no_business_meta' => $businessRequired ? $request->input('alternate_whats_app_no_business_meta', '') : '',
                'prim_email_business' => $businessRequired ? ($validated['prim_email_business'] ?? null) : null,
                'alt_email_business' => $businessRequired ? ($validated['alt_email_business'] ?? null) : null,
                'website_business' => $businessRequired ? ($validated['website_business'] ?? null) : null,
                'business_instagram_id' => $businessRequired ? ($validated['business_instagram_id'] ?? null) : null,
                'business_facebook_id' => $businessRequired ? ($validated['business_facebook_id'] ?? null) : null,
                'business_linkedin_id' => $businessRequired ? ($validated['business_linkedin_id'] ?? null) : null,
                'bank_name_business' => $businessRequired ? ($validated['bank_name_business'] ?? null) : null,
                'account_no_business' => $businessRequired ? ($validated['account_no_business'] ?? null) : null,
                'account_name_business' => $businessRequired ? ($validated['account_name_business'] ?? null) : null,
                'branch_code_business' => $businessRequired ? ($validated['branch_code_business'] ?? null) : null,
                'branch_name_business' => $businessRequired ? ($validated['branch_name_business'] ?? null) : null,
                'branch_address_business' => $businessRequired ? ($validated['branch_address_business'] ?? null) : null,
                'ifsc_code_business' => $businessRequired ? ($validated['ifsc_code_business'] ?? null) : null,
                'micr_code_business' => $typeOption === 'international' ? ($validated['micr_code_business'] ?? null) : null,
                'ad_code_business' => $typeOption === 'international' ? ($validated['ad_code_business'] ?? null) : null,

                'name' => $validated['name_personal'],
                'father_name' => $validated['father_name'] ?? null,
                'dob' => $validated['dob'] ?? null,
                'religion' => $validated['religion'] ?? null,
                'anniversary' => $validated['anniversary'] ?? null,
                'street_add_first' => $validated['street_add_first_personal'] ?? null,
                'street_add_sec' => $validated['street_add_sec_personal'] ?? null,
                'locality_land_mark' => $validated['locality_land_mark_personal'] ?? null,
                'village' => $validated['village_personal'] ?? null,
                'post' => $validated['post_personal'] ?? null,
                'city_id' => $validated['city_id_personal'] ?? null,
                'district' => $validated['district_personal'] ?? null,
                'state_id' => $validated['state_id_personal'] ?? null,
                'pincode' => $validated['pincode_personal'] ?? null,
                'country_id' => $validated['country_id_personal'] ?? null,
                'country_code' => $validated['country_code_personal'] ?? null,
                'prim_mobile_no' => $personalPrimaryCode . '-' . $validated['phone_personal'],
                'prim_mobile_no_meta' => $request->input('phone_personal_meta', ''),
                'alt_mobile_no' => $personalAltCode && $request->filled('alternate_mob_no_personal')
                    ? $personalAltCode . '-' . ($validated['alternate_mob_no_personal'] ?? '')
                    : null,
                'alt_mobile_no_meta' => $request->input('alternate_mob_no_personal_meta', ''),
                'prim_whats_app_no' => !empty($validated['whats_app_no_personal'])
                    ? $personalWhatsCode . '-' . $validated['whats_app_no_personal']
                    : null,
                'prim_whats_app_no_meta' => $request->input('whats_app_no_personal_meta', ''),
                'alt_whats_app_no' => $personalAltWhatsCode && $request->filled('alternate_whats_app_no_personal')
                    ? $personalAltWhatsCode . '-' . ($validated['alternate_whats_app_no_personal'] ?? '')
                    : null,
                'alt_whats_app_no_meta' => $request->input('alternate_whats_app_no_personal_meta', ''),
                'prim_email_personal' => $validated['prim_email_personal'],
                'alt_email_personal' => $validated['alt_email_personal'] ?? null,
                'bank_name_personal' => $validated['bank_name_personal'] ?? null,
                'account_no_personal' => $validated['account_no_personal'] ?? null,
                'account_name_personal' => $validated['account_name_personal'] ?? null,
                'branch_code_personal' => $validated['branch_code_personal'] ?? null,
                'branch_name_personal' => $validated['branch_name_personal'] ?? null,
                'branch_address_personal' => $validated['branch_address_personal'] ?? null,
                'ifsc_code_personal' => $validated['ifsc_code_personal'] ?? null,
                'micr_code_personal' => $typeOption === 'international' ? ($validated['micr_code_personal'] ?? null) : null,
                'ad_code_personal' => $typeOption === 'international' ? ($validated['ad_code_personal'] ?? null) : null,
                'photo_file' => $photoFile,
                'aadhaar_no' => $validated['aadhaar_no'] ?? null,
                'aadhaar_no_file' => $aadhaarFile,
                'pan_no' => $validated['pan_no'] ?? null,
                'pan_no_file' => $panFile,
                'passport_no' => $validated['passport_no'] ?? null,
                'passport_no_file' => $passportFile,

                'd_l_no_1' => $validated['d_l_no_1'] ?? null,
                'd_l_no_1_file' => $licenseFiles['d_l_no_1_file'],
                'd_l_no_2' => $validated['d_l_no_2'] ?? null,
                'd_l_no_2_file' => $licenseFiles['d_l_no_2_file'],
                'd_l_no_3' => $validated['d_l_no_3'] ?? null,
                'd_l_no_3_file' => $licenseFiles['d_l_no_3_file'],
                'doctor_hospital_reg_no' => $validated['doctor_hospital_reg_no'] ?? null,
                'doctor_hospital_reg_no_file' => $licenseFiles['doctor_hospital_reg_no_file'],
                'dairy_trust_ngo_reg_no' => $validated['dairy_trust_ngo_reg_no'] ?? null,
                'dairy_trust_ngo_reg_no_file' => $licenseFiles['dairy_trust_ngo_reg_no_file'],
                'cc_mdl_reg_no' => $validated['cc_mdl_reg_no'] ?? null,
                'cc_mdl_reg_no_file' => $licenseFiles['cc_mdl_reg_no_file'],
                'other_reg_no' => $validated['other_reg_no'] ?? null,
                'other_reg_no_file' => $licenseFiles['other_reg_no_file'],
            ]);
            $details->save();

            sync_business_addresses_to_address_book($user, $details);

            // try {
            //     EmailUtility::customer_registration_email('registration_from_system_email_to_customer', $user, $password);
            // } catch (\Exception $e) {
            //     DB::rollBack();
            //     if ($request->ajax()) {
            //         return response()->json([
            //             'errors' => [
            //                 'prim_email_personal' => [translate('Registration failed. Please try again later.')],
            //             ],
            //         ], 422);
            //     }
            //     flash(translate('Registration failed. Please try again later.'))->error();
            //     return back()->withInput();
            // }

            // $user->email_verified_at = date('Y-m-d H:m:s');
            // $user->save();
            // offerUserWelcomeCoupon();

            // if ((get_email_template_data('customer_reg_email_to_admin', 'status') == 1)) {
            //     try {
            //         EmailUtility::customer_registration_email('customer_reg_email_to_admin', $user, null);
            //     } catch (\Exception $e) {
            //     }
            // }

            DB::commit();

            // if (!empty($user->id) && function_exists('storeIPLocation')) {
            //     $createdUserId = $user->id;
            //     app()->terminating(function () use ($createdUserId) {
            //         storeIPLocation('users', $createdUserId);
            //     });
            // }

            if ($request->ajax()) {
                return response()->json([
                    'message' => translate('Customer created successfully'),
                    'redirect_url' => route('customers.business'),
                ]);
            }

            flash(translate('Customer created successfully'))->success();
            return redirect()->route('customers.business');
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     */
    public function edit($id)
    {
        $user = User::with('details')->findOrFail($id);
        $details = $user->details;

        $countries = Cache::remember('countries_for_customer_edit', 86400, function () {
            return Country::select('id', 'name', 'code')->orderBy('name')->get();
        });
        $transports = $this->activeTransportsWithBookedTo();
        $currentStatuses = UserDetails::CURRENT_STATUSES;
        $customerTypes = UserDetails::CUSTOMER_TYPES;

        return view('backend.customer.customers.edit', compact(
            'user',
            'details',
            'countries',
            'transports',
            'currentStatuses',
            'customerTypes',
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     */
    public function update(Request $request, $id)
    {
        $user = User::with('details')->findOrFail($id);
        $details = $user->details ?? new UserDetails(['user_id' => $user->id]);
        $typeOption = $request->input('type_option', $details->type_option ?? 'domestic');

        $domesticChoice = $request->input('domestic_identity_selection', 'gst');
        $internationalChoice = $request->input('international_identity_selection', 'iec');
        $businessRequired = ($typeOption === 'domestic' && $domesticChoice === 'gst') || ($typeOption === 'international' && $internationalChoice === 'iec');

        // $gstRule = ($typeOption === 'domestic' && $domesticChoice === 'gst')
        //     ? ['nullable', 'regex:/^[0-9A-Z]{15}$/i']
        //     : ['nullable', 'string', 'max:255'];

        $businessRules = [
            'type_option' => 'required|in:domestic,international',
            'crm_id' => ['nullable', 'string', 'max:255', 'unique:user_details,crm_id,' . $details->id],
            'registration_date' => [$businessRequired ? 'required' : 'nullable'],
            'const_of_business' => [$businessRequired ? 'required' : 'nullable'],
            'con_person_name' => [$businessRequired ? 'required' : 'nullable', 'string', 'regex:/^[A-Za-z\\s]+$/', 'min:1', 'max:50'],
            'company_name' => [$businessRequired ? 'required' : 'nullable', 'string', 'min:1', 'max:150'],
            'customer_type' => ['nullable', Rule::in(UserDetails::CUSTOMER_TYPES)],
            'current_status' => ['nullable', Rule::in(UserDetails::CURRENT_STATUSES)],
            'street_add_first_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'min:1', 'max:150'],
            'street_add_sec_business' => ['nullable', 'string', 'min:1', 'max:150'],
            'locality_land_mark_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'min:1', 'max:150'],
            'village_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'min:1', 'max:150'],
            'post_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'min:1', 'max:150'],
            'district_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'min:1', 'max:150'],
            'country_code_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'min:1', 'max:150'],
            'pincode_business' => $businessRequired
                ? ['required', 'regex:/^\\d{6}$/']
                : ['nullable'],
            'city_id_business' => [$businessRequired ? 'required' : 'nullable', 'integer', 'exists:cities,id'],
            'state_id_business' => [$businessRequired ? 'required' : 'nullable', 'integer', 'exists:states,id'],
            'country_id_business' => [$businessRequired ? 'required' : 'nullable', 'integer'],
            'phone_business' => [$businessRequired ? 'required' : 'nullable', 'regex:/^[\\d\\s\\-\\+]+$/', 'min:5', 'max:15'],
            'alternate_mob_no_business' => ['nullable', 'regex:/^[\\d\\s\\-\\+]+$/', 'min:5', 'max:15'],
            'whats_app_no_business' => [$businessRequired ? 'required' : 'nullable', 'regex:/^[\\d\\s\\-\\+]+$/', 'min:5', 'max:15'],
            'alternate_whats_app_no_business' => ['nullable', 'regex:/^[\\d\\s\\-\\+]+$/', 'min:5', 'max:15'],
            'prim_email_business' => [$businessRequired ? 'required' : 'nullable', 'email'],
            'alt_email_business' => ['nullable', 'email'],
            'website_business' => ['nullable'],
            'business_instagram_id' => ['nullable', 'string', 'max:255'],
            'business_facebook_id' => ['nullable', 'string', 'max:255'],
            'business_linkedin_id' => ['nullable', 'string', 'max:255'],
            'bank_name_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'max:255'],
            'account_no_business' => [$businessRequired ? 'required' : 'nullable', 'regex:/^\\d+$/', 'max:20'],
            'account_name_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'max:255'],
            'branch_code_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'max:50'],
            'branch_name_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'max:255'],
            'branch_address_business' => [$businessRequired ? 'required' : 'nullable', 'string', 'max:255'],
            'ifsc_code_business' => [$businessRequired ? 'required' : 'nullable', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            // Optional / conditional docs
            // 'gst_no' => $gstRule,
            'gst_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'gstin_current_status' => ['nullable', 'string'],
            'iec_no' => ['nullable', 'regex:/^[0-9A-Z]{10}$/i'],
            'iec_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'uin_current_status' => ['nullable', 'string'],
            'micr_code_business' => ['nullable', 'regex:/^\\d{9}$/'],
            'ad_code_business' => ['nullable', 'string', 'max:255'],
        ];

        $personalRules = [
            'photo_file' => [$details->photo_file ? 'nullable' : 'required', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'name_personal' => ['required', 'string', 'min:1', 'max:150'],
            'father_name' => ['required', 'string', 'min:1', 'max:150'],
            'dob' => ['required'],
            'religion' => ['nullable', 'string', 'max:150'],
            'anniversary' => ['nullable', 'date'],
            'street_add_first_personal' => ['required', 'string', 'min:1', 'max:150'],
            'street_add_sec_personal' => ['nullable', 'string', 'min:1', 'max:150'],
            'locality_land_mark_personal' => ['required', 'string', 'min:1', 'max:150'],
            'village_personal' => ['required', 'string', 'min:1', 'max:150'],
            'post_personal' => ['required', 'string', 'min:1', 'max:150'],
            'district_personal' => ['required', 'string', 'min:1', 'max:150'],
            'country_code_personal' => ['required', 'string', 'min:1', 'max:150'],
            'pincode_personal' => ['required', 'regex:/^\\d{6}$/'],
            'city_id_personal' => ['required', 'integer', 'exists:cities,id'],
            'state_id_personal' => ['required', 'integer', 'exists:states,id'],
            'country_id_personal' => ['required', 'integer'],
            'phone_personal' => ['required', 'regex:/^[\\d\\s\\-\\+]+$/', 'min:5', 'max:15'],
            'alternate_mob_no_personal' => ['nullable', 'regex:/^[\\d\\s\\-\\+]+$/', 'min:5', 'max:15'],
            'whats_app_no_personal' => ['required', 'regex:/^[\\d\\s\\-\\+]+$/', 'min:5', 'max:15'],
            'alternate_whats_app_no_personal' => ['nullable', 'regex:/^[\\d\\s\\-\\+]+$/', 'min:5', 'max:15'],
            'prim_email_personal' => ['required', 'email'],
            'alt_email_personal' => ['nullable', 'email'],
            'bank_name_personal' => ['required', 'string', 'max:255'],
            'account_no_personal' => ['required', 'regex:/^\\d+$/', 'max:20'],
            'account_name_personal' => ['required', 'string', 'max:255'],
            'branch_code_personal' => ['required', 'string', 'max:50'],
            'branch_name_personal' => ['required', 'string', 'max:255'],
            'branch_address_personal' => ['required', 'string', 'max:255'],
            'ifsc_code_personal' => ['required', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'aadhaar_no' => ['nullable', 'regex:/^[0-9]{12}$/i'],
            'aadhaar_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'pan_no' => ['nullable', 'regex:/^[0-9A-Z]{10}$/i'],
            'pan_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'passport_no' => ['nullable', 'regex:/^[0-9A-Z]{1,15}$/i'],
            'passport_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'micr_code_personal' => ['nullable', 'regex:/^\\d{9}$/'],
            'ad_code_personal' => ['nullable', 'string', 'max:255'],
        ];

        $licenseRules = [
            'd_l_no_1' => ['nullable', 'string', 'max:255'],
            'd_l_no_1_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'd_l_no_2' => ['nullable', 'string', 'max:255'],
            'd_l_no_2_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'd_l_no_3' => ['nullable', 'string', 'max:255'],
            'd_l_no_3_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'doctor_hospital_reg_no' => ['nullable', 'string', 'max:255'],
            'doctor_hospital_reg_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'dairy_trust_ngo_reg_no' => ['nullable', 'string', 'max:255'],
            'dairy_trust_ngo_reg_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'cc_mdl_reg_no' => ['nullable', 'string', 'max:255'],
            'cc_mdl_reg_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
            'other_reg_no' => ['nullable', 'string', 'max:255'],
            'other_reg_no_file' => ['nullable', 'mimes:jpg,jpeg,webp,png,pdf', 'max:5120'],
        ];

        $transportRules = [
            'transport' => ['nullable', 'string', 'max:255'],
            'booked_to' => ['nullable', 'string', 'max:255'],
            'salesman' => ['nullable', 'string', 'max:255'],
            'dl_expiry' => ['nullable', 'string', 'max:255'],
            'dl1' => ['nullable', 'string', 'max:255'],
            'dl2' => ['nullable', 'string', 'max:255'],
        ];

        // Simplified validation: only keep basic user email/phone checks per request; comment out the detailed rules above.
        // $validator = \Validator::make($request->all(), array_merge($businessRules, $personalRules, $licenseRules));
        // $validator->after(function ($v) use ($request, $details, $typeOption, $domesticChoice, $internationalChoice) { ... });
        $validator = \Validator::make($request->all(), [
            'prim_email_personal'  => ['required', 'email'],
            'religion'             => ['nullable', 'string', 'max:150'],
            'anniversary'          => ['nullable', 'date'],
            'customer_type'        => ['nullable', Rule::in(UserDetails::CUSTOMER_TYPES)],
            // 'prim_email_business'  => ['nullable', 'email'],
            'phone_personal'       => ['required', 'regex:/^[\\d\\s\\-\\+]+$/', 'min:5', 'max:15'],
            // 'phone_business'       => ['nullable'],
        ]);
        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            throw new ValidationException($validator);
        }
        // Keep full payload so downstream assignments continue to work.
        $validated = array_merge($request->all(), $validator->validated());

        $documentPath = public_path('uploads/document');
        if (!File::exists($documentPath)) {
            File::makeDirectory($documentPath, 0777, true, true);
        }

        $gstFile = $details->gst_no_file;
        if ($request->hasFile('gst_no_file')) {
            $file = $request->file('gst_no_file');
            $documentName = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
            $file->move($documentPath, $documentName);
            $gstFile = 'uploads/document/' . $documentName;
        }

        $iecFile = $details->iec_no_file;
        if ($request->hasFile('iec_no_file')) {
            $file = $request->file('iec_no_file');
            $documentName = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
            $file->move($documentPath, $documentName);
            $iecFile = 'uploads/document/' . $documentName;
        }

        $photoFile = $details->photo_file;
        if ($request->hasFile('photo_file')) {
            $file = $request->file('photo_file');
            $documentName = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
            $file->move($documentPath, $documentName);
            $photoFile = 'uploads/document/' . $documentName;
        }

        $aadhaarFile = $details->aadhaar_no_file;
        if ($request->hasFile('aadhaar_no_file')) {
            $file = $request->file('aadhaar_no_file');
            $documentName = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
            $file->move($documentPath, $documentName);
            $aadhaarFile = 'uploads/document/' . $documentName;
        }

        $panFile = $details->pan_no_file;
        if ($request->hasFile('pan_no_file')) {
            $file = $request->file('pan_no_file');
            $documentName = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
            $file->move($documentPath, $documentName);
            $panFile = 'uploads/document/' . $documentName;
        }

        $passportFile = $details->passport_no_file;
        if ($request->hasFile('passport_no_file')) {
            $file = $request->file('passport_no_file');
            $documentName = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
            $file->move($documentPath, $documentName);
            $passportFile = 'uploads/document/' . $documentName;
        }

        $removeLicense = $request->input('remove_license', []);

        $licenseFiles = [
            'd_l_no_1_file' => $details->d_l_no_1_file,
            'd_l_no_2_file' => $details->d_l_no_2_file,
            'd_l_no_3_file' => $details->d_l_no_3_file,
            'doctor_hospital_reg_no_file' => $details->doctor_hospital_reg_no_file,
            'dairy_trust_ngo_reg_no_file' => $details->dairy_trust_ngo_reg_no_file,
            'cc_mdl_reg_no_file' => $details->cc_mdl_reg_no_file,
            'other_reg_no_file' => $details->other_reg_no_file,
        ];

        foreach ($licenseFiles as $field => $current) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $documentName = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
                $file->move($documentPath, $documentName);
                $licenseFiles[$field] = 'uploads/document/' . $documentName;
            } elseif (isset($removeLicense[str_replace('_file', '', $field)])) {
                $licenseFiles[$field] = null;
            }
        }

        $businessPrimaryCode = $request->input('country_code_phone_code_business', $details->country_code_business ?? '');
        $businessAltCode = $request->input('country_code_alternate_mob_no_business', $details->country_code_business ?? '');
        $businessWhatsCode = $request->input('country_code_whats_app_no_business', $details->country_code_business ?? '');
        $businessAltWhatsCode = $request->input('country_code_alternate_whats_app_no_business', $details->country_code_business ?? '');

        $personalPrimaryCode = $request->input('country_code_phone_code_personal', $details->country_code ?? '');
        $personalAltCode = $request->input('country_code_alternate_mob_no_personal', $details->country_code ?? '');
        $personalWhatsCode = $request->input('country_code_whats_app_no_personal', $details->country_code ?? '');
        $personalAltWhatsCode = $request->input('country_code_alternate_whats_app_no_personal', $details->country_code ?? '');

        // Persist core user columns (users table)
        $user->update([
            'type_option' => $typeOption,
            'name' => $validated['name_personal'],
            'email' => $validated['prim_email_personal'],
            'phone' => '+' . $personalPrimaryCode . '-' . $validated['phone_personal'],
            'address' => implode(',', array_filter([
                $validated['street_add_first_personal'],
                $validated['street_add_sec_personal'] ?? '',
                $validated['locality_land_mark_personal'],
                $validated['village_personal'],
            ])),
            'city' => $validated['city_id_personal'],
            'state' => $validated['state_id_personal'],
            'country' => $validated['country_id_personal'],
            'postal_code' => $validated['pincode_personal'],
            'gst_no' => $validated['gst_no'] ?? null,
            // 'gst_no' => $typeOption === 'domestic' ? ($validated['gst_no'] ?? null) : null,
            'iec_no' => $typeOption === 'international' ? ($validated['iec_no'] ?? null) : null,
            'aadhaar_no' => $validated['aadhaar_no'] ?? null,
            'pan_no' => $validated['pan_no'] ?? null,
            'passport_no' => $validated['passport_no'] ?? null,
        ]);

        $transportSelection = $this->resolveTransportSelection($request, $details);
        $details->fill([
            'type_option' => $typeOption,
            'crm_id' => $request->input('crm_id', $details->crm_id ?? null),

            'transport_id' => $transportSelection['transport_id'],
            'booked_to_id' => $transportSelection['booked_to_id'],
            'transport' => $transportSelection['transport'],
            'booked_to' => $transportSelection['booked_to'],
            'salesman' => $request->input('salesman', $details->salesman ?? null),
            'dl_expiry' => $request->input('dl_expiry', $details->dl_expiry ?? null),
            'dl1' => $request->input('dl1', $details->dl1 ?? null),
            'dl2' => $request->input('dl2', $details->dl2 ?? null),

            'gst_no' => $validated['gst_no'] ?? null,
            // 'gst_no' => $typeOption === 'domestic' ? ($validated['gst_no'] ?? $details->gst_no) : null,
            'gst_no_file' => $gstFile,
            'iec_no' => $typeOption === 'international' ? ($validated['iec_no'] ?? $details->iec_no) : null,
            'iec_no_file' => $iecFile,
            'registration_date' => $businessRequired ? ($validated['registration_date'] ?? $details->registration_date) : $details->registration_date,
            'const_of_business' => $businessRequired ? ($validated['const_of_business'] ?? $details->const_of_business) : $details->const_of_business,
            'gstin_current_status' => $typeOption === 'domestic' ? ($validated['gstin_current_status'] ?? $details->gstin_current_status) : null,
            'uin_current_status' => $typeOption === 'international' ? ($validated['uin_current_status'] ?? $details->uin_current_status) : null,
            'con_person_name' => $businessRequired ? ($validated['con_person_name'] ?? $details->con_person_name) : $details->con_person_name,
            'company_name' => $businessRequired ? ($validated['company_name'] ?? $details->company_name) : $details->company_name,
            'customer_type' => $businessRequired ? ($validated['customer_type'] ?? null) : $details->customer_type,
            'current_status' => $validated['current_status'] ?? null,
            'street_add_first_business' => $businessRequired ? ($validated['street_add_first_business'] ?? $details->street_add_first_business) : $details->street_add_first_business,
            'street_add_sec_business' => $businessRequired ? ($validated['street_add_sec_business'] ?? $details->street_add_sec_business) : $details->street_add_sec_business,
            'locality_land_mark_business' => $businessRequired ? ($validated['locality_land_mark_business'] ?? $details->locality_land_mark_business) : $details->locality_land_mark_business,
            'village_business' => $businessRequired ? ($validated['village_business'] ?? $details->village_business) : $details->village_business,
            'post_business' => $businessRequired ? ($validated['post_business'] ?? $details->post_business) : $details->post_business,
            'city_id_business' => $businessRequired ? ($validated['city_id_business'] ?? $details->city_id_business) : $details->city_id_business,
            'district_business' => $businessRequired ? ($validated['district_business'] ?? $details->district_business) : $details->district_business,
            'state_id_business' => $businessRequired ? ($validated['state_id_business'] ?? $details->state_id_business) : $details->state_id_business,
            'pincode_business' => $businessRequired ? ($validated['pincode_business'] ?? $details->pincode_business) : $details->pincode_business,
            'country_id_business' => $businessRequired ? ($validated['country_id_business'] ?? $details->country_id_business) : $details->country_id_business,
            'country_code_business' => $businessRequired ? ($validated['country_code_business'] ?? $details->country_code_business) : $details->country_code_business,
            'prim_mobile_no_business' => $businessRequired && !empty($validated['phone_business'])
                ? $businessPrimaryCode . '-' . $validated['phone_business']
                : ($details->prim_mobile_no_business ?? null),
            'prim_mobile_no_business_meta' => $businessRequired ? $request->input('phone_code_meta', '') : ($details->prim_mobile_no_business_meta ?? ''),
            'alt_mobile_no_business' => ($businessRequired && $businessAltCode && $request->filled('alternate_mob_no_business'))
                ? $businessAltCode . '-' . ($validated['alternate_mob_no_business'] ?? '')
                : ($details->alt_mobile_no_business ?? null),
            'alt_mobile_no_business_meta' => $businessRequired ? $request->input('alternate_mob_no_business_meta', '') : ($details->alt_mobile_no_business_meta ?? ''),
            'prim_whats_app_no_business' => $businessRequired && !empty($validated['whats_app_no_business'])
                ? $businessWhatsCode . '-' . $validated['whats_app_no_business']
                : ($details->prim_whats_app_no_business ?? null),
            'prim_whats_app_no_business_meta' => $businessRequired ? $request->input('whats_app_no_business_meta', '') : ($details->prim_whats_app_no_business_meta ?? ''),
            'alternate_whats_app_no_business' => ($businessRequired && $businessAltWhatsCode && $request->filled('alternate_whats_app_no_business'))
                ? $businessAltWhatsCode . '-' . ($validated['alternate_whats_app_no_business'] ?? '')
                : ($details->alternate_whats_app_no_business ?? null),
            'alternate_whats_app_no_business_meta' => $businessRequired ? $request->input('alternate_whats_app_no_business_meta', '') : ($details->alternate_whats_app_no_business_meta ?? ''),
            'prim_email_business' => $businessRequired ? ($validated['prim_email_business'] ?? $details->prim_email_business) : $details->prim_email_business,
            'alt_email_business' => $businessRequired ? ($validated['alt_email_business'] ?? null) : $details->alt_email_business,
            'website_business' => $businessRequired ? ($validated['website_business'] ?? null) : $details->website_business,
            'business_instagram_id' => $businessRequired ? ($validated['business_instagram_id'] ?? $details->business_instagram_id) : $details->business_instagram_id,
            'business_facebook_id' => $businessRequired ? ($validated['business_facebook_id'] ?? $details->business_facebook_id) : $details->business_facebook_id,
            'business_linkedin_id' => $businessRequired ? ($validated['business_linkedin_id'] ?? $details->business_linkedin_id) : $details->business_linkedin_id,
            'bank_name_business' => $businessRequired ? ($validated['bank_name_business'] ?? $details->bank_name_business) : $details->bank_name_business,
            'account_no_business' => $businessRequired ? ($validated['account_no_business'] ?? $details->account_no_business) : $details->account_no_business,
            'account_name_business' => $businessRequired ? ($validated['account_name_business'] ?? $details->account_name_business) : $details->account_name_business,
            'branch_code_business' => $businessRequired ? ($validated['branch_code_business'] ?? $details->branch_code_business) : $details->branch_code_business,
            'branch_name_business' => $businessRequired ? ($validated['branch_name_business'] ?? $details->branch_name_business) : $details->branch_name_business,
            'branch_address_business' => $businessRequired ? ($validated['branch_address_business'] ?? $details->branch_address_business) : $details->branch_address_business,
            'ifsc_code_business' => $businessRequired ? ($validated['ifsc_code_business'] ?? $details->ifsc_code_business) : $details->ifsc_code_business,
            'micr_code_business' => $typeOption === 'international' ? ($validated['micr_code_business'] ?? null) : null,
            'ad_code_business' => $typeOption === 'international' ? ($validated['ad_code_business'] ?? null) : null,
            // 'transport' => $businessRequired ? ($validated['transport'] ?? $details->transport) : $details->transport,

            // Personal
            'name' => $validated['name_personal'],
            'father_name' => $validated['father_name'],
            'dob' => $validated['dob'],
            'religion' => $validated['religion'] ?? $details->religion,
            'anniversary' => $validated['anniversary'] ?? $details->anniversary,
            'street_add_first' => $validated['street_add_first_personal'],
            'street_add_sec' => $validated['street_add_sec_personal'] ?? null,
            'locality_land_mark' => $validated['locality_land_mark_personal'],
            'village' => $validated['village_personal'],
            'post' => $validated['post_personal'],
            'city_id' => $validated['city_id_personal'],
            'district' => $validated['district_personal'],
            'state_id' => $validated['state_id_personal'],
            'pincode' => $validated['pincode_personal'],
            'country_id' => $validated['country_id_personal'],
            'country_code' => $validated['country_code_personal'],
            'prim_mobile_no' => $personalPrimaryCode . '-' . $validated['phone_personal'],
            'prim_mobile_no_meta' => $request->input('phone_personal_meta', ''),
            'alt_mobile_no' => $personalAltCode && $request->filled('alternate_mob_no_personal')
                ? $personalAltCode . '-' . ($validated['alternate_mob_no_personal'] ?? '')
                : null,
            'alt_mobile_no_meta' => $request->input('alternate_mob_no_personal_meta', ''),
            'prim_whats_app_no' => $personalWhatsCode . '-' . $validated['whats_app_no_personal'],
            'prim_whats_app_no_meta' => $request->input('whats_app_no_personal_meta', ''),
            'alt_whats_app_no' => $personalAltWhatsCode && $request->filled('alternate_whats_app_no_personal')
                ? $personalAltWhatsCode . '-' . ($validated['alternate_whats_app_no_personal'] ?? '')
                : null,
            'alt_whats_app_no_meta' => $request->input('alternate_whats_app_no_personal_meta', ''),
            'prim_email_personal' => $validated['prim_email_personal'],
            'alt_email_personal' => $validated['alt_email_personal'] ?? null,
            'bank_name_personal' => $validated['bank_name_personal'],
            'account_no_personal' => $validated['account_no_personal'],
            'account_name_personal' => $validated['account_name_personal'],
            'branch_code_personal' => $validated['branch_code_personal'],
            'branch_name_personal' => $validated['branch_name_personal'],
            'branch_address_personal' => $validated['branch_address_personal'],
            'ifsc_code_personal' => $validated['ifsc_code_personal'],
            'micr_code_personal' => $typeOption === 'international' ? ($validated['micr_code_personal'] ?? $details->micr_code_personal) : null,
            'ad_code_personal' => $typeOption === 'international' ? ($validated['ad_code_personal'] ?? $details->ad_code_personal) : null,
            'photo_file' => $photoFile,
            'aadhaar_no' => $validated['aadhaar_no'] ?? $details->aadhaar_no,
            'aadhaar_no_file' => $aadhaarFile,
            'pan_no' => $validated['pan_no'] ?? $details->pan_no,
            'pan_no_file' => $panFile,
            'passport_no' => $validated['passport_no'] ?? $details->passport_no,
            'passport_no_file' => $passportFile,

            // License
            'd_l_no_1' => isset($removeLicense['d_l_no_1']) ? null : ($request->filled('d_l_no_1') ? $validated['d_l_no_1'] : ($request->has('d_l_no_1') ? null : $details->d_l_no_1)),
            'd_l_no_1_file' => $licenseFiles['d_l_no_1_file'],
            'd_l_no_2' => isset($removeLicense['d_l_no_2']) ? null : ($request->filled('d_l_no_2') ? $validated['d_l_no_2'] : ($request->has('d_l_no_2') ? null : $details->d_l_no_2)),
            'd_l_no_2_file' => $licenseFiles['d_l_no_2_file'],
            'd_l_no_3' => isset($removeLicense['d_l_no_3']) ? null : ($request->filled('d_l_no_3') ? $validated['d_l_no_3'] : ($request->has('d_l_no_3') ? null : $details->d_l_no_3)),
            'd_l_no_3_file' => $licenseFiles['d_l_no_3_file'],
            'doctor_hospital_reg_no' => isset($removeLicense['doctor_hospital_reg_no']) ? null : ($request->filled('doctor_hospital_reg_no') ? $validated['doctor_hospital_reg_no'] : ($request->has('doctor_hospital_reg_no') ? null : $details->doctor_hospital_reg_no)),
            'doctor_hospital_reg_no_file' => $licenseFiles['doctor_hospital_reg_no_file'],
            'dairy_trust_ngo_reg_no' => isset($removeLicense['dairy_trust_ngo_reg_no']) ? null : ($request->filled('dairy_trust_ngo_reg_no') ? $validated['dairy_trust_ngo_reg_no'] : ($request->has('dairy_trust_ngo_reg_no') ? null : $details->dairy_trust_ngo_reg_no)),
            'dairy_trust_ngo_reg_no_file' => $licenseFiles['dairy_trust_ngo_reg_no_file'],
            'cc_mdl_reg_no' => isset($removeLicense['cc_mdl_reg_no']) ? null : ($request->filled('cc_mdl_reg_no') ? $validated['cc_mdl_reg_no'] : ($request->has('cc_mdl_reg_no') ? null : $details->cc_mdl_reg_no)),
            'cc_mdl_reg_no_file' => $licenseFiles['cc_mdl_reg_no_file'],
            'other_reg_no' => isset($removeLicense['other_reg_no']) ? null : ($request->filled('other_reg_no') ? $validated['other_reg_no'] : ($request->has('other_reg_no') ? null : $details->other_reg_no)),
            'other_reg_no_file' => $licenseFiles['other_reg_no_file'],
        ]);
        $details->save();
        sync_business_addresses_to_address_book($user, $details);
          if ($request->ajax()) {
              return response()->json([
                  'message' => translate('Customer details updated successfully'),
                //   'redirect_url' => back()
                //   'redirect_url' => route('customers.business')
              ]);
          }

          flash(translate('Customer details updated successfully'))->success();

          return redirect()->back();
      }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $customer = User::findOrFail($id);
        $customer->customer_products()->delete(); 
        $customer->user_details()->delete();
        $customer->addresses()->delete();

        User::destroy($id);
        flash(translate('Customer has been deleted successfully'))->success();
        return redirect()->route('customers.index');
    }
    
    public function bulk_customer_delete(Request $request) {
        if($request->id) {
            foreach ($request->id as $customer_id) {
                $customer = User::findOrFail($customer_id);
                $customer->customer_products()->delete(); 
                $this->destroy($customer_id);
            }
        }
        
        return 1;
    }

    public function login($id)
    {
        $user = User::findOrFail(decrypt($id));

        auth()->login($user, true);

        return redirect()->route('dashboard');
    }

    public function ban($id) {
        $user = User::findOrFail(decrypt($id));

        if($user->banned == 1) {
            $user->banned = 0;
            flash(translate('Customer UnBanned Successfully'))->success();
        } else {
            $user->banned = 1;
            flash(translate('Customer Banned Successfully'))->success();
        }

        $user->save();
        
        return back();
    }


    public function view($id)
    {
        $user = UserDetails::where('user_id', decrypt($id))->first();
        $user2 = User::where('id', decrypt($id))->first();
        return view('backend.customer.customers.view', compact('user', 'user2'));
    }

    public function approval(Request $request) {
        $user = User::findOrFail($request->id);

        $approval = ($request->approval_status == 'approve') ? '1' : '0';

        if($approval == 1) {


            $user->approval_status = 1;
            $user->note = $request->note;
            $user->user_subtype = $request->user_subtype;

            $user->save();

            try {
                EmailUtility::approval_registration_email($user);
            } catch (\Exception $e) {}

            flash(translate('Customer Approve Successfully'))->success();

        } else {

            $user->approval_status = 2;
            $user->note = $request->note;
            $user->user_subtype = null;

            $user->save();

            try {
                EmailUtility::approval_reject_email($user);
            } catch (\Exception $e) {}

            flash(translate('Customer Not Approve Successfully'))->success();

        }

        return back();

    }


    public function update_credit(Request $request)
    {
        try {
            // ✅ Validate request data
            $validator = \Validator::make($request->all(), [
                'user_id'       => 'required|exists:users,id',
                'credit_status' => 'required|in:active,deactive',
                'credit_limit'  => 'required|numeric|min:0',
                'credit_days'   => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                // Laravel will automatically redirect back with errors
                return back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('open_modal', true); // Optional flag if you reopen a modal
            }

            $validated = $validator->validated();

            $user = User::findOrFail($validated['user_id']);

            $statusVal = $validated['credit_status'];
            // $statusStr = $validated['credit_status'];
            // $statusVal = $statusStr === 'active' ? 1 : 0;
            $newLimit  = (int) $validated['credit_limit'];
            $creditDays = (int) $validated['credit_days'];

            DB::beginTransaction();

            $oldLimit  = (int) ($user->credit_limit ?? 0);
            $oldRemain = (int) ($user->credit_remain ?? 0);

            // ✅ Safe rule for remain
            $delta     = $newLimit - $oldLimit;
            // $newRemain = $oldRemain;

            // if ($delta > 0) {
            //     $newRemain = min($newLimit, $oldRemain + $delta);
            // } elseif ($delta < 0) {
            //     $newRemain = min($newLimit, $oldRemain);
            // }

            // $newRemain = max(0, $newRemain);

            // ✅ Update user
            $user->update([
                'credit_status' => $statusVal,
                'credit_limit'  => $newLimit,
                // 'credit_remain' => $newRemain,
                'credit_days'   => $creditDays,
            ]);

            DB::commit();

            // ✅ Flash success + redirect back
            flash(translate('Credit details updated successfully'))->success();
            return back();

        } catch (\Throwable $e) {
            DB::rollBack(); // ✅ Always rollback on error
            report($e);

            flash(translate('Something went wrong: ' . $e->getMessage()))->error();
            return back()->withInput();
        }
    }


    /**
     * Import user detail values from an Excel file located in /public.
     * Uses header row to decide which columns to update (must include crm_id).
     */
    public function importTransportFromExcel(Request $request)
    {
        // Log::info("importTransportFromExcel: START", [
        //     'input' => $request->all()
        // ]);

        $fileInput = $request->input('file', 'user_details.xls');
        // $fileInput = $request->input('file', 'transport.xlsx');
        $fileName = basename($fileInput);
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Log::info("File details", [
        //     'file' => $fileName,
        //     'extension' => $extension
        // ]);

        if (!in_array($extension, ['xlsx', 'xls'])) {
            // Log::warning("Invalid file type", ['file' => $fileName]);
            return response()->json(['message' => 'Invalid file type. Only .xlsx or .xls allowed.'], 422);
        }

        $path = public_path($fileName);

        if (!File::exists($path)) {
            // Log::error("File not found", ['path' => $path]);
            return response()->json(['message' => "File not found at {$path}."], 404);
        }

        try {
            // Log::info("Reading Excel file", ['path' => $path]);
            $sheet = IOFactory::load($path)->getActiveSheet();
            $rows = $sheet->toArray();
        } catch (\Throwable $e) {
            // Log::error("Failed to read Excel", ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to read Excel file.', 'error' => $e->getMessage()], 500);
        }

        $updated = 0;
        $missing = 0;
        $skippedBlank = 0;

        // Build column map from header row
        $headerRow = $rows[0] ?? [];
        $headerMap = [];
        foreach ($headerRow as $colIndex => $header) {
            $normalized = Str::snake(trim((string) $header));
            if ($normalized === '' || in_array($normalized, $headerMap, true)) {
                continue;
            }
            $headerMap[$colIndex] = $normalized;
        }

        if (! in_array('crm_id', $headerMap, true)) {
            return response()->json(['message' => 'Missing required column: crm_id'], 422);
        }

        $crmIndex = array_search('crm_id', $headerMap, true);

        // Only allow columns that are fillable on UserDetails (except crm_id/user_id)
        $fillable = (new UserDetails())->getFillable();
        $updatableColumns = [];
        $ignoredColumns = [];
        foreach ($headerMap as $colIndex => $columnName) {
            if ($columnName === 'crm_id') {
                continue;
            }
            if ($columnName === 'user_id' || ! in_array($columnName, $fillable, true)) {
                $ignoredColumns[] = $columnName;
                continue;
            }
            $updatableColumns[$colIndex] = $columnName;
        }

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                // Header row already processed
                continue;
            }

            $crmId = trim((string) ($row[$crmIndex] ?? ''));

            if ($crmId === '') {
                $skippedBlank++;
                continue;
            }

            $details = UserDetails::where('crm_id', $crmId)->first();

            if (! $details) {
                $missing++;
                continue;
            }

            $changes = [];
            foreach ($updatableColumns as $colIndex => $columnName) {
                $value = $row[$colIndex] ?? null;
                $value = is_string($value) ? trim($value) : $value;
                $changes[$columnName] = ($value === '' || $value === null) ? null : $value;
            }

            if (! empty($changes)) {
                $details->fill($changes);
                $details->save();
                $updated++;
            }
        }

        // Log::info("IMPORT COMPLETE", [
        //     'file' => $fileName,
        //     'updated' => $updated,
        //     'missing' => $missing,
        //     'blank_skipped' => $skippedBlank,
        //     'columns_used' => array_values($updatableColumns),
        //     'columns_ignored' => $ignoredColumns,
        // ]);

        return response()->json([
            'file' => $fileName,
            'updated_rows' => $updated,
            'missing_crm_ids' => $missing,
            'blank_rows_skipped' => $skippedBlank,
            'columns_updated' => array_values($updatableColumns),
            'columns_ignored' => $ignoredColumns,
        ]);
    }

    private function ensureCustomerDocumentDirectory()
    {
        $documentPath = public_path('uploads/document');

        if (!File::exists($documentPath)) {
            File::makeDirectory($documentPath, 0777, true, true);
        }

        return $documentPath;
    }

    private function moveCustomerDocument(Request $request, string $field, string $documentPath, ?string $current = null)
    {
        if (!$request->hasFile($field)) {
            return $current;
        }

        $file = $request->file($field);
        $documentName = time() . '_' . str_replace(' ', '-', $file->getClientOriginalName());
        $file->move($documentPath, $documentName);

        return 'uploads/document/' . $documentName;
    }

    /**
     * Return null for unrestricted admin access and an array (possibly empty)
     * for a staff user's configured area assignments.
     */
    protected function currentStaffAreaAssignments(): ?array
    {
        $user = auth()->user();
        if (!$user || $user->user_type !== 'staff') {
            return null;
        }

        $rawAssignments = optional($user->staff)->area_assignments;
        $assignments = is_array($rawAssignments)
            ? $rawAssignments
            : json_decode((string) $rawAssignments, true);

        if (!is_array($assignments)) {
            return [];
        }

        return collect($assignments)
            ->filter(function ($area) {
                return is_array($area) && !empty($area['country_id']);
            })
            ->map(function ($area) {
                $districtId = $area['district_id'] ?? null;

                return [
                    'country_id' => (int) $area['country_id'],
                    'state_id' => filled($area['state_id'] ?? null) ? (int) $area['state_id'] : null,
                    'district_id' => filled($districtId) ? (int) $districtId : null,
                    'all_districts' => !empty($area['all_districts']) || !filled($districtId),
                ];
            })
            ->unique(function ($area) {
                return implode(':', [
                    $area['country_id'],
                    $area['state_id'] ?? '*',
                    $area['all_districts'] ? '*' : ($area['district_id'] ?? '*'),
                ]);
            })
            ->values()
            ->all();
    }

    protected function addAreaAssignmentConditions($query, array $assignments, string $scope, string $boolean = 'and'): void
    {
        $isBusiness = $scope === 'business';
        $countryColumn = $isBusiness ? 'country_id_business' : 'country_id';
        $stateColumn = $isBusiness ? 'state_id_business' : 'state_id';
        $cityColumn = $isBusiness ? 'city_id_business' : 'city_id';
        $method = $boolean === 'or' ? 'orWhere' : 'where';

        $query->{$method}(function ($areaQuery) use ($assignments, $countryColumn, $stateColumn, $cityColumn) {
            if (empty($assignments)) {
                $areaQuery->whereRaw('1 = 0');
                return;
            }

            foreach ($assignments as $area) {
                $areaQuery->orWhere(function ($assignmentQuery) use ($area, $countryColumn, $stateColumn, $cityColumn) {
                    $assignmentQuery->where($countryColumn, $area['country_id']);

                    if ($area['state_id'] !== null) {
                        $assignmentQuery->where($stateColumn, $area['state_id']);
                    }

                    if (!$area['all_districts'] && $area['district_id'] !== null) {
                        $assignmentQuery->where($cityColumn, $area['district_id']);
                    }
                });
            }
        });
    }

    protected function applyStaffAreaScope($users, array $assignments): void
    {
        $users->whereHas('details', function ($detailsQuery) use ($assignments) {
            $detailsQuery->where(function ($locationQuery) use ($assignments) {
                $this->addAreaAssignmentConditions($locationQuery, $assignments, 'business');
                $this->addAreaAssignmentConditions($locationQuery, $assignments, 'personal', 'or');
            });
        });
    }

    protected function locationSelectionIsAllowed($countryId, $stateId, $cityId, array $assignments): bool
    {
        if (!filled($countryId) && !filled($stateId) && !filled($cityId)) {
            return true;
        }

        if (!filled($countryId)) {
            return false;
        }

        foreach ($assignments as $area) {
            if ((int) $area['country_id'] !== (int) $countryId) {
                continue;
            }

            if (filled($stateId) && $area['state_id'] !== null && (int) $area['state_id'] !== (int) $stateId) {
                continue;
            }

            if (filled($cityId) && !$area['all_districts'] && $area['district_id'] !== null
                && (int) $area['district_id'] !== (int) $cityId) {
                continue;
            }

            return true;
        }

        return false;
    }

    protected function allowedStateIdsForArea(?array $assignments, int $countryId): ?array
    {
        if ($assignments === null) {
            return null;
        }

        $areas = collect($assignments)->where('country_id', $countryId);
        if ($areas->contains(function ($area) {
            return $area['state_id'] === null;
        })) {
            return null;
        }

        return $areas->pluck('state_id')->filter()->unique()->values()->all();
    }

    protected function allowedCityIdsForArea(?array $assignments, int $countryId, int $stateId): ?array
    {
        if ($assignments === null) {
            return null;
        }

        $areas = collect($assignments)->filter(function ($area) use ($countryId, $stateId) {
            return (int) $area['country_id'] === $countryId
                && ($area['state_id'] === null || (int) $area['state_id'] === $stateId);
        });

        if ($areas->contains(function ($area) {
            return $area['all_districts'] || $area['district_id'] === null;
        })) {
            return null;
        }

        return $areas->pluck('district_id')->filter()->unique()->values()->all();
    }


    /**
     * Ajax: dependent location options based on current selections.
     */
    public function locationOptions(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'nullable|in:business,personal',
            'country_id' => 'nullable|integer|exists:countries,id',
            'state' => 'nullable|integer|exists:states,id',
            'district' => 'nullable|string|max:255',
            'city' => 'nullable|integer|exists:cities,id',
            'post' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',
        ]);

        $scope = $validated['scope'] ?? 'business';
        $countryId = $validated['country_id'] ?? null;
        $stateId = $validated['state'] ?? null;
        $district = $validated['district'] ?? null;
        $cityId = $validated['city'] ?? null;
        $postName = $validated['post'] ?? null;
        $pincode = isset($validated['pincode']) ? trim($validated['pincode']) : null;
        $staffAreaAssignments = $this->currentStaffAreaAssignments();

        if ($staffAreaAssignments !== null
            && !$this->locationSelectionIsAllowed($countryId, $stateId, $cityId, $staffAreaAssignments)) {
            abort(403, translate('You cannot filter customers outside your assigned area.'));
        }

        $isBusiness = $scope === 'business';
        $countryCol = $isBusiness ? 'country_id_business' : 'country_id';
        $stateCol   = $isBusiness ? 'state_id_business' : 'state_id';
        $cityCol    = $isBusiness ? 'city_id_business' : 'city_id';
        $distCol    = $isBusiness ? 'district_business' : 'district';
        $postCol    = $isBusiness ? 'post_business' : 'post';
        $villageCol = $isBusiness ? 'village_business' : 'village';
        $pincodeCol = $isBusiness ? 'pincode_business' : 'pincode';

        $newDetailsQuery = function () use ($staffAreaAssignments, $scope) {
            $query = UserDetails::query();
            if ($staffAreaAssignments !== null) {
                $this->addAreaAssignmentConditions($query, $staffAreaAssignments, $scope);
            }

            return $query;
        };

        $location = null;
        if (filled($pincode)) {
            $matchQuery = $newDetailsQuery()
                ->whereRaw('TRIM(' . $pincodeCol . ') = ?', [$pincode]);

            if ($countryId) {
                $matchQuery->where($countryCol, $countryId);
            }

            $match = $matchQuery->orderByDesc('updated_at')->first([
                $countryCol,
                $stateCol,
                $cityCol,
                $distCol,
                $postCol,
                $villageCol,
            ]);

            if ($match) {
                $matchedCountryId = (int) $match->{$countryCol};
                $stateValue = trim((string) $match->{$stateCol});
                $cityValue = trim((string) $match->{$cityCol});

                $matchedState = null;
                if ($stateValue !== '') {
                    $matchedState = State::query()
                        ->where('country_id', $matchedCountryId)
                        ->where(function ($query) use ($stateValue) {
                            if (ctype_digit($stateValue)) {
                                $query->where('id', (int) $stateValue)
                                    ->orWhereRaw('LOWER(TRIM(name)) = ?', [strtolower($stateValue)]);
                            } else {
                                $query->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($stateValue)]);
                            }
                        })
                        ->first(['id', 'name']);
                }

                $matchedCity = null;
                if ($cityValue !== '') {
                    $matchedCity = City::query()
                        ->when($matchedState, function ($query) use ($matchedState) {
                            $query->where('state_id', $matchedState->id);
                        })
                        ->where(function ($query) use ($cityValue) {
                            if (ctype_digit($cityValue)) {
                                $query->where('id', (int) $cityValue)
                                    ->orWhereRaw('LOWER(TRIM(name)) = ?', [strtolower($cityValue)]);
                            } else {
                                $query->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($cityValue)]);
                            }
                        })
                        ->first(['id', 'name']);
                }

                $location = [
                    'country_id' => $matchedCountryId ?: null,
                    'state_id' => optional($matchedState)->id,
                    'district' => $match->{$distCol},
                    'city_id' => optional($matchedCity)->id,
                    'post' => $match->{$postCol},
                    'village' => $match->{$villageCol},
                ];

                $countryId = $countryId ?: $location['country_id'];
                $stateId = $stateId ?: $location['state_id'];
                $district = $district ?: $location['district'];
                $cityId = $cityId ?: $location['city_id'];
                $postName = $postName ?: $location['post'];
            }
        }

        $states = collect();
        if ($countryId) {
            $stateQuery = State::query()
                ->where('status', 1)
                ->where('country_id', $countryId);
            $allowedStateIds = $this->allowedStateIdsForArea($staffAreaAssignments, (int) $countryId);
            if ($allowedStateIds !== null) {
                $stateQuery->whereIn('id', $allowedStateIds);
            }
            $states = $stateQuery->orderBy('name')->get(['id', 'name']);
        }

        $cities = collect();
        $districts = collect();
        $posts = collect();
        $villages = collect();

        if ($countryId && $stateId) {
            $districts = $newDetailsQuery()
                ->where($countryCol, $countryId)
                ->where($stateCol, $stateId)
                ->pluck($distCol)
                ->filter()
                ->map(function ($val) {
                    $val = trim((string) $val);
                    return ['id' => $val, 'name' => $val];
                })
                ->unique('id')
                ->sortBy('name')
                ->values();
        }

        if ($countryId && $stateId) {
            $cityQuery = City::query()
                ->where('status', 1)
                ->where('state_id', $stateId);
            $allowedCityIds = $this->allowedCityIdsForArea(
                $staffAreaAssignments,
                (int) $countryId,
                (int) $stateId
            );
            if ($allowedCityIds !== null) {
                $cityQuery->whereIn('id', $allowedCityIds);
            }
            $cities = $cityQuery->orderBy('name')->get(['id', 'name']);
        }

        if ($countryId && $stateId && $district && $cityId) {
            $district = trim((string) $district);

            $posts = $newDetailsQuery()
                ->where($countryCol, $countryId)
                ->where($stateCol, $stateId)
                ->where($cityCol, $cityId)
                ->whereRaw('LOWER(TRIM(' . $distCol . ')) = ?', [strtolower($district)])
                ->pluck($postCol)
                ->filter()
                ->map(function ($val) {
                    $val = trim((string) $val);
                    return ['id' => $val, 'name' => $val];
                })
                ->unique('id')
                ->sortBy('name')
                ->values();
        }

        if ($countryId && $stateId && $district && $cityId && $postName) {
            $district = trim((string) $district);
            $postName = trim((string) $postName);

            $villages = $newDetailsQuery()
                ->where($countryCol, $countryId)
                ->where($stateCol, $stateId)
                ->where($cityCol, $cityId)
                ->whereRaw('LOWER(TRIM(' . $distCol . ')) = ?', [strtolower($district)])
                ->whereRaw('LOWER(TRIM(' . $postCol . ')) = ?', [strtolower($postName)])
                ->pluck($villageCol)
                ->filter()
                ->map(function ($val) {
                    $val = trim((string) $val);
                    return ['id' => $val, 'name' => $val];
                })
                ->unique('id')
                ->sortBy('name')
                ->values();
        }

        return response()->json([
            'states'    => $states,
            'districts' => $districts,
            'cities'    => $cities,
            'posts'     => $posts,
            'villages'  => $villages,
            'location'  => $location,
        ]);
    }
}





