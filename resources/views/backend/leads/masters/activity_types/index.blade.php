@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">{{ translate('Activity Type Master') }}</h1></div>
        <div class="col-md-6 text-md-right"><a href="{{ route('leads.index') }}" class="btn btn-primary">{{ translate('Lead List') }}</a></div>
    </div>
</div>

<div class="row">
    <div class="{{ auth()->user()->can('add_lead') ? 'col-lg-7' : 'col-lg-12' }}">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Activity Types') }}</h5></div>
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ translate('Title') }}</th>
                            <th>{{ translate('Activities') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th class="text-right">{{ translate('Options') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activityTypes as $key => $activityType)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $activityType->title }}</td>
                                <td>{{ $activityType->activities_count }}</td>
                                <td>
                                    @can('edit_lead')
                                        <label class="aiz-switch aiz-switch-success mb-0">
                                            <input onchange="update_status(this)" value="{{ $activityType->id }}" type="checkbox" @checked($activityType->status == 1)>
                                            <span class="slider round"></span>
                                        </label>
                                    @else
                                        <span class="badge badge-inline badge-{{ $activityType->status ? 'success' : 'secondary' }}">{{ $activityType->status ? translate('Active') : translate('Inactive') }}</span>
                                    @endcan
                                </td>
                                <td class="text-right">
                                    @can('edit_lead')
                                        <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('lead-activity-types.edit', $activityType->id) }}" title="{{ translate('Edit') }}">
                                            <i class="las la-edit"></i>
                                        </a>
                                    @endcan
                                    @can('delete_lead')
                                        <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('lead-activity-types.destroy', $activityType->id) }}" title="{{ translate('Delete') }}">
                                            <i class="las la-trash"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">{{ translate('No activity types found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @can('add_lead')
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h5 class="mb-0 h6">{{ translate('Add Activity Type') }}</h5></div>
                <div class="card-body">
                    <form action="{{ route('lead-activity-types.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>{{ translate('Title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                            @error('title') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Status') }}</label>
                            <select name="status" class="form-control aiz-selectpicker">
                                <option value="1">{{ translate('Active') }}</option>
                                <option value="0" @selected(old('status') === '0')>{{ translate('Inactive') }}</option>
                            </select>
                        </div>
                        <div class="text-right">
                            <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
</div>
@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection

@section('script')
<script>
    function update_status(el) {
        if ('{{ env('DEMO_MODE') }}' == 'On') {
            AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
            return;
        }
        $.post('{{ route('lead-activity-types.update_status') }}', {
            _token: '{{ csrf_token() }}',
            id: el.value,
            status: el.checked ? 1 : 0
        }, function(data) {
            AIZ.plugins.notify(data == 1 ? 'success' : 'danger', data == 1 ? '{{ translate('Status updated successfully') }}' : '{{ translate('Something went wrong') }}');
        });
    }
</script>
@endsection
