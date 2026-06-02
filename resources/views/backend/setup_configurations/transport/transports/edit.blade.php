@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">{{ translate('Edit Transport') }}</h1></div>
        <div class="col-md-6 text-md-right"><a href="{{ route('transports.index') }}" class="btn btn-primary">{{ translate('Back') }}</a></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Transport Information') }}</h5></div>
    <div class="card-body">
        <form action="{{ route('transports.update', $transport->id) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('Name') }} <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <input type="text" name="name" class="form-control" value="{{ old('name', $transport->name) }}" required>
                    @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('URL') }}</label>
                <div class="col-md-9">
                    <input type="url" name="url" class="form-control" value="{{ old('url', $transport->url) }}" placeholder="https://example.com">
                    @error('url') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('Status') }}</label>
                <div class="col-md-9">
                    <select name="status" class="form-control aiz-selectpicker">
                        <option value="active" @if(old('status', $transport->status) == 'active') selected @endif>{{ translate('Active') }}</option>
                        <option value="inactive" @if(old('status', $transport->status) == 'inactive') selected @endif>{{ translate('Inactive') }}</option>
                    </select>
                </div>
            </div>
            <div class="text-right"><button type="submit" class="btn btn-primary">{{ translate('Update') }}</button></div>
        </form>
    </div>
</div>
@endsection
