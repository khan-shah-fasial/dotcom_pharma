@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">{{ translate('Booked To') }}</h1></div>
        <div class="col-md-6 text-md-right"><a href="{{ route('booked-to.create') }}" class="btn btn-circle btn-info">{{ translate('Add New Booked To') }}</a></div>
    </div>
</div>

<div class="card">
    <div class="card-header row gutters-5">
        <div class="col text-center text-md-left"><h5 class="mb-md-0 h6">{{ translate('Booked To List') }}</h5></div>
        <div class="col-md-4">
            <form action="" method="GET">
                <input type="text" class="form-control form-control-sm" name="search" value="{{ $sort_search }}" placeholder="{{ translate('Type location / branch / contact & Enter') }}">
            </form>
        </div>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ translate('Transport') }}</th>
                    <th>{{ translate('Location') }}</th>
                    <th data-breakpoints="lg">{{ translate('Branch Name') }}</th>
                    <th data-breakpoints="lg">{{ translate('Branch Address') }}</th>
                    <th data-breakpoints="lg">{{ translate('Branch Code') }}</th>
                    <th data-breakpoints="lg">{{ translate('Branch GST Number') }}</th>
                    <th>{{ translate('Branch Mobile Number') }}</th>
                    <th data-breakpoints="lg">{{ translate('Branch Alternate Mobile Number') }}</th>
                    <th data-breakpoints="lg">{{ translate('Contact - Incharge') }}</th>
                    <th>{{ translate('Branch Email ID') }}</th>
                    <th data-breakpoints="lg">{{ translate('Scanner') }}</th>
                    <th data-breakpoints="lg">{{ translate('Created By') }}</th>
                    <th>{{ translate('Status') }}</th>
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
