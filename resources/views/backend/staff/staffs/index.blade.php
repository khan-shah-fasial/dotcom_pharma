@extends('backend.layouts.app')

@section('content')

@php
    $filters = $filters ?? [];
    $sortBy = $sortBy ?? '';
    $sortDir = $sortDir ?? 'asc';
    $filtersApplied = collect($filters)->contains(function ($value) {
        return $value !== null && $value !== '';
    });
@endphp

<div class="aiz-titlebar text-left mt-2 mb-3">
	<div class="row align-items-center">
		<div class="col-md-6">
			<h1 class="h3">{{translate('All Staffs')}}</h1>
		</div>
        @can('add_staff')
            <div class="col-md-6 text-md-right">
                <a href="{{ route('staffs.create') }}" class="btn btn-circle btn-info">
                    <span>{{translate('Add New Staffs')}}</span>
                </a>
            </div>
        @endcan
	</div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <div class="mb-2">
            <h5 class="mb-0 h6">{{translate('Staffs')}}</h5>
            @if ($filtersApplied)
                <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
            @endif
        </div>
        <div class="d-flex flex-wrap align-items-center">
            <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal" data-target="#staffFilterModal">
                {{ translate('Open Filters') }}
            </button>
            <a href="{{ route('staffs.index') }}" class="btn btn-danger mb-2">{{ translate('Reset') }}</a>
        </div>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th data-breakpoints="lg" width="10%">#</th>
                    @include('backend.inc.sortable_th', ['column' => 'photo', 'label' => translate('Photo'), 'routeName' => 'staffs.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'name', 'label' => translate('Name'), 'routeName' => 'staffs.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                    @include('backend.inc.sortable_th', ['column' => 'email', 'label' => translate('Email'), 'routeName' => 'staffs.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'phone', 'label' => translate('Phone'), 'routeName' => 'staffs.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'role', 'label' => translate('Role'), 'routeName' => 'staffs.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'designation', 'label' => translate('Designation'), 'routeName' => 'staffs.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'status', 'label' => translate('Status'), 'routeName' => 'staffs.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'area', 'label' => translate('Area Assign'), 'routeName' => 'staffs.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    <th width="10%" class="text-right">{{translate('Options')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($staffs as $key => $staff)
                    @if($staff->user != null)
                        <tr>
                            <td>{{ ($key+1) + ($staffs->currentPage() - 1)*$staffs->perPage() }}</td>
                            <td>
                                <span class="avatar avatar-sm">
                                    <img class="rounded-circle"
                                         @if($staff->user->avatar_original)
                                             src="{{ uploaded_asset($staff->user->avatar_original) }}"
                                         @else
                                             src="{{ static_asset('assets/img/avatar-place.png') }}"
                                         @endif
                                         onerror="this.onerror=null;this.src='{{ static_asset('assets/img/avatar-place.png') }}';">
                                </span>
                            </td>
                            <td>{{$staff->user->name}}</td>
                            <td>{{$staff->user->email}}</td>
                            <td>{{$staff->user->phone}}</td>
                            <td>
								@if ($staff->role != null)
									{{ $staff->role->getTranslation('name') }}
								@endif
							</td>
                            <td>
                                {{ $staff->designation ?? '-' }}
                            </td>
                            <td>
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input type="checkbox"
                                        @can('edit_staff') onchange="change_staff_status(this)" @endcan
                                        value="{{ $staff->id }}"
                                        @checked($staff->status)
                                        @cannot('edit_staff') disabled @endcan
                                    >
                                    <span></span>
                                </label>
                            </td>
                            <td>
                                @php
                                    $areas = $staff->area_assignments ? json_decode($staff->area_assignments, true) : [];
                                @endphp
                                @if(!empty($areas))
                                    @php
                                        $areaLabels = [];
                                        foreach($areas as $area){
                                            $countryName = isset($area['country_id']) ? (getParticularData('countries', 'name', (int) $area['country_id']) ?? '') : '';
                                            $stateName = isset($area['state_id']) && $area['state_id'] ? (getParticularData('states', 'name', (int) $area['state_id']) ?? '') : '';
                                            $districtLabel = '';
                                            if(isset($area['all_districts']) && $area['all_districts']){
                                                $districtLabel = translate('All Districts');
                                            } elseif(isset($area['district_id']) && $area['district_id']) {
                                                $districtLabel = getParticularData('cities', 'name', (int) $area['district_id']) ?? '';
                                            }

                                            $labelParts = array_filter([$countryName, $stateName, $districtLabel]);
                                            if(!empty($labelParts)){
                                                $areaLabels[] = implode(' - ', $labelParts);
                                            }
                                        }
                                    @endphp
                                    @php
                                        $areaSummary = implode(' | ', $areaLabels);
                                    @endphp
                                    <span title="{{ $areaSummary }}">
                                        {{ \Illuminate\Support\Str::limit($areaSummary, 60) }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-right">
                                @can('edit_staff')
                                    <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{route('staffs.edit', encrypt($staff->id))}}" title="{{ translate('Edit') }}">
                                        <i class="las la-edit"></i>
                                    </a>
                                @endcan
                                @can('delete_staff')
                                    <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{route('staffs.destroy', $staff->id)}}" title="{{ translate('Delete') }}">
                                        <i class="las la-trash"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $staffs->appends(request()->input())->links() }}
        </div>
    </div>
</div>

@endsection

@section('modal')
    @include('modals.delete_modal')
    <form action="{{ route('staffs.index') }}" method="GET">
        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
        <div class="modal fade" id="staffFilterModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Filter Staffs') }}</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row gutters-5">
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Name') }}</label>
                                <input type="text" class="form-control" name="name" value="{{ $filters['name'] ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Email') }}</label>
                                <input type="text" class="form-control" name="email" value="{{ $filters['email'] ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Phone') }}</label>
                                <input type="text" class="form-control" name="phone" value="{{ $filters['phone'] ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Role') }}</label>
                                <input type="text" class="form-control" name="role" value="{{ $filters['role'] ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Designation') }}</label>
                                <input type="text" class="form-control" name="designation" value="{{ $filters['designation'] ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Area Assign') }}</label>
                                <input type="text" class="form-control" name="area" value="{{ $filters['area'] ?? '' }}" placeholder="{{ translate('Country, state, or district') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Photo') }}</label>
                                <select name="photo" class="form-control">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="1" @selected(($filters['photo'] ?? '') === '1')>{{ translate('Has photo') }}</option>
                                    <option value="0" @selected(($filters['photo'] ?? '') === '0')>{{ translate('No photo') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Status') }}</label>
                                <select name="status" class="form-control">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="1" @selected(($filters['status'] ?? '') === '1')>{{ translate('Active') }}</option>
                                    <option value="0" @selected(($filters['status'] ?? '') === '0')>{{ translate('Inactive') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('staffs.index') }}" class="btn btn-danger">{{ translate('Reset') }}</a>
                        <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
    <script type="text/javascript">
        function change_staff_status(el) {
            if ('{{ env('DEMO_MODE') }}' === 'On') {
                el.checked = !el.checked;
                AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
                return;
            }

            var status = el.checked ? 1 : 0;

            $.post('{{ route('staffs.update_status') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                status: status
            }, function (data) {
                if (data == 1) {
                    AIZ.plugins.notify('success', '{{ translate('Change staff status successfully') }}');
                } else {
                    el.checked = !el.checked;
                    AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                }
            }).fail(function () {
                el.checked = !el.checked;
                AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
            });
        }
    </script>
@endsection
