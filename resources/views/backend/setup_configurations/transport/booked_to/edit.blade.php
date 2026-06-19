@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">{{ translate('Edit Booked To') }}</h1></div>
        <div class="col-md-6 text-md-right"><a href="{{ route('booked-to.index') }}" class="btn btn-primary">{{ translate('Back') }}</a></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0 h6">{{ translate('Booked To Information') }}</h5></div>
    <div class="card-body">
        <form action="{{ route('booked-to.update', $bookedTo->id) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('Transport') }} <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <select name="transport_id" class="form-control aiz-selectpicker" data-live-search="true" required>
                        @foreach ($transports as $transport)
                            <option value="{{ $transport->id }}" @if(old('transport_id', $bookedTo->transport_id) == $transport->id) selected @endif>{{ $transport->name }}</option>
                        @endforeach
                    </select>
                    @error('transport_id') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('Location') }} <span class="text-danger">*</span></label>
                <div class="col-md-9">
                    <input type="text" name="name" class="form-control" value="{{ old('name', old('location', $bookedTo->name)) }}" required>
                    @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('Branch Name') }}</label>
                <div class="col-md-9">
                    <input type="text" name="branch_name" class="form-control" value="{{ old('branch_name', $bookedTo->branch_name) }}">
                    @error('branch_name') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('Branch Code') }}</label>
                <div class="col-md-9">
                    <input type="text" name="branch_code" class="form-control" value="{{ old('branch_code', $bookedTo->branch_code) }}">
                    @error('branch_code') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('Branch GST Number') }}</label>
                <div class="col-md-9">
                    <input type="text" name="branch_gst_number" class="form-control" value="{{ old('branch_gst_number', $bookedTo->branch_gst_number) }}">
                    @error('branch_gst_number') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('Branch Mobile Number') }}</label>
                <div class="col-md-9">
                    <input type="text" name="branch_mobile_number" class="form-control" value="{{ old('branch_mobile_number', $bookedTo->branch_mobile_number) }}">
                    @error('branch_mobile_number') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('Branch Alternate Mobile Number') }}</label>
                <div class="col-md-9">
                    <input type="text" name="branch_alternate_mobile_number" class="form-control" value="{{ old('branch_alternate_mobile_number', $bookedTo->branch_alternate_mobile_number) }}">
                    @error('branch_alternate_mobile_number') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('Contact - Incharge') }}</label>
                <div class="col-md-9">
                    <input type="text" name="contact_incharge" class="form-control" value="{{ old('contact_incharge', $bookedTo->contact_incharge) }}">
                    @error('contact_incharge') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('Branch Email ID') }}</label>
                <div class="col-md-9">
                    <input type="email" name="branch_email" class="form-control" value="{{ old('branch_email', $bookedTo->branch_email) }}">
                    @error('branch_email') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group row">
                <label class="col-md-2 col-form-label">{{ translate('Status') }}</label>
                <div class="col-md-9">
                    <select name="status" class="form-control aiz-selectpicker">
                        <option value="active" @if(old('status', $bookedTo->status) == 'active') selected @endif>{{ translate('Active') }}</option>
                        <option value="inactive" @if(old('status', $bookedTo->status) == 'inactive') selected @endif>{{ translate('Inactive') }}</option>
                    </select>
                </div>
            </div>
            <div class="text-right"><button type="submit" class="btn btn-primary">{{ translate('Update') }}</button></div>
        </form>
    </div>
</div>
@endsection
