@extends('backend.layouts.app')

@section('content')

<div class="row">
    <div class="col-lg-6 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Staff Information')}}</h5>
            </div>

            <form action="{{ route('staffs.update', $staff->id) }}" method="POST">
                <input name="_method" type="hidden" value="PATCH">
            	@csrf
                <div class="card-body">
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="avatar">{{ translate('Staff Photo') }}</label>
                        <div class="col-sm-9">
                            <div class="input-group" data-toggle="aizuploader" data-type="image">
                                <div class="input-group-prepend">
                                    <div class="input-group-text bg-soft-secondary">{{ translate('Browse') }}</div>
                                </div>
                                <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                                <input type="hidden" name="avatar" value="{{ $staff->user->avatar_original }}" class="selected-files">
                            </div>
                            <div class="file-preview box sm"></div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="name">{{translate('Name')}}</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="{{translate('Name')}}" id="name" name="name" value="{{ $staff->user->name }}" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="email">{{translate('Email')}}</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="{{translate('Email')}}" id="email" name="email" value="{{ $staff->user->email }}" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="mobile">{{translate('Phone')}}</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="{{translate('Phone')}}" id="mobile" name="mobile" value="{{ $staff->user->phone }}" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="display_email">{{ translate('Display Mail ID') }}</label>
                        <div class="col-sm-9">
                            <input type="email" placeholder="{{ translate('Public email shown on support page') }}" id="display_email"
                                name="display_email" value="{{ old('display_email', $staff->display_email) }}" class="form-control">
                            @error('display_email') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="designation">{{ translate('Designation') }}</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="{{ translate('Designation') }}" id="designation" name="designation" value="{{ $staff->designation }}" class="form-control">
                        </div>
                    </div>
                    @include('backend.staff.staffs._additional_fields', ['staff' => $staff])
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="password">{{translate('Password')}}</label>
                        <div class="col-sm-9">
                            <input type="password" placeholder="{{translate('Password')}}" id="password" name="password" class="form-control">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="name">{{translate('Role')}}</label>
                        <div class="col-sm-9">
                            <select name="role_id" required class="form-control aiz-selectpicker">
                                @foreach($roles as $role)
                                    <option value="{{$role->id}}" @php if($staff->role_id == $role->id) echo "selected"; @endphp >{{$role->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @php
                        $areaAssignments = $staff->area_assignments ? json_decode($staff->area_assignments, true) : [];
                    @endphp
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label">{{ translate('Area Assign') }}</label>
                        <div class="col-sm-9">
                            <div id="area-assign-container">
                                @if(!empty($areaAssignments))
                                    @foreach($areaAssignments as $index => $area)
                                        <div class="row gutters-5 area-assign-row mb-2">
                                            <div class="col-md-4 mb-2 mb-md-0">
                                                <select class="select2 form-control aiz-selectpicker area-country" name="area_country_id[]" data-toggle="select2" data-placeholder="{{ translate('Select Country') }}" data-live-search="true">
                                                    <option value="">{{ translate('Select Country') }}</option>
                                                    @foreach($countries as $country)
                                                        <option value="{{ $country->id }}" @if($area['country_id'] == $country->id) selected @endif>{{ $country->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-2 mb-md-0">
                                                @php
                                                    $selectedCountryId = $area['country_id'] ?? null;
                                                    $states = $selectedCountryId ? \App\Models\State::where('status', 1)->where('country_id', $selectedCountryId)->get() : collect();
                                                @endphp
                                                <select class="select2 form-control aiz-selectpicker area-state" name="area_state_id[]" data-toggle="select2" data-placeholder="{{ translate('Select State') }}" data-live-search="true">
                                                    <option value="">{{ translate('Select State') }}</option>
                                                    @foreach($states as $state)
                                                        <option value="{{ $state->id }}" @if(($area['state_id'] ?? null) == $state->id) selected @endif>{{ $state->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3 mb-2 mb-md-0">
            @php
                                                    $selectedStateId = $area['state_id'] ?? null;
                                                    $cities = $selectedStateId ? \App\Models\City::where('status', 1)->where('state_id', $selectedStateId)->get() : collect();
                                                @endphp
                                                <select class="select2 form-control aiz-selectpicker area-district" name="area_district_id[]" data-toggle="select2" data-placeholder="{{ translate('Select District') }}" data-live-search="true">
                                                    <option value="all" @if(($area['all_districts'] ?? false)) selected @endif>{{ translate('All Districts') }}</option>
                                                    @foreach($cities as $city)
                                                        <option value="{{ $city->id }}" @if((isset($area['district_id']) && $area['district_id'] == $city->id) && empty($area['all_districts'])) selected @endif>{{ $city->getTranslation('name') }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-1 d-flex align-items-center">
                                                @if($loop->first)
                                                    <button type="button" class="btn btn-icon btn-soft-success btn-sm add-area-row" title="{{ translate('Add more') }}">
                                                        <i class="las la-plus"></i>
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-icon btn-soft-danger btn-sm remove-area-row" title="{{ translate('Remove') }}">
                                                        <i class="las la-minus"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="row gutters-5 area-assign-row mb-2">
                                        <div class="col-md-4 mb-2 mb-md-0">
                                            <select class="select2 form-control aiz-selectpicker area-country" name="area_country_id[]" data-toggle="select2" data-placeholder="{{ translate('Select Country') }}" data-live-search="true">
                                                <option value="">{{ translate('Select Country') }}</option>
                                                @foreach($countries as $country)
                                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-2 mb-md-0">
                                            <select class="select2 form-control aiz-selectpicker area-state" name="area_state_id[]" data-toggle="select2" data-placeholder="{{ translate('Select State') }}" data-live-search="true">
                                                <option value="">{{ translate('Select State') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2 mb-md-0">
                                            <select class="select2 form-control aiz-selectpicker area-district" name="area_district_id[]" data-toggle="select2" data-placeholder="{{ translate('Select District') }}" data-live-search="true">
                                                <option value="all">{{ translate('All Districts') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-1 d-flex align-items-center">
                                            <button type="button" class="btn btn-icon btn-soft-success btn-sm add-area-row" title="{{ translate('Add more') }}">
                                                <i class="las la-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <template id="area-assign-template">
                                <div class="row gutters-5 area-assign-row mb-2">
                                    <div class="col-md-4 mb-2 mb-md-0">
                                        <select class="select2 form-control aiz-selectpicker area-country" name="area_country_id[]" data-toggle="select2" data-placeholder="{{ translate('Select Country') }}" data-live-search="true">
                                            <option value="">{{ translate('Select Country') }}</option>
                                            @foreach($countries as $country)
                                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-2 mb-md-0">
                                        <select class="select2 form-control aiz-selectpicker area-state" name="area_state_id[]" data-toggle="select2" data-placeholder="{{ translate('Select State') }}" data-live-search="true">
                                            <option value="">{{ translate('Select State') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2 mb-md-0">
                                        <select class="select2 form-control aiz-selectpicker area-district" name="area_district_id[]" data-toggle="select2" data-placeholder="{{ translate('Select District') }}" data-live-search="true">
                                            <option value="all">{{ translate('All Districts') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1 d-flex align-items-center">
                                        <button type="button" class="btn btn-icon btn-soft-danger btn-sm remove-area-row" title="{{ translate('Remove') }}">
                                            <i class="las la-minus"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="form-group mb-0 text-right">
                        <button type="submit" class="btn btn-sm btn-primary">{{translate('Save')}}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
    <script type="text/javascript">
        (function () {
            'use strict';

            function initAreaRow($row) {
                var $countrySelect  = $row.find('select[name="area_country_id[]"]');
                var $stateSelect    = $row.find('select[name="area_state_id[]"]');
                var $districtSelect = $row.find('select[name="area_district_id[]"]');

                $countrySelect.off('change').on('change', function () {
                    var countryId = $(this).val();

                    $stateSelect.html('<option value="">' + '{{ translate('Select State') }}' + '</option>');
                    $districtSelect.html('<option value="all">' + '{{ translate('All Districts') }}' + '</option>');

                    if (!countryId) {
                        AIZ.plugins.bootstrapSelect('refresh');
                        return;
                    }

                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        url: '{{ route('get-state') }}',
                        type: 'POST',
                        data: { country_id: countryId },
                        success: function (response) {
                            var obj = JSON.parse(response);
                            if (obj) {
                                $stateSelect.html(obj);
                                AIZ.plugins.bootstrapSelect('refresh');
                            }
                        }
                    });
                });

                $stateSelect.off('change').on('change', function () {
                    var stateId = $(this).val();

                    $districtSelect.html('<option value="all">' + '{{ translate('All Districts') }}' + '</option>');

                    if (!stateId) {
                        AIZ.plugins.bootstrapSelect('refresh');
                        return;
                    }

                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        url: '{{ route('get-city') }}',
                        type: 'POST',
                        data: { state_id: stateId },
                        success: function (response) {
                            var obj = JSON.parse(response);
                            if (obj) {
                                $districtSelect.html('<option value="all">' + '{{ translate('All Districts') }}' + '</option>' + obj);
                                AIZ.plugins.bootstrapSelect('refresh');
                            }
                        }
                    });
                });
            }

            $(document).on('click', '.add-area-row', function () {
                var template = document.getElementById('area-assign-template');
                if (!template) {
                    return;
                }

                var clone = document.importNode(template.content, true);
                $('#area-assign-container').append(clone);

                var $newRow = $('#area-assign-container .area-assign-row').last();
                AIZ.plugins.bootstrapSelect('refresh');
                initAreaRow($newRow);
            });

            $(document).on('click', '.remove-area-row', function () {
                $(this).closest('.area-assign-row').remove();
            });

            $(document).ready(function () {
                $('#area-assign-container .area-assign-row').each(function () {
                    initAreaRow($(this));
                });
            });
        })();
    </script>
@endsection
