<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Address;
use App\Models\City;
use App\Models\State;
use Auth;
use App\Models\Country;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $address = new Address;
        $requestedType = $request->input('type', Address::TYPE_SHIPPING);
        $address->type = in_array($requestedType, [Address::TYPE_BILLING, Address::TYPE_SHIPPING], true)
            ? $requestedType
            : Address::TYPE_SHIPPING;
        if ($request->has('customer_id')) {
            $address->user_id   = $request->customer_id;
        } else {
            $address->user_id   = Auth::user()->id;
        }
        $address->address       = $request->address;
        $address->country_id    = $request->country_id;
        $address->state_id      = $request->state_id;
        $address->city_id       = $request->city_id;
        $address->longitude     = $request->longitude;
        $address->latitude      = $request->latitude;
        $address->postal_code   = $request->postal_code;
        $address->phone         = $request->country_code_phone_code_addr.'-'.$request->phone;

        $address->save();

        flash(translate('Address info Stored successfully'))->success();
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
        $data['address_data'] = Address::findOrFail($id);
        $data['states'] = State::where('status', 1)->where('country_id', $data['address_data']->country_id)->get();
        $data['cities'] = City::where('status', 1)->where('state_id', $data['address_data']->state_id)->get();

        $returnHTML = view('frontend.partials.address.address_edit_modal', $data)->render();
        return response()->json(array('data' => $data, 'html' => $returnHTML));
        //        return ;
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
        $address = Address::findOrFail($id);

        $requestedType = $request->input('type', $address->type ?? Address::TYPE_SHIPPING);
        $address->type = in_array($requestedType, [Address::TYPE_BILLING, Address::TYPE_SHIPPING], true)
            ? $requestedType
            : ($address->type ?? Address::TYPE_SHIPPING);

        $address->address       = $request->address;
        $address->country_id    = $request->country_id;
        $address->state_id      = $request->state_id;
        $address->city_id       = $request->city_id;
        $address->longitude     = $request->longitude;
        $address->latitude      = $request->latitude;
        $address->postal_code   = $request->postal_code;
        // $address->phone         = $request->phone;
        $address->phone         = $request->country_code_phone_code_addr_edit.'-'.$request->phone;


        $address->save();

        flash(translate('Address info updated successfully'))->success();
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
        $address = Address::findOrFail($id);
        if (!$address->set_default) {
            $address->delete();
            return back();
        }
        flash(translate('Default address cannot be deleted'))->warning();
        return back();
    }

    public function getStates(Request $request)
    {
        $states = State::where('status', 1)->where('country_id', $request->country_id)->get();
        $html = '<option value="">' . translate("Select State") . '</option>';

        foreach ($states as $state) {
            $html .= '<option value="' . $state->id . '">' . $state->name . '</option>';
        }

        echo json_encode($html);
    }

    public function getCities(Request $request)
    {
        $cities = City::where('status', 1)->where('state_id', $request->state_id)->get();
        $html = '<option value="">' . translate("Select City") . '</option>';

        foreach ($cities as $row) {
            $html .= '<option value="' . $row->id . '">' . $row->getTranslation('name') . '</option>';
        }

        echo json_encode($html);
    }

    public function getLocation(Request $request)
    {
        $country = null;
        if ($request->filled('country_id')) {
            $country = Country::find($request->country_id);
        }

        $countryCode = $country ? $country->code : null;

        // Fetch location data (will include country_code from response)
        $locationData = get_location_by_postalcode($countryCode, $request->postal_code);

        // If country not provided, attempt to resolve from lookup
        if (!$country && !empty($locationData['country_code'])) {
            $country = Country::where('code', strtoupper($locationData['country_code']))->first();
        }

        $stateName = isset($locationData['state']) ? trim($locationData['state']) : null;
        $cityName = isset($locationData['city']) ? trim($locationData['city']) : null;

        $normalized = function ($value) {
            return $value !== null ? strtolower(trim($value)) : null;
        };

        $state = null;
        if ($stateName !== null && $stateName !== '') {
            $state = State::query()
                ->when($country, function ($q) use ($country) {
                    $q->where('country_id', $country->id);
                })
                ->whereRaw('LOWER(name) = ?', [$normalized($stateName)])
                ->first();
        }

        $city = null;
        if ($cityName !== null && $cityName !== '') {
            $cityQuery = City::query()
                ->whereRaw('LOWER(name) = ?', [$normalized($cityName)]);

            if ($state) {
                $cityQuery->where('state_id', $state->id);
            } elseif ($country) {
                $cityQuery->whereHas('state', function ($q) use ($country) {
                    $q->where('country_id', $country->id);
                });
            }

            $city = $cityQuery->first();
        }

        return response()->json([
            'state_id' => $state ? $state->id : null,
            'city_id' => $city ? $city->id : null,
            'state_name' => $state ? $state->name : null,
            'city_name' => $city ? $city->name : null,
            'country_id' => $country ? $country->id : null,
            'country_code' => $country ? $country->code : ($locationData['country_code'] ?? null),
            'village' => $locationData['village'] ?? $locationData['placename'] ?? null,
            'district' => $locationData['district'] ?? null,
            'postal_code' => $locationData['postal_code'] ?? $request->postal_code,
        ]);        
    }  

    public function set_default($id)
    {
        foreach (Auth::user()->addresses as $key => $address) {
            $address->set_default = 0;
            $address->save();
        }
        $address = Address::findOrFail($id);
        $address->set_default = 1;
        $address->save();

        return back();
    }
}
