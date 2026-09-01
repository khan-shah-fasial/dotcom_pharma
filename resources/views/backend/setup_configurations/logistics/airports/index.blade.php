@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Airport Master') }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('airports.import-sample') }}" class="btn btn-soft-success mr-2">
                <i class="las la-file-excel"></i> {{ translate('Download Sample Excel') }}
            </a>
            <a href="{{ route('airports.create') }}" class="btn btn-info">
                <i class="las la-plus"></i> {{ translate('Add Airport') }}
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Bulk Excel Upload') }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('airports.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row align-items-end">
                <div class="col-md-8">
                    <label>{{ translate('Excel File') }} <span class="text-danger">*</span></label>
                    <input type="file" name="bulk_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    <small class="text-muted">
                        {{ translate('Use the sample headings exactly. Existing rows with the same Port ID, IATA, or ICAO code will be updated. Maximum file size: 10 MB.') }}
                    </small>
                    @foreach($errors->get('bulk_file') as $message)
                        <div class="text-danger small">{{ $message }}</div>
                    @endforeach
                </div>
                <div class="col-md-4 text-md-right mt-3 mt-md-0">
                    <button type="submit" class="btn btn-primary">
                        <i class="las la-upload"></i> {{ translate('Import Airports') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Airports') }}</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('airports.index') }}" class="mb-3">
            <div class="row gutters-5">
                <div class="col-md-3 mb-2">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="{{ translate('Search Port ID, name, IATA, ICAO, city or contact') }}">
                </div>
                <div class="col-md-2 mb-2">
                    <select name="country_id" class="form-control aiz-selectpicker" data-live-search="true" data-skip-country-default="1" data-placeholder="{{ translate('All Countries') }}">
                        <option value="">{{ translate('All Countries') }}</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) $countryId === (string) $country->id)>{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="status" class="form-control aiz-selectpicker">
                        <option value="">{{ translate('All Statuses') }}</option>
                        <option value="1" @selected((string) $status === '1')>{{ translate('Active') }}</option>
                        <option value="0" @selected((string) $status === '0')>{{ translate('Inactive') }}</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="city" class="form-control aiz-selectpicker" data-live-search="true">
                        <option value="">{{ translate('All Cities') }}</option>
                        @foreach($cities as $value)
                            <option value="{{ $value }}" @selected($city === (string) $value)>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <select name="terminal_type" class="form-control aiz-selectpicker" data-live-search="true">
                        <option value="">{{ translate('All Terminal Types') }}</option>
                        @foreach($terminalTypes as $value)
                            <option value="{{ $value }}" @selected($terminalType === (string) $value)>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="cargo_airport" class="form-control aiz-selectpicker">
                        <option value="">{{ translate('Cargo Airport') }}</option>
                        @foreach($facilityOptions as $value)
                            <option value="{{ $value }}" @selected($cargoAirport === $value)>{{ translate($value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="customs_airport" class="form-control aiz-selectpicker">
                        <option value="">{{ translate('Customs Airport') }}</option>
                        @foreach($facilityOptions as $value)
                            <option value="{{ $value }}" @selected($customsAirport === $value)>{{ translate($value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="cold_chain_facility" class="form-control aiz-selectpicker">
                        <option value="">{{ translate('Cold Chain') }}</option>
                        @foreach($facilityOptions as $value)
                            <option value="{{ $value }}" @selected($coldChain === $value)>{{ translate($value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="sort_by" class="form-control aiz-selectpicker">
                        @foreach([
                            'name' => 'Airport Name',
                            'port_id' => 'Port ID',
                            'iata' => 'IATA',
                            'icao' => 'ICAO',
                            'country' => 'Country',
                            'city' => 'City',
                            'terminal_type' => 'Terminal Type',
                            'status' => 'Status',
                            'created_at' => 'Created Date',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected($sortBy === $value)>{{ translate($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 mb-2">
                    <select name="sort_order" class="form-control">
                        <option value="asc" @selected($sortOrder === 'asc')>{{ translate('Asc') }}</option>
                        <option value="desc" @selected($sortOrder === 'desc')>{{ translate('Desc') }}</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <button class="btn btn-primary btn-block" type="submit">{{ translate('Filter') }}</button>
                </div>
                <div class="col-md-1 mb-2">
                    <a href="{{ route('airports.index') }}?country_id=" class="btn btn-soft-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        @include('backend.inc.sort_th', ['column' => 'port_id', 'label' => translate('Port ID'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                        @include('backend.inc.sort_th', ['column' => 'name', 'label' => translate('Airport'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                        @include('backend.inc.sort_th', ['column' => 'iata', 'label' => translate('IATA / ICAO'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                        @include('backend.inc.sort_th', ['column' => 'country', 'label' => translate('Country / City'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                        @include('backend.inc.sort_th', ['column' => 'terminal_type', 'label' => translate('Terminal Type'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                        <th>{{ translate('Cargo / Cold Chain') }}</th>
                        <th>{{ translate('Authority / Coordinator') }}</th>
                        @include('backend.inc.sort_th', ['column' => 'status', 'label' => translate('Status'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($airports as $key => $airport)
                        <tr>
                            <td>{{ $airports->firstItem() + $key }}</td>
                            <td><strong>{{ $airport->port_id ?: '-' }}</strong></td>
                            <td>
                                <strong>{{ $airport->name }}</strong>
                            </td>
                            <td>{{ $airport->iata ?: '-' }} / {{ $airport->icao ?: '-' }}</td>
                            <td>
                                {{ $airport->country }}
                                @if($airport->city)
                                    <div class="small text-muted">{{ $airport->city }}</div>
                                @endif
                            </td>
                            <td>{{ $airport->terminal_type ?: '-' }}</td>
                            <td>{{ $airport->cargo_airport ?: '-' }} / {{ $airport->cold_chain_facility ?: '-' }}</td>
                            <td>
                                @if($airport->authority_name)
                                    <strong>{{ translate('Authority') }}:</strong> {{ $airport->authority_name }}
                                    @if($airport->authority_mobile || $airport->authority_email)
                                        <div class="small text-muted">
                                            {{ collect([$airport->authority_mobile, $airport->authority_email])->filter()->implode(' | ') }}
                                        </div>
                                    @endif
                                @endif
                                @if($airport->coordinator_name)
                                    <div @class(['mt-1' => $airport->authority_name])>
                                        <strong>{{ translate('Coordinator') }}:</strong> {{ $airport->coordinator_name }}
                                    </div>
                                    @if($airport->coordinator_mobile || $airport->coordinator_email)
                                        <div class="small text-muted">
                                            {{ collect([$airport->coordinator_mobile, $airport->coordinator_email])->filter()->implode(' | ') }}
                                        </div>
                                    @endif
                                @endif
                                @if(!$airport->authority_name && !$airport->coordinator_name)
                                    -
                                @endif
                            </td>
                            <td>
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input onchange="updateAirportStatus(this)" value="{{ $airport->id }}" type="checkbox" @checked($airport->status)>
                                    <span class="slider round"></span>
                                </label>
                            </td>
                            <td class="text-right">
                                <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('airports.edit', $airport) }}" title="{{ translate('Edit') }}">
                                    <i class="las la-edit"></i>
                                </a>
                                <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('airports.destroy', $airport) }}" title="{{ translate('Delete') }}">
                                    <i class="las la-trash"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">{{ translate('No airports found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="aiz-pagination mt-3">
            {{ $airports->links() }}
        </div>
    </div>
</div>
@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection

@section('script')
<script>
    function updateAirportStatus(el) {
        $.post('{{ route('airports.update-status') }}', {
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
