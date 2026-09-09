@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">{{ translate('Edit Master Item') }}</h1></div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('contact-classifications.index') }}" class="btn btn-primary">{{ translate('Back') }}</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Master Item Information') }}</h5></div>
    <div class="card-body">
        <form action="{{ route('contact-classifications.update', $classification->id) }}" method="POST">
            @csrf
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('Kind') }}</label>
                <div class="col-md-9">
                    <input type="hidden" name="kind" value="{{ $classification->kind }}">
                    <input type="text" class="form-control" value="{{ translate($kinds[$classification->kind] ?? $classification->kind) }}" readonly>
                </div>
            </div>
            @if($parentKind)
                <div class="form-group row">
                    <label class="col-md-2 col-form-label">{{ translate('Parent') }} <span class="text-danger">*</span></label>
                    <div class="col-md-9">
                        <select name="parent_id" class="form-control aiz-selectpicker" data-live-search="true" required>
                            <option value="">{{ translate('Select Parent') }}</option>
                            @foreach ($parents as $parent)
                                <option value="{{ $parent->id }}" @selected((string) old('parent_id', $classification->parent_id) === (string) $parent->id)>
                                    {{ $parent->name }}
                                    @if((int) $parent->status === 0) ({{ translate('Inactive') }}) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('parent_id') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>
            @endif
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('Name') }} <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <input type="text" name="name" class="form-control" value="{{ old('name', $classification->name) }}" required>
                    @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('Status') }}</label>
                <div class="col-md-9">
                    <select name="status" class="form-control aiz-selectpicker">
                        <option value="1" @selected((string) old('status', $classification->status) === '1')>{{ translate('Active') }}</option>
                        <option value="0" @selected((string) old('status', $classification->status) === '0')>{{ translate('Inactive') }}</option>
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
