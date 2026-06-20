<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Staff;
use App\Models\Role;
use App\Models\User;
use Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:view_all_staffs'])->only('index');
        $this->middleware(['permission:add_staff'])->only('create');
        $this->middleware(['permission:edit_staff'])->only('edit');
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
            'designation' => 'nullable|string|max:255',
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
                $staff->designation = $request->designation;
                $staff->display_email = $request->filled('display_email') ? trim($request->display_email) : null;
                $staff->area_assignments = $this->prepareAreaAssignmentsFromRequest($request);

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
            'designation' => 'nullable|string|max:255',
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
            $staff->designation = $request->designation;
            $staff->display_email = $request->filled('display_email') ? trim($request->display_email) : null;
            $staff->area_assignments = $this->prepareAreaAssignmentsFromRequest($request);

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
        User::destroy(Staff::findOrFail($id)->user->id);
        if (Staff::destroy($id)) {
            Cache::forget('lead_options.assignees');
            flash(translate('Staff has been deleted successfully'))->success();
            return redirect()->route('staffs.index');
        }

        flash(translate('Something went wrong'))->error();
        return back();
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
}
