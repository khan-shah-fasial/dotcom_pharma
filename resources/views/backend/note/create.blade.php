@extends('backend.layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{ translate('Note Information') }}</h5>
                </div>
                <div class="card-body">
                    @if ($types->isEmpty())
                        <div class="alert alert-warning">
                            {{ translate('No active note types found. Add a type first.') }}
                            <a href="{{ route('note_types.create') }}" class="alert-link">{{ translate('Note Type Master') }}</a>
                        </div>
                    @endif
                    <form class="form-horizontal" action="{{ route('note.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">{{ translate('Type') }} *</label>
                            <div class="col-md-9">
                                <select name="note_type" class="form-control aiz-selectpicker mb-2 mb-md-0" required
                                    data-live-search="true" @disabled($types->isEmpty())>
                                    <option value="">{{ translate('Select Type') }}</option>
                                    @foreach ($types as $type)
                                        <option value="{{ $type->slug }}" @selected(old('note_type') === $type->slug)>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('note_type') <div class="text-danger small">{{ $message }}</div> @enderror
                                <small class="text-muted d-block mt-1">
                                    <a href="{{ route('note_types.index') }}">{{ translate('Manage types') }}</a>
                                </small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-md-3 col-from-label">{{ translate('Description') }} *</label>
                            <div class="col-md-9">
                                <textarea name="description" rows="8" class="form-control" required>{{ old('description') }}</textarea>
                                @error('description') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-primary" @disabled($types->isEmpty())>{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
