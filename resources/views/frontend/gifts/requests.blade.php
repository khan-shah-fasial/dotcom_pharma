@extends('frontend.layouts.app')

@push('styles')
<style>
    .gift-request-status-cell {
        white-space: nowrap;
    }

    .gift-request-status-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 96px;
        padding: 0.45rem 0.9rem;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
        text-transform: capitalize;
        white-space: nowrap;
    }
</style>
@endpush

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
                                    <td class="gift-request-status-cell">
                                        <span class="badge badge-inline badge-{{ $req->status_badge_class }} gift-request-status-badge">
                                            {{ str_replace('_', ' ', $req->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $req->created_at }}</td>
                                    <td class="text-muted small">
                                        @if($addr)
                                            {{ $addr['address'] ?? '' }}<br>
                                            {{ $addr['city'] ?? '' }}, {{ $addr['state'] ?? '' }} {{ $addr['postal_code'] ?? '' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $req->admin_note ?? '-' }}</td>
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
