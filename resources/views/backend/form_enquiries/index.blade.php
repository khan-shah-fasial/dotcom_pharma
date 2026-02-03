@extends('backend.layouts.app')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Enquiry / Suggestion Forms') }}</h5>
        </div>
        <form class="px-3 pt-3 pb-0" method="GET">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">{{ translate('Type') }}</label>
                    <select name="type" class="form-control aiz-selectpicker" data-live-search="true">
                        <option value="">{{ translate('All') }}</option>
                        <option value="enquiry" {{ ($filters['type'] ?? '') == 'enquiry' ? 'selected' : '' }}>{{ translate('Enquiry') }}</option>
                        <option value="suggestion" {{ ($filters['type'] ?? '') == 'suggestion' ? 'selected' : '' }}>{{ translate('Suggestion') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ translate('Domestic Type') }}</label>
                    <select name="domestic_type" class="form-control aiz-selectpicker" data-live-search="true">
                        <option value="">{{ translate('All') }}</option>
                        <option value="govt_supply" {{ ($filters['domestic_type'] ?? '') == 'govt_supply' ? 'selected' : '' }}>{{ translate('Govt. Supply') }}</option>
                        <option value="exports" {{ ($filters['domestic_type'] ?? '') == 'exports' ? 'selected' : '' }}>{{ translate('Exports') }}</option>
                        <option value="third_party" {{ ($filters['domestic_type'] ?? '') == 'third_party' ? 'selected' : '' }}>{{ translate('Third Party') }}</option>
                        <option value="loan_licence" {{ ($filters['domestic_type'] ?? '') == 'loan_licence' ? 'selected' : '' }}>{{ translate('Loan Licence') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ translate('Search') }}</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ translate('Form code / product / company') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary mr-2">{{ translate('Filter') }}</button>
                    <a href="{{ route('form_enquiries.index') }}" class="btn btn-soft-secondary">{{ translate('Reset') }}</a>
                </div>
            </div>
        </form>

        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ translate('Form No') }}</th>
                        <th>{{ translate('Type') }}</th>
                        <th>{{ translate('Domestic') }}</th>
                        <th>{{ translate('Product') }}</th>
                        <th>{{ translate('Company') }}</th>
                        <th>{{ translate('Date') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($enquiries as $key => $item)
                        <tr>
                            <td>{{ $enquiries->firstItem() + $key }}</td>
                            <td class="fw-700">{{ $item->form_code }}</td>
                            <td>{{ ucfirst($item->type) }}</td>
                            <td>{{ str_replace('_',' ', ucfirst($item->domestic_type)) }}</td>
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->company_name }}</td>
                            <td>{{ $item->created_at->format('d-m-Y') }}</td>
                            <td class="text-right">
                                <a href="{{ route('form_enquiries.show', $item->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm" title="{{ translate('View') }}">
                                    <i class="las la-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="aiz-pagination">
                {{ $enquiries->appends(request()->input())->links() }}
            </div>
        </div>
    </div>
@endsection
