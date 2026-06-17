@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">{{ translate('Department Master') }}</h1></div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('leads.index') }}" class="btn btn-primary">{{ translate('Lead List') }}</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="{{ auth()->user()->can('add_lead') ? 'col-lg-7' : 'col-lg-12' }}">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Department Categories') }}</h5>
            </div>
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ translate('Name') }}</th>
                            <th>{{ translate('Departments') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th class="text-right">{{ translate('Options') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $key => $category)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $category->name }}</td>
                                <td>{{ $category->departments_count }}</td>
                                <td>
                                    @can('edit_lead')
                                        <label class="aiz-switch aiz-switch-success mb-0">
                                            <input onchange="update_category_status(this)" value="{{ $category->id }}" type="checkbox" @checked($category->status == 1)>
                                            <span class="slider round"></span>
                                        </label>
                                    @else
                                        <span class="badge badge-inline badge-{{ $category->status ? 'success' : 'secondary' }}">{{ $category->status ? translate('Active') : translate('Inactive') }}</span>
                                    @endcan
                                </td>
                                <td class="text-right">
                                    @can('edit_lead')
                                        <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('lead-departments.categories.edit', $category->id) }}" title="{{ translate('Edit') }}">
                                            <i class="las la-edit"></i>
                                        </a>
                                    @endcan
                                    @can('delete_lead')
                                        <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('lead-departments.categories.destroy', $category->id) }}" title="{{ translate('Delete') }}">
                                            <i class="las la-trash"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">{{ translate('No department categories found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @can('add_lead')
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{ translate('Add Department Category') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('lead-departments.categories.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>{{ translate('Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
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

<div class="row">
    <div class="{{ auth()->user()->can('add_lead') ? 'col-lg-7' : 'col-lg-12' }}">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Departments') }}</h5>
            </div>
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ translate('Department') }}</th>
                            <th>{{ translate('Category') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th class="text-right">{{ translate('Options') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($departments as $key => $department)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $department->name }}</td>
                                <td>{{ optional($department->category)->name ?? '-' }}</td>
                                <td>
                                    @can('edit_lead')
                                        <label class="aiz-switch aiz-switch-success mb-0">
                                            <input onchange="update_department_status(this)" value="{{ $department->id }}" type="checkbox" @checked($department->status == 1)>
                                            <span class="slider round"></span>
                                        </label>
                                    @else
                                        <span class="badge badge-inline badge-{{ $department->status ? 'success' : 'secondary' }}">{{ $department->status ? translate('Active') : translate('Inactive') }}</span>
                                    @endcan
                                </td>
                                <td class="text-right">
                                    @can('edit_lead')
                                        <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('lead-departments.departments.edit', $department->id) }}" title="{{ translate('Edit') }}">
                                            <i class="las la-edit"></i>
                                        </a>
                                    @endcan
                                    @can('delete_lead')
                                        <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('lead-departments.departments.destroy', $department->id) }}" title="{{ translate('Delete') }}">
                                            <i class="las la-trash"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">{{ translate('No departments found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @can('add_lead')
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{ translate('Add Department') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('lead-departments.departments.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>{{ translate('Category') }} <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-control aiz-selectpicker" data-live-search="true" required>
                                <option value="">{{ translate('Select Category') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
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
    function update_category_status(el) {
        if ('{{ env('DEMO_MODE') }}' == 'On') {
            AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
            return;
        }
        $.post('{{ route('lead-departments.categories.update_status') }}', {
            _token: '{{ csrf_token() }}',
            id: el.value,
            status: el.checked ? 1 : 0
        }, function(data) {
            AIZ.plugins.notify(data == 1 ? 'success' : 'danger', data == 1 ? '{{ translate('Status updated successfully') }}' : '{{ translate('Something went wrong') }}');
        });
    }

    function update_department_status(el) {
        if ('{{ env('DEMO_MODE') }}' == 'On') {
            AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
            return;
        }
        $.post('{{ route('lead-departments.departments.update_status') }}', {
            _token: '{{ csrf_token() }}',
            id: el.value,
            status: el.checked ? 1 : 0
        }, function(data) {
            AIZ.plugins.notify(data == 1 ? 'success' : 'danger', data == 1 ? '{{ translate('Status updated successfully') }}' : '{{ translate('Something went wrong') }}');
        });
    }
</script>
@endsection
