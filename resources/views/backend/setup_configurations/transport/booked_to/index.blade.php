@extends('backend.layouts.app')

@section('content')
@php
    $filters = $filters ?? [];
    $sortBy = $sortBy ?? '';
    $sortDir = $sortDir ?? 'asc';
    $filtersApplied = filled($sort_search) || collect($filters)->contains(function ($value) {
        return $value !== null && $value !== '';
    });
@endphp
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">{{ translate('Booked To') }}</h1></div>
        <div class="col-md-6 text-md-right"><a href="{{ route('booked-to.create') }}" class="btn btn-circle btn-info">{{ translate('Add New Booked To') }}</a></div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <div class="mb-2">
            <h5 class="mb-md-0 h6">{{ translate('Booked To List') }}</h5>
            @if ($filtersApplied)
                <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
            @endif
        </div>
        <div>
            <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal" data-target="#bookedToFilterModal">{{ translate('Open Filters') }}</button>
            <a href="{{ route('booked-to.index') }}" class="btn btn-danger mb-2">{{ translate('Reset') }}</a>
        </div>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    @include('backend.inc.sortable_th', ['column' => 'transport', 'label' => translate('Transport'), 'routeName' => 'booked-to.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                    @include('backend.inc.sortable_th', ['column' => 'location', 'label' => translate('Location'), 'routeName' => 'booked-to.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                    @include('backend.inc.sortable_th', ['column' => 'branch_name', 'label' => translate('Branch Name'), 'routeName' => 'booked-to.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'branch_address', 'label' => translate('Branch Address'), 'routeName' => 'booked-to.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'branch_code', 'label' => translate('Branch Code'), 'routeName' => 'booked-to.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'branch_gst_number', 'label' => translate('Branch GST Number'), 'routeName' => 'booked-to.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'branch_mobile_number', 'label' => translate('Branch Mobile Number'), 'routeName' => 'booked-to.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                    @include('backend.inc.sortable_th', ['column' => 'branch_alternate_mobile_number', 'label' => translate('Branch Alternate Mobile Number'), 'routeName' => 'booked-to.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'contact_incharge', 'label' => translate('Contact - Incharge'), 'routeName' => 'booked-to.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'branch_email', 'label' => translate('Branch Email ID'), 'routeName' => 'booked-to.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                    @include('backend.inc.sortable_th', ['column' => 'scanner', 'label' => translate('Scanner'), 'routeName' => 'booked-to.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'created_by', 'label' => translate('Created By'), 'routeName' => 'booked-to.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'status', 'label' => translate('Status'), 'routeName' => 'booked-to.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                    <th class="text-right">{{ translate('Options') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($booked_to as $key => $item)
                    @php
                        $branchMobileHref = $item->branch_mobile_number ? preg_replace('/\D+/', '', $item->branch_mobile_number) : null;
                        $alternateMobileHref = $item->branch_alternate_mobile_number ? preg_replace('/\D+/', '', $item->branch_alternate_mobile_number) : null;
                    @endphp
                    <tr>
                        <td>{{ $booked_to->firstItem() + $key }}</td>
                        <td>{{ optional($item->transport)->name ?? '-' }}</td>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->branch_name ?? '-' }}</td>
                        <td>{{ $item->branch_address ?? '-' }}</td>
                        <td>{{ $item->branch_code ?? '-' }}</td>
                        <td>{{ $item->branch_gst_number ?? '-' }}</td>
                        <td>
                            @if($item->branch_mobile_number && $branchMobileHref)
                                <a href="https://wa.me/{{ $branchMobileHref }}" target="_blank" rel="noopener">{{ $item->branch_mobile_number }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($item->branch_alternate_mobile_number && $alternateMobileHref)
                                <a href="https://wa.me/{{ $alternateMobileHref }}" target="_blank" rel="noopener">{{ $item->branch_alternate_mobile_number }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $item->contact_incharge ?? '-' }}</td>
                        <td>
                            @if($item->branch_email)
                                <a href="mailto:{{ $item->branch_email }}">{{ $item->branch_email }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($item->scanner)
                                <a href="{{ uploaded_asset($item->scanner) }}" target="_blank" rel="noopener">{{ translate('View File') }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ optional($item->creator)->name ?? '-' }}</td>
                        <td>
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input onchange="update_status(this)" value="{{ $item->id }}" type="checkbox" @if($item->status == 'active') checked @endif>
                                <span class="slider round"></span>
                            </label>
                        </td>
                        <td class="text-right">
                            <a class="btn btn-soft-info btn-icon btn-circle btn-sm" href="{{ route('booked-to.show', $item->id) }}" title="{{ translate('View') }}"><i class="las la-eye"></i></a>
                            <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('booked-to.edit', $item->id) }}" title="{{ translate('Edit') }}"><i class="las la-edit"></i></a>
                            <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('booked-to.destroy', $item->id) }}" title="{{ translate('Delete') }}"><i class="las la-trash"></i></a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">{{ $booked_to->appends(request()->input())->links() }}</div>
    </div>
</div>
@endsection

@section('modal')
    @include('modals.delete_modal')
    <form action="{{ route('booked-to.index') }}" method="GET">
        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
        <div class="modal fade" id="bookedToFilterModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Filter Booked To') }}</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row gutters-5">
                            <div class="col-md-12 mb-3">
                                <label>{{ translate('Search') }}</label>
                                <input type="text" class="form-control" name="search" value="{{ $sort_search }}" placeholder="{{ translate('Location, branch, or contact') }}">
                            </div>
                            @foreach ([
                                'transport' => 'Transport',
                                'location' => 'Location',
                                'branch_name' => 'Branch Name',
                                'branch_address' => 'Branch Address',
                                'branch_code' => 'Branch Code',
                                'branch_gst_number' => 'Branch GST Number',
                                'branch_mobile_number' => 'Branch Mobile Number',
                                'branch_alternate_mobile_number' => 'Branch Alternate Mobile Number',
                                'contact_incharge' => 'Contact - Incharge',
                                'branch_email' => 'Branch Email ID',
                                'created_by' => 'Created By',
                            ] as $field => $label)
                                <div class="col-md-6 mb-3">
                                    <label>{{ translate($label) }}</label>
                                    <input type="text" class="form-control" name="{{ $field }}" value="{{ $filters[$field] ?? '' }}">
                                </div>
                            @endforeach
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Scanner') }}</label>
                                <select name="scanner" class="form-control">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="1" @selected(($filters['scanner'] ?? '') === '1')>{{ translate('Has file') }}</option>
                                    <option value="0" @selected(($filters['scanner'] ?? '') === '0')>{{ translate('No file') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Status') }}</label>
                                <select name="status" class="form-control">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>{{ translate('Active') }}</option>
                                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>{{ translate('Inactive') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('booked-to.index') }}" class="btn btn-danger">{{ translate('Reset') }}</a>
                        <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
<script>
    function update_status(el) {
        $.post('{{ route('booked-to.update_status') }}', {
            _token: '{{ csrf_token() }}',
            id: el.value,
            status: el.checked ? 1 : 0
        }, function(data) {
            AIZ.plugins.notify(data == 1 ? 'success' : 'danger', data == 1 ? '{{ translate('Status updated successfully') }}' : '{{ translate('Something went wrong') }}');
        });
    }
</script>
@endsection
