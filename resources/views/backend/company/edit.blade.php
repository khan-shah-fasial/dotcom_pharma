@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Edit Company') }}</h1>
            </div>
            <div class="col-md-6 text-md-right">
                <a href="{{ route('companies.show', $company) }}" class="btn btn-soft-secondary">
                    {{ translate('Back to Company') }}
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Company Information') }}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('companies.update', $company) }}" method="POST">
                @csrf
                @method('PATCH')
                @include('backend.company.partials.form')

                <div class="text-right">
                    <button type="submit" class="btn btn-primary">{{ translate('Update Company') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    @stack('company_scripts')
@endsection
