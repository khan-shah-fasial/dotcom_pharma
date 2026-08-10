@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Transports') }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('transports.create') }}" class="btn btn-circle btn-info">
                <span>{{ translate('Add New Transport') }}</span>
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header row gutters-5">
        <div class="col text-center text-md-left">
            <h5 class="mb-md-0 h6">{{ translate('Transport List') }}</h5>
        </div>
        <div class="col-md-4">
            <form action="" method="GET">
                <input type="text" class="form-control form-control-sm" name="search" value="{{ $sort_search }}" placeholder="{{ translate('Type name & Enter') }}">
            </form>
        </div>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ translate('Name') }}</th>
                    <th>{{ translate('Mode') }}</th>
                    <th>{{ translate('URL') }}</th>
                    <th>{{ translate('Created By') }}</th>
                    <th>{{ translate('Status') }}</th>
                    <th class="text-right">{{ translate('Options') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transports as $key => $transport)
                    <tr>
                        <td>{{ $transports->firstItem() + $key }}</td>
                        <td>{{ $transport->name }}</td>
                        <td>{{ translate(ucfirst($transport->mode)) }}</td>
                        <td>
                            @if($transport->url)
                                <a href="{{ $transport->url }}" target="_blank" rel="noopener">{{ translate('Open') }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ optional($transport->creator)->name ?? '-' }}</td>
                        <td>
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input onchange="update_status(this)" value="{{ $transport->id }}" type="checkbox" @if($transport->status == 'active') checked @endif>
                                <span class="slider round"></span>
                            </label>
                        </td>
                        <td class="text-right">
                            <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('transports.edit', $transport->id) }}" title="{{ translate('Edit') }}">
                                <i class="las la-edit"></i>
                            </a>
                            <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('transports.destroy', $transport->id) }}" title="{{ translate('Delete') }}">
                                <i class="las la-trash"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $transports->appends(request()->input())->links() }}
        </div>
    </div>
</div>
@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection

@section('script')
<script>
    function update_status(el) {
        if ('{{ env('DEMO_MODE') }}' == 'On') {
            AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
            return;
        }
        $.post('{{ route('transports.update_status') }}', {
            _token: '{{ csrf_token() }}',
            id: el.value,
            status: el.checked ? 1 : 0
        }, function(data) {
            AIZ.plugins.notify(data == 1 ? 'success' : 'danger', data == 1 ? '{{ translate('Status updated successfully') }}' : '{{ translate('Something went wrong') }}');
        });
    }
</script>
@endsection
