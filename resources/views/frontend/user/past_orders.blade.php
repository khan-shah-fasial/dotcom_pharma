@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="card shadow-none rounded-0 border">
        <div class="card-header border-bottom-0 d-flex align-items-center justify-content-between flex-wrap">
            <div>
                <h5 class="mb-0 fs-20 fw-700 text-dark">{{ translate('Past Orders') }}</h5>
                <div class="text-muted fs-12">{{ translate('Grouped products you have purchased earlier') }}</div>
            </div>
        </div>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead class="text-gray fs-12">
                    <tr>
                        <th class="pl-0">{{ translate('Product') }}</th>
                        <th data-breakpoints="md">{{ translate('Variant') }}</th>
                        <th>{{ translate('Last Ordered') }}</th>
                        <th class="text-center">{{ translate('Quantity') }}</th>
                        <th class="text-right">{{ translate('Spent') }}</th>
                        <th class="text-right pr-0">{{ translate('Saved') }}</th>
                    </tr>
                </thead>
                <tbody class="fs-14">
                    @forelse ($groupedOrders as $row)
                        @php
                            $productName = $row->product_name ?? translate('Product unavailable');
                            $productSlug = $row->product_slug ?? null;
                            $thumb = $row->product_thumbnail ? uploaded_asset($row->product_thumbnail) : static_asset('assets/img/placeholder.jpg');
                            $spend = $row->total_sale ?? $row->total_price ?? 0;
                            $mrpTotal = $row->total_mrp ?? 0;
                            $saved = max($mrpTotal - $spend, 0);
                        @endphp
                        <tr>
                            <td class="pl-0">
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm mr-2">
                                        <img src="{{ $thumb }}"
                                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';"
                                            alt="{{ $productName }}">
                                    </span>
                                    @if ($productSlug)
                                        <a href="{{ route('product', $productSlug) }}" class="fw-700 text-dark">
                                            {{ $productName }}
                                        </a>
                                    @else
                                        <span class="fw-700 text-dark">{{ $productName }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $row->variation ?: translate('Default') }}</td>
                            <td class="text-secondary">
                                {{ $row->last_purchase_at ? date('d-m-Y', strtotime($row->last_purchase_at)) : '-' }}
                            </td>
                            <td class="text-center fw-700">{{ $row->total_qty }}</td>
                            <td class="text-right fw-700">{{ single_price($spend) }}</td>
                            <td class="text-right pr-0 fw-700 text-success">{{ single_price($saved) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                {{ translate('No past orders found') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination mt-2">
                {{ $groupedOrders->links() }}
            </div>
        </div>
    </div>
@endsection
