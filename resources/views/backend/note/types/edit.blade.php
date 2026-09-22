@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <h5 class="mb-0 h6">{{ translate('Edit Note Type') }}</h5>
    </div>
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{ translate('Note Type Information') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('note_types.update', $type->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('backend.note.types._form', ['type' => $type])
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
