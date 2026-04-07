@extends('backend.layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">{{ translate('Gift Requests') }}</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ translate('User') }}</th>
                        <th>{{ translate('Gift') }}</th>
                        <th>{{ translate('Quantity') }}</th>
                        <th>{{ translate('Cost') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th>{{ translate('Requested At') }}</th>
                        <th class="text-right">{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $req)
                        <tr>
                            <td>{{ $req->id }}</td>
                            <td>{{ optional($req->user)->name }} (ID: {{ $req->user_id }})</td>
                            <td>{{ optional($req->gift)->name }} (ID: {{ $req->gift_id }})</td>
                            <td>{{ $req->quantity }}</td>
                            <td>{{ single_price($req->cost_snapshot) }}</td>
                            <td>
                                <span class="badge badge-{{ $req->status_badge_class }}">
                                    {{ ucfirst($req->status) }}
                                </span>
                            </td>
                            <td>{{ $req->created_at }}</td>
                            <td class="text-right">
                                @if($req->status === 'pending')
                                    <form action="{{ route('gift_requests.approve') }}" method="POST" class="d-inline-block mb-2">
                                        @csrf
                                        <input type="hidden" name="request_id" value="{{ $req->id }}">
                                        <button class="btn btn-soft-success btn-sm" type="submit">{{ translate('Approve') }}</button>
                                    </form>
                                    <form action="{{ route('gift_requests.reject') }}" method="POST" class="d-inline-block">
                                        @csrf
                                        <input type="hidden" name="request_id" value="{{ $req->id }}">
                                        <input type="text" name="reason" class="form-control form-control-sm mb-2" placeholder="{{ translate('Reason (optional)') }}">
                                        <button class="btn btn-soft-danger btn-sm" type="submit">{{ translate('Reject & Refund') }}</button>
                                    </form>
                                @elseif($req->status === 'approved')
                                    <form action="{{ route('gift_requests.deliver') }}" method="POST" class="d-inline-block mb-2">
                                        @csrf
                                        <input type="hidden" name="request_id" value="{{ $req->id }}">
                                        <input type="text" name="note" class="form-control form-control-sm mb-2" placeholder="{{ translate('Note (optional)') }}">
                                        <button class="btn btn-soft-primary btn-sm" type="submit">{{ translate('Mark Delivered') }}</button>
                                    </form>
                                    <form action="{{ route('gift_requests.reject') }}" method="POST" class="d-inline-block">
                                        @csrf
                                        <input type="hidden" name="request_id" value="{{ $req->id }}">
                                        <input type="text" name="reason" class="form-control form-control-sm mb-2" placeholder="{{ translate('Reason (optional)') }}">
                                        <button class="btn btn-soft-danger btn-sm" type="submit">{{ translate('Reject & Refund') }}</button>
                                    </form>
                                @else
                                    <span class="text-muted">{{ translate('Processed') }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="aiz-pagination">
            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection
