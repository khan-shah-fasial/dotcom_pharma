@extends('backend.layouts.app')

@section('content')

<div class="row">
    <div class="col-lg-6 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Staff Information')}}</h5>
            </div>

            <form class="form-horizontal" action="{{ route('staffs.store') }}" method="POST" enctype="multipart/form-data">
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
                                <input type="hidden" name="avatar" class="selected-files">
                            </div>
                            <div class="file-preview box sm"></div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="name">{{translate('Name')}}</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="{{translate('Name')}}" id="name" name="name" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="email">{{translate('Email')}}</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="{{translate('Email')}}" id="email" name="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="mobile">{{translate('Phone')}}</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="{{translate('Phone')}}" id="mobile" name="mobile" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="display_email">{{ translate('Display Mail ID') }}</label>
                        <div class="col-sm-9">
                            <input type="email" placeholder="{{ translate('Public email shown on support page') }}" id="display_email"
                                name="display_email" value="{{ old('display_email') }}" class="form-control">
                            @error('display_email') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="designation">{{ translate('Designation') }}</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="{{ translate('Designation') }}" id="designation" name="designation" class="form-control">
                        </div>
                    </div>
                    @include('backend.staff.staffs._additional_fields')
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="password">{{translate('Password')}}</label>
                        <div class="col-sm-9">
                            <input type="password" placeholder="{{translate('Password')}}" id="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="name">{{translate('Role')}}</label>
                        <div class="col-sm-9">
                            <select name="role_id" required class="form-control aiz-selectpicker">
                                @foreach($roles as $role)
                                    <option value="{{$role->id}}">{{$role->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label">{{ translate('Area Assign') }}</label>
                        <div class="col-sm-9">
                            <div id="area-assign-container">
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
                initAreaRow($('.area-assign-row').first());
            });
        })();
    </script>
@endsection
