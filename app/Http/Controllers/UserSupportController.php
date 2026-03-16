<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserSupportController extends Controller
{
    /**
     * Show support staff for the authenticated user.
     *
     * Staff are matched based on their area_assignments JSON against
     * the user's business/personal country & state (user_details) and
     * address book entries (addresses table).
     *
     * Only users with a non-null type_option (on user_details) can access.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->type_option === null) {
            abort(403);
        }

        $details = $user->details;

        $countryIds = [];
        $stateIds   = [];

        if ($details) {
            if ($details->country_id_business) {
                $countryIds[] = (int) $details->country_id_business;
            }
            if ($details->state_id_business) {
                $stateIds[] = (string) $details->state_id_business;
            }

            if ($details->country_id) {
                $countryIds[] = (int) $details->country_id;
            }
            if ($details->state_id) {
                $stateIds[] = (string) $details->state_id;
            }
        }

        $addresses = $user->addresses()->select(['country_id', 'state_id'])->get();

        foreach ($addresses as $address) {
            if ($address->country_id) {
                $countryIds[] = (int) $address->country_id;
            }
            if ($address->state_id) {
                $stateIds[] = (string) $address->state_id;
            }
        }

        $countryIds = array_values(array_unique(array_filter($countryIds, static function ($id) {
            return $id !== null;
        })));

        $stateIds = array_values(array_unique(array_filter($stateIds, static function ($id) {
            return $id !== null && $id !== '';
        })));

        if (empty($countryIds) || empty($stateIds)) {
            $staffs = collect();

            return view('frontend.user.support.index', compact('staffs'));
        }

        $staffs = Staff::with('user', 'role')
            ->whereNotNull('area_assignments')
            ->get()
            ->filter(function (Staff $staff) use ($countryIds, $stateIds) {
                $assignments = json_decode($staff->area_assignments ?? '[]', true);

                if (empty($assignments) || !is_array($assignments)) {
                    return false;
                }

                foreach ($assignments as $area) {
                    $staffCountryId = isset($area['country_id']) ? (int) $area['country_id'] : null;
                    if (!$staffCountryId || !in_array($staffCountryId, $countryIds, true)) {
                        continue;
                    }

                    $staffStateId = isset($area['state_id']) && $area['state_id'] !== null && $area['state_id'] !== ''
                        ? (string) $area['state_id']
                        : null;

                    if ($staffStateId === null) {
                        return true;
                    }

                    if (in_array($staffStateId, $stateIds, true)) {
                        return true;
                    }
                }

                return false;
            })
            ->values();

        return view('frontend.user.support.index', compact('staffs'));
    }
}

