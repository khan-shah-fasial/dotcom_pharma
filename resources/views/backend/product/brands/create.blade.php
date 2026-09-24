@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <h5 class="mb-0 h6">{{ translate('Add New Brand') }}</h5>
</div>

<div class="col-lg-8 mx-auto">
    <div class="card">
        <div class="card-body">
            <form action="{{ route('brands.store') }}" method="POST">
                @csrf
                <div class="form-group mb-3">
                    <label for="company_id">{{ translate('Company Name') }}</label>
                    <select id="company_id" name="company_id" class="form-control aiz-selectpicker"
                        data-live-search="true" data-placeholder="{{ translate('Select Company') }}">
                        <option value="">{{ translate('Select Company') }}</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>
                                {{ $company->company_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('company_id')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group mb-3">
                    <label for="name">{{ translate('Name') }}</label>
                    <input type="text" placeholder="{{ translate('Name') }}" id="name" name="name" value="{{ old('name') }}" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label for="logo">{{ translate('Logo') }} <small>({{ translate('120x80') }})</small></label>
                    <div class="input-group" data-toggle="aizuploader" data-type="image">
                        <div class="input-group-prepend">
                            <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                        </div>
                        <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                        <input type="hidden" name="logo" value="{{ old('logo') }}" class="selected-files">
                    </div>
                    <div class="file-preview box sm"></div>
                    <small class="text-muted">{{ translate('Minimum dimensions required: 126px width X 100px height.') }}</small>
                </div>
                <div class="form-group mb-3">
                    <label for="meta_title">{{ translate('Meta Title') }}</label>
                    <input type="text" class="form-control" id="meta_title" name="meta_title" value="{{ old('meta_title') }}" placeholder="{{ translate('Meta Title') }}">
                </div>
                <div class="form-group mb-3">
                    <label for="meta_description">{{ translate('Meta Description') }}</label>
                    <textarea name="meta_description" id="meta_description" rows="5" class="form-control">{{ old('meta_description') }}</textarea>
                </div>
                <div class="form-group mb-3 text-right">
                    <a href="{{ route('brands.index') }}" class="btn btn-soft-secondary">{{ translate('Back') }}</a>
                    <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
