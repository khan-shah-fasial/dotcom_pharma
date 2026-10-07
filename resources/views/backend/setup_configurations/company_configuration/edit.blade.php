@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Company Configuration') }}</h1>
            </div>
        </div>
    </div>

    @if (empty($tableReady))
        <div class="alert alert-warning">
            {{ translate('Company Configuration is not ready yet. Run the database SQL first, then reload this page.') }}
        </div>
    @endif

    @if (!empty($filesReady) && $company->exists)
        <div class="mb-3 text-right">
            @include('backend.company.partials.files_button', [
                'viewerTitle' => \App\Models\CompanyConfiguration::BILLING_NAME,
                'viewerCertificates' => $company->certificates,
                'viewerDocuments' => $company->documents,
                'viewerKey' => 'billing-company-files',
            ])
        </div>
        @include('backend.company.partials.files_viewer_assets')
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Billing Company') }}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('company_configuration.update') }}" method="POST">
                @csrf
                @include('backend.company.partials.form')

                @if (!empty($tableReady))
                    <div class="form-group row">
                        <label class="col-md-3 col-form-label" for="security_password">
                            {{ translate('Security Password') }} <span class="text-danger">*</span>
                        </label>
                        <div class="col-md-9">
                            <input type="password" id="security_password" name="security_password"
                                class="form-control @error('security_password') is-invalid @enderror"
                                autocomplete="new-password" required>
                            <small class="text-muted">{{ translate('Required on every save. The company name stays locked.') }}</small>
                            @error('security_password') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">
                            {{ $company->exists ? translate('Update Billing Company') : translate('Save Billing Company') }}
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>
@endsection

@section('script')
    @stack('company_scripts')
@endsection
