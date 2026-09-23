@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Sea Port Master') }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('sea-ports.import-sample') }}" class="btn btn-soft-success mr-2">
                <i class="las la-file-excel"></i> {{ translate('Download Sample Excel') }}
            </a>
            <a href="{{ route('sea-ports.create') }}" class="btn btn-info">
                <i class="las la-plus"></i> {{ translate('Add Sea Port') }}
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Bulk Excel Upload') }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('sea-ports.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row align-items-end">
                <div class="col-md-8">
                    <label>{{ translate('Excel File') }} <span class="text-danger">*</span></label>
                    <input type="file" name="bulk_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    <small class="text-muted">
                        {{ translate('Use the sample headings exactly. Existing rows with the same Port ID or UN/LOCODE will be updated. Maximum file size: 10 MB.') }}
                    </small>
                    @foreach($errors->get('bulk_file') as $message)
                        <div class="text-danger small">{{ $message }}</div>
                    @endforeach
                </div>
                <div class="col-md-4 text-md-right mt-3 mt-md-0">
                    <button type="submit" class="btn btn-primary">
                        <i class="las la-upload"></i> {{ translate('Import Sea Ports') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@php
    $seaPortFiltersApplied = collect([
        $search, $status, $continent, $stateRegion, $portType, $terminalType,
        $classification, $customsPort, $exportSupported, $importSupported,
    ])->contains(fn ($value) => $value !== null && $value !== '');
@endphp
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <div class="mb-2">
            <h5 class="mb-0 h6">{{ translate('Sea Ports') }}</h5>
            @if ($seaPortFiltersApplied)
                <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
            @endif
        </div>
        <div class="d-flex flex-wrap align-items-center">
            <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal" data-target="#seaPortFilterModal">
                {{ translate('Open Filters') }}
            </button>
            <a href="{{ route('sea-ports.index') }}?country_id=" class="btn btn-danger mb-2">{{ translate('Reset') }}</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        @include('backend.inc.sort_th', ['column' => 'port_id', 'label' => translate('Port ID'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                        @include('backend.inc.sort_th', ['column' => 'name', 'label' => translate('Port'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                        @include('backend.inc.sort_th', ['column' => 'un_locode', 'label' => translate('UN/LOCODE'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                        @include('backend.inc.sort_th', ['column' => 'country', 'label' => translate('Country / State'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                        @include('backend.inc.sort_th', ['column' => 'terminal_type', 'label' => translate('Terminal / Class'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                        <th>{{ translate('Authority / Coordinator') }}</th>
                        @include('backend.inc.sort_th', ['column' => 'status', 'label' => translate('Status'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ports as $key => $port)
                        <tr>
                            <td>{{ $ports->firstItem() + $key }}</td>
                            <td><strong>{{ $port->port_id ?: '-' }}</strong></td>
                            <td>
                                <strong>{{ $port->name }}</strong>
                            </td>
                            <td>{{ $port->un_locode ?: '-' }}</td>
                            <td>
                                {{ $port->country }}
                                @if($port->state_region)
                                    <div class="small text-muted">{{ $port->state_region }}</div>
                                @endif
                            </td>
                            <td>
                                {{ $port->terminal_type ?: '-' }}
                                @if($port->classification)
                                    <div class="small text-muted">{{ $port->classification }}</div>
                                @endif
                            </td>
                            <td>
                                @if($port->authority_name)
                                    <strong>{{ translate('Authority') }}:</strong> {{ $port->authority_name }}
                                    @if($port->authority_mobile || $port->authority_email)
                                        <div class="small text-muted">
                                            {{ collect([$port->authority_mobile, $port->authority_email])->filter()->implode(' | ') }}
                                        </div>
                                    @endif
                                @endif
                                @if($port->coordinator_name)
                                    <div @class(['mt-1' => $port->authority_name])>
                                        <strong>{{ translate('Coordinator') }}:</strong> {{ $port->coordinator_name }}
                                    </div>
                                    @if($port->coordinator_mobile || $port->coordinator_email)
                                        <div class="small text-muted">
                                            {{ collect([$port->coordinator_mobile, $port->coordinator_email])->filter()->implode(' | ') }}
                                        </div>
                                    @endif
                                @endif
                                @if(!$port->authority_name && !$port->coordinator_name)
                                    -
                                @endif
                            </td>
                            <td>
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input onchange="updateSeaPortStatus(this)" value="{{ $port->id }}" type="checkbox" @checked($port->status)>
                                    <span class="slider round"></span>
                                </label>
                            </td>
                            <td class="text-right">
                                <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('sea-ports.edit', $port) }}" title="{{ translate('Edit') }}">
                                    <i class="las la-edit"></i>
                                </a>
                                <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('sea-ports.destroy', $port) }}" title="{{ translate('Delete') }}">
                                    <i class="las la-trash"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">{{ translate('No sea ports found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="aiz-pagination mt-3">
            {{ $ports->links() }}
        </div>
    </div>
</div>
@endsection

@section('modal')
    @include('modals.delete_modal')

    <div class="modal fade" id="seaPortFilterModal" tabindex="-1" role="dialog" aria-labelledby="seaPortFilterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <form method="GET" action="{{ route('sea-ports.index') }}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="seaPortFilterModalLabel">{{ translate('Filter Sea Ports') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row gutters-5">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ translate('Search') }}</label>
                                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="{{ translate('Search Port ID, name, UN/LOCODE, state or contact') }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ translate('Country') }}</label>
                                <select name="country_id" class="form-control aiz-selectpicker" data-live-search="true" data-container="body" data-skip-country-default="1" data-placeholder="{{ translate('All Countries') }}">
                                    <option value="">{{ translate('All Countries') }}</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country->id }}" @selected((string) $countryId === (string) $country->id)>{{ $country->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ translate('Status') }}</label>
                                <select name="status" class="form-control aiz-selectpicker" data-container="body">
                                    <option value="">{{ translate('All Statuses') }}</option>
                                    <option value="1" @selected((string) $status === '1')>{{ translate('Active') }}</option>
                                    <option value="0" @selected((string) $status === '0')>{{ translate('Inactive') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ translate('Continent') }}</label>
                                <select name="continent" class="form-control aiz-selectpicker" data-live-search="true" data-container="body">
                                    <option value="">{{ translate('All Continents') }}</option>
                                    @foreach($continents as $value)
                                        <option value="{{ $value }}" @selected($continent === (string) $value)>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ translate('State / Region') }}</label>
                                <select name="state_region" class="form-control aiz-selectpicker" data-live-search="true" data-container="body">
                                    <option value="">{{ translate('All States / Regions') }}</option>
                                    @foreach($stateRegions as $value)
                                        <option value="{{ $value }}" @selected($stateRegion === (string) $value)>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ translate('Port Type') }}</label>
                                <select name="port_type" class="form-control aiz-selectpicker" data-live-search="true" data-container="body">
                                    <option value="">{{ translate('All Port Types') }}</option>
                                    @foreach($portTypes as $value)
                                        <option value="{{ $value }}" @selected($portType === (string) $value)>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ translate('Terminal Type') }}</label>
                                <select name="terminal_type" class="form-control aiz-selectpicker" data-live-search="true" data-container="body">
                                    <option value="">{{ translate('All Terminal Types') }}</option>
                                    @foreach($terminalTypes as $value)
                                        <option value="{{ $value }}" @selected($terminalType === (string) $value)>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ translate('Classification') }}</label>
                                <select name="classification" class="form-control aiz-selectpicker" data-live-search="true" data-container="body">
                                    <option value="">{{ translate('All Classifications') }}</option>
                                    @foreach($classifications as $value)
                                        <option value="{{ $value }}" @selected($classification === (string) $value)>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ translate('Customs facility') }}</label>
                                <select name="customs_port" class="form-control aiz-selectpicker" data-container="body">
                                    <option value="">{{ translate('Customs facility') }}</option>
                                    @foreach($supportOptions as $value)
                                        <option value="{{ $value }}" @selected($customsPort === $value)>{{ translate($value) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ translate('Handles export cargo') }}</label>
                                <select name="export_supported" class="form-control aiz-selectpicker" data-container="body">
                                    <option value="">{{ translate('Handles export cargo') }}</option>
                                    @foreach($supportOptions as $value)
                                        <option value="{{ $value }}" @selected($exportSupported === $value)>{{ translate($value) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ translate('Handles import cargo') }}</label>
                                <select name="import_supported" class="form-control aiz-selectpicker" data-container="body">
                                    <option value="">{{ translate('Handles import cargo') }}</option>
                                    @foreach($supportOptions as $value)
                                        <option value="{{ $value }}" @selected($importSupported === $value)>{{ translate($value) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ translate('Sort By') }}</label>
                                <select name="sort_by" class="form-control aiz-selectpicker" data-container="body">
                                    @foreach([
                                        'name' => 'Port Name',
                                        'port_id' => 'Port ID',
                                        'un_locode' => 'UN/LOCODE',
                                        'country' => 'Country',
                                        'state_region' => 'State / Region',
                                        'continent' => 'Continent',
                                        'status' => 'Status',
                                        'created_at' => 'Created Date',
                                    ] as $value => $label)
                                        <option value="{{ $value }}" @selected($sortBy === $value)>{{ translate($label) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-0">
                                <label class="form-label">{{ translate('Order') }}</label>
                                <select name="sort_order" class="form-control">
                                    <option value="asc" @selected($sortOrder === 'asc')>{{ translate('Asc') }}</option>
                                    <option value="desc" @selected($sortOrder === 'desc')>{{ translate('Desc') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('sea-ports.index') }}?country_id=" class="btn btn-light">{{ translate('Reset') }}</a>
                        <button class="btn btn-primary" type="submit">{{ translate('Apply Filters') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
<script>
    $('#seaPortFilterModal').on('shown.bs.modal', function () {
        if (window.AIZ && AIZ.plugins && AIZ.plugins.bootstrapSelect) {
            AIZ.plugins.bootstrapSelect('refresh');
        }
    });

    function updateSeaPortStatus(el) {
        $.post('{{ route('sea-ports.update-status') }}', {
            _token: '{{ csrf_token() }}',
            id: el.value,
            status: el.checked ? 1 : 0
        }, function (data) {
            if (data == 1) {
                AIZ.plugins.notify('success', '{{ translate('Status updated successfully') }}');
            } else {
                el.checked = !el.checked;
                AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
            }
        }).fail(function () {
            el.checked = !el.checked;
            AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
        });
    }
</script>
@endsection
