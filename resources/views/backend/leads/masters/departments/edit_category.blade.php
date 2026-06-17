@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">{{ translate('Edit Department Category') }}</h1></div>
        <div class="col-md-6 text-md-right"><a href="{{ route('lead-departments.index') }}" class="btn btn-primary">{{ translate('Back') }}</a></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Department Category Information') }}</h5></div>
    <div class="card-body">
        <form action="{{ route('lead-departments.categories.update', $category->id) }}" method="POST">
            @csrf
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('Name') }} <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <input type="text" name="name" class="form-control" value="{{ old('name', $category->name) }}" required>
                    @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('Status') }}</label>
                <div class="col-md-9">
                    <select name="status" class="form-control aiz-selectpicker">
                        <option value="1" @selected((string) old('status', $category->status) === '1')>{{ translate('Active') }}</option>
                        <option value="0" @selected((string) old('status', $category->status) === '0')>{{ translate('Inactive') }}</option>
                    </select>
                </div>
            </div>
            <div class="text-right">
                <button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
