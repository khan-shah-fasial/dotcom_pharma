@extends('backend.layouts.app')

@section('content')

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
    <div class="card-header">
        <h5 class="mb-0 h6">{{translate('Staffs')}}</h5>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th data-breakpoints="lg" width="10%">#</th>
                    <th data-breakpoints="lg">{{translate('Photo')}}</th>
                    <th>{{translate('Name')}}</th>
                    <th data-breakpoints="lg">{{translate('Email')}}</th>
                    <th data-breakpoints="lg">{{translate('Phone')}}</th>
                    <th data-breakpoints="lg">{{translate('Role')}}</th>
                    <th data-breakpoints="lg">{{translate('Designation')}}</th>
                    <th data-breakpoints="lg">{{translate('Status')}}</th>
                    <th data-breakpoints="lg">{{translate('Area Assign')}}</th>
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
