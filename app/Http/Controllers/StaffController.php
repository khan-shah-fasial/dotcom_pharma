<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Staff;
use App\Models\Role;
use App\Models\User;
use Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:view_all_staffs'])->only('index');
        $this->middleware(['permission:add_staff'])->only('create');
        $this->middleware(['permission:edit_staff'])->only(['edit', 'update', 'updateStatus']);
        $this->middleware(['permission:delete_staff'])->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $staffs = Staff::with('user', 'role')->paginate(10);
        return view('backend.staff.staffs.index', compact('staffs'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::where('id','!=',1)->orderBy('id', 'desc')->get();
        $countries = get_active_countries();
        return view('backend.staff.staffs.create', compact('roles', 'countries'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'display_email' => 'nullable|email|max:255',
            'mobile' => 'required|string|max:50',
            'password' => 'required|string|min:6',
            'role_id' => 'required|integer|exists:roles,id',
            'status' => 'required|boolean',
            'designation' => 'nullable|string|max:255',
            'aadhaar_card_no' => ['nullable', 'regex:/^[0-9]{12}$/'],
            'pan_no' => ['nullable', 'regex:/^[A-Za-z]{5}[0-9]{4}[A-Za-z]$/'],
            'bank_account_holder_name' => 'nullable|required_with:bank_name,bank_account_number,bank_ifsc_code|string|max:255',
            'bank_name' => 'nullable|required_with:bank_account_holder_name,bank_account_number,bank_ifsc_code|string|max:255',
            'bank_branch_name' => 'nullable|string|max:255',
            'bank_account_number' => ['nullable', 'required_with:bank_account_holder_name,bank_name,bank_ifsc_code', 'string', 'max:34', 'regex:/^[0-9]+$/'],
            'bank_account_type' => ['nullable', Rule::in(['savings', 'current', 'salary'])],
            'bank_ifsc_code' => ['nullable', 'required_with:bank_account_holder_name,bank_name,bank_account_number', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/'],
            'attendance_id' => 'nullable|string|max:100|unique:staff,attendance_id',
            'attachments' => 'nullable|string|max:20000',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_number' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+()\-\s]+$/'],
            'date_of_birth' => 'nullable|date|before_or_equal:today',
            'religion' => 'nullable|string|max:100',
            'anniversary_date' => 'nullable|date',
        ]);

        if (User::where('email', $request->email)->first() == null) {
            $user = new User;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->mobile;
            $user->user_type = "staff";
            $user->password = Hash::make($request->password);
            $user->avatar = $request->avatar;
            $user->avatar_original = $request->avatar;

            if ($user->save()) {
                $staff = new Staff;
                $staff->user_id = $user->id;
                $staff->role_id = $request->role_id;
                $staff->status = $request->boolean('status');
                $staff->designation = $request->designation;
                $staff->display_email = $request->filled('display_email') ? trim($request->display_email) : null;
                $staff->area_assignments = $this->prepareAreaAssignmentsFromRequest($request);
                $this->fillAdditionalDetails($staff, $request);

                $user->assignRole(Role::findOrFail($request->role_id)->name);
                if ($staff->save()) {
                    Cache::forget('lead_options.assignees');
                    flash(translate('Staff has been inserted successfully'))->success();
                    return redirect()->route('staffs.index');
                }
            }
        }

        flash(translate('Email already used'))->error();
        return back();
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
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $staff = Staff::findOrFail(decrypt($id));
        $roles = Role::where('id','!=',1)->orderBy('id', 'desc')->get();
        $countries = get_active_countries();
        return view('backend.staff.staffs.edit', compact('staff', 'roles', 'countries'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $staff = Staff::findOrFail($id);
        $user = $staff->user;

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'display_email' => 'nullable|email|max:255',
            'mobile' => 'required|string|max:50',
            'password' => 'nullable|string|min:6',
            'role_id' => 'required|integer|exists:roles,id',
            'status' => 'required|boolean',
            'designation' => 'nullable|string|max:255',
            'aadhaar_card_no' => ['nullable', 'regex:/^[0-9]{12}$/'],
            'pan_no' => ['nullable', 'regex:/^[A-Za-z]{5}[0-9]{4}[A-Za-z]$/'],
            'bank_account_holder_name' => 'nullable|required_with:bank_name,bank_account_number,bank_ifsc_code|string|max:255',
            'bank_name' => 'nullable|required_with:bank_account_holder_name,bank_account_number,bank_ifsc_code|string|max:255',
            'bank_branch_name' => 'nullable|string|max:255',
            'bank_account_number' => ['nullable', 'required_with:bank_account_holder_name,bank_name,bank_ifsc_code', 'string', 'max:34', 'regex:/^[0-9]+$/'],
            'bank_account_type' => ['nullable', Rule::in(['savings', 'current', 'salary'])],
            'bank_ifsc_code' => ['nullable', 'required_with:bank_account_holder_name,bank_name,bank_account_number', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/'],
            'attendance_id' => ['nullable', 'string', 'max:100', Rule::unique('staff', 'attendance_id')->ignore($staff->id)],
            'attachments' => 'nullable|string|max:20000',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_number' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+()\-\s]+$/'],
            'date_of_birth' => 'nullable|date|before_or_equal:today',
            'religion' => 'nullable|string|max:100',
            'anniversary_date' => 'nullable|date',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->mobile;
        $user->avatar = $request->avatar;
        $user->avatar_original = $request->avatar;

        if (strlen($request->password) > 0) {
            $user->password = Hash::make($request->password);
        }
        if ($user->save()) {
            $staff->role_id = $request->role_id;
            $staff->status = $request->boolean('status');
            $staff->designation = $request->designation;
            $staff->display_email = $request->filled('display_email') ? trim($request->display_email) : null;
            $staff->area_assignments = $this->prepareAreaAssignmentsFromRequest($request);
            $this->fillAdditionalDetails($staff, $request);

            if ($staff->save()) {
                $user->syncRoles(Role::findOrFail($request->role_id)->name);
                Cache::forget('lead_options.assignees');
                flash(translate('Staff has been updated successfully'))->success();
                return redirect()->route('staffs.index');
            }
        }

        flash(translate('Something went wrong'))->error();
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $staff = Staff::with('user')->findOrFail($id);
        $linkedTables = $this->linkedStaffTables($staff);

        if (!empty($linkedTables)) {
            flash(translate('Staff cannot be deleted because linked records exist') . ': ' . implode(', ', $linkedTables))->warning();
            return back();
        }

        try {
            DB::transaction(function () use ($staff) {
                $user = $staff->user;
                $staff->delete();

                if ($user) {
                    $user->delete();
                }
            });

            Cache::forget('lead_options.assignees');
            flash(translate('Staff has been deleted successfully'))->success();
            return redirect()->route('staffs.index');
        } catch (QueryException $exception) {
            report($exception);
            flash(translate('Staff cannot be deleted because linked records exist'))->warning();
            return back();
        }
    }

    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:staff,id'],
            'status' => ['required', 'boolean'],
        ]);

        $staff = Staff::findOrFail($validated['id']);
        $staff->status = (bool) $validated['status'];

        if ($staff->save()) {
            Cache::forget('lead_options.assignees');
            return 1;
        }

        return 0;
    }

    protected function linkedStaffTables(Staff $staff): array
    {
        $checks = [
            ['pickup_points', 'staff_id', $staff->id],
            ['leads', 'assigned_to', $staff->user_id],
            ['leads', 'created_by', $staff->user_id],
            ['lead_activities', 'created_by', $staff->user_id],
            ['orders', 'sales_person_id', $staff->user_id],
            ['orders', 'sales_executive_id', $staff->user_id],
            ['orders', 'packed_by', $staff->user_id],
            ['orders', 'checked_by', $staff->user_id],
            ['orders', 'billing_by', $staff->user_id],
            ['uploads', 'user_id', $staff->user_id],
            ['tickets', 'user_id', $staff->user_id],
            ['ticket_replies', 'user_id', $staff->user_id],
            ['conversations', 'sender_id', $staff->user_id],
            ['conversations', 'receiver_id', $staff->user_id],
            ['messages', 'user_id', $staff->user_id],
            ['transports', 'created_by', $staff->user_id],
            ['booked_to', 'created_by', $staff->user_id],
            ['local_delivery_partners', 'created_by', $staff->user_id],
        ];

        $linkedTables = collect($checks)
            ->filter(function ($check) {
                return Schema::hasTable($check[0])
                    && Schema::hasColumn($check[0], $check[1])
                    && DB::table($check[0])->where($check[1], $check[2])->exists();
            })
            ->pluck(0)
            ->unique()
            ->values()
            ->all();

        if (
            Schema::hasTable('contacts')
            && Schema::hasColumn('contacts', 'data')
            && DB::table('contacts')->where('data->staff->staff_id', $staff->id)->exists()
        ) {
            $linkedTables[] = 'contacts';
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            try {
                $foreignKeys = DB::table('information_schema.KEY_COLUMN_USAGE')
                    ->where('REFERENCED_TABLE_SCHEMA', DB::connection()->getDatabaseName())
                    ->whereIn('REFERENCED_TABLE_NAME', ['staff', 'users'])
                    ->whereNotNull('REFERENCED_COLUMN_NAME')
                    ->get(['TABLE_NAME', 'COLUMN_NAME', 'REFERENCED_TABLE_NAME']);

                foreach ($foreignKeys as $foreignKey) {
                    if ($foreignKey->TABLE_NAME === 'staff' && $foreignKey->COLUMN_NAME === 'user_id') {
                        continue;
                    }

                    $linkedId = $foreignKey->REFERENCED_TABLE_NAME === 'staff'
                        ? $staff->id
                        : $staff->user_id;

                    if (DB::table($foreignKey->TABLE_NAME)->where($foreignKey->COLUMN_NAME, $linkedId)->exists()) {
                        $linkedTables[] = $foreignKey->TABLE_NAME;
                    }
                }
            } catch (QueryException $exception) {
                report($exception);
            }
        }

        return array_values(array_unique($linkedTables));
    }

    /**
     * Build a normalized area assignments payload from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function prepareAreaAssignmentsFromRequest(Request $request)
    {
        $countryIds  = (array) $request->input('area_country_id', []);
        $stateIds    = (array) $request->input('area_state_id', []);
        $districtIds = (array) $request->input('area_district_id', []);

        $areas = [];

        foreach ($countryIds as $index => $countryId) {
            if (empty($countryId)) {
                continue;
            }

            $stateId    = $stateIds[$index] ?? null;
            $districtId = $districtIds[$index] ?? null;

            $allDistricts = ($districtId === null || $districtId === '' || $districtId === 'all');

            $areas[] = [
                'country_id'    => (int) $countryId,
                'state_id'      => !empty($stateId) ? (int) $stateId : null,
                'district_id'   => (!$allDistricts && !empty($districtId)) ? (int) $districtId : null,
                'all_districts' => $allDistricts,
            ];
        }

        return !empty($areas) ? json_encode($areas) : null;
    }

    protected function fillAdditionalDetails(Staff $staff, Request $request): void
    {
        foreach ([
            'aadhaar_card_no',
            'bank_account_holder_name',
            'bank_name',
            'bank_branch_name',
            'bank_account_number',
            'bank_account_type',
            'attendance_id',
            'attachments',
            'emergency_contact_name',
            'emergency_contact_number',
            'date_of_birth',
            'religion',
            'anniversary_date',
        ] as $field) {
            $value = $request->input($field);
            $staff->{$field} = is_string($value) ? (trim($value) ?: null) : $value;
        }

        $panNo = trim((string) $request->input('pan_no'));
        $staff->pan_no = $panNo === '' ? null : strtoupper($panNo);

        $ifscCode = trim((string) $request->input('bank_ifsc_code'));
        $staff->bank_ifsc_code = $ifscCode === '' ? null : strtoupper($ifscCode);
    }
}
