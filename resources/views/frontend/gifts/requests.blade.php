@extends('frontend.layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">{{ translate('My Gift Requests') }}</h4>
            <p class="text-muted mb-0">{{ translate('Track the status of your gift redemptions.') }}</p>
        </div>
        <a href="{{ route('gifts.front.index') }}" class="btn btn-outline-primary btn-sm">{{ translate('Back to Gifts') }}</a>
    </div>

    @if($requests->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h5 class="mb-1">{{ translate('No gift requests yet') }}</h5>
                <p class="text-muted mb-2">{{ translate('Redeem a gift to see it listed here.') }}</p>
                <a href="{{ route('gifts.front.index') }}" class="btn btn-primary btn-sm">{{ translate('Browse Gifts') }}</a>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ translate('Gift') }}</th>
                                <th>{{ translate('Quantity') }}</th>
                                <th>{{ translate('Cost') }}</th>
                                <th>{{ translate('Status') }}</th>
                                <th>{{ translate('Requested At') }}</th>
                                <th>{{ translate('Address') }}</th>
                                <th>{{ translate('Note') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $req)
                                @php $addr = $req->meta['shipping_address'] ?? null; @endphp
                                <tr>
                                    <td>{{ $req->id }}</td>
                                    <td>{{ optional($req->gift)->name }}</td>
                                    <td>{{ $req->quantity }}</td>
                                    <td>{{ single_price($req->cost_snapshot) }}</td>
                                    <td>
                                        <span class="badge badge-pill badge-{{ $req->status_badge_class }} px-3 py-2 text-capitalize">{{ $req->status }}</span>
                                    </td>
                                    <td>{{ $req->created_at }}</td>
                                    <td class="text-muted small">
                                        @if($addr)
                                            {{ $addr['address'] ?? '' }}<br>
                                            {{ $addr['city'] ?? '' }}, {{ $addr['state'] ?? '' }} {{ $addr['postal_code'] ?? '' }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $req->admin_note ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {{ $requests->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
