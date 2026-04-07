@php use Illuminate\Support\Str; @endphp

@extends('frontend.layouts.app')

@section('content')
<style>
    :root {
        --gift-shadow: 0 12px 30px rgba(16,24,40,0.12);
        --gift-radius: 14px;
        --gift-accent: #0d6efd;
        --gift-muted: #6c757d;
        --gift-success1: #4caf50;
        --gift-success2: #67d66b;
    }
    .gift-card {
        border-radius: var(--gift-radius);
        overflow: hidden;
        box-shadow: var(--gift-shadow);
        border: 1px solid #eef1f4;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .gift-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 36px rgba(16,24,40,0.15);
    }
    .gift-thumb {
        position: relative;
        height: 210px;
        background-size: cover;
        background-position: center;
    }
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .gift-badge {
        position: absolute;
        top: 12px;
        padding: 8px 14px;
        border-radius: 999px;
        color: #fff;
        font-weight: 700;
        font-size: 13px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.18);
        line-height: 1.1;
        min-width: 90px;
        text-align: center;
    }
    .gift-badge.cost { left: 12px; background: linear-gradient(135deg,var(--gift-success1),var(--gift-success2)); }
    .gift-badge.stock { right: 12px; background: var(--gift-muted); }

    .gift-chip {
        display: inline-flex;
        align-items: center;
        padding: 10px 14px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 14px;
        color: #fff;
        line-height: 1.1;
    }
    .gift-chip.cost { background: linear-gradient(135deg,var(--gift-success1),var(--gift-success2)); }
    .gift-chip.stock { background: #5b6370; }

    .wallet-pill {
        display: inline-flex;
        align-items: center;
        padding: 10px 14px;
        border-radius: 999px;
        background: var(--gift-accent);
        color: #fff;
        font-weight: 700;
        font-size: 15px;
        box-shadow: 0 6px 16px rgba(13,110,253,0.2);
        line-height: 1.2;
        white-space: nowrap;
    }
    .gift-modal-body {
        padding: 24px;
    }
    #gift-detail-images img {
        border-radius: 10px;
        box-shadow: 0 6px 12px rgba(16,24,40,0.12);
        transition: transform 0.2s ease;
        cursor: pointer;
    }
        #gift-detail-images img:hover {
            transform: scale(1.03);
        }
    .address-tile {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 12px;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .address-tile:hover {
        border-color: var(--gift-accent);
        box-shadow: 0 8px 16px rgba(13,110,253,0.15);
    }
    .address-radio { margin-right: 10px; }
    @media (max-width: 576px) {
        .wallet-pill {
            width: 100%;
            justify-content: center;
            font-size: 14px;
        }
    }
</style>
<div class="container py-4">
    <div class="aiz-titlebar mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="fs-20 fw-700 text-dark">{{ translate('Gifts') }}</h1>
                <p class="text-muted mb-0">{{ translate('Redeem gifts directly from your wallet balance.') }}</p>
            </div>
            <div class="col-md-6 text-md-right mt-3 mt-md-0">
                <div class="d-flex justify-content-md-end align-items-center gap-2">
                    <a href="{{ route('gifts.requests') }}" class="btn btn-outline-secondary btn-sm mr-2">{{ translate('View Requests') }}</a>
                    <span class="wallet-pill">{{ translate('Wallet Balance') }}: {{ single_price($walletBalance) }}</span>
                </div>
            </div>
        </div>
    </div>

    @if ($gifts->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h5 class="mb-1">{{ translate('No gifts available right now.') }}</h5>
                <p class="text-muted mb-0">{{ translate('Please check back later.') }}</p>
            </div>
        </div>
    @else
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
            @foreach ($gifts as $gift)
                @php
                    $imageId = collect($gift->photos ?? [])->first();
                    $imageUrl = $imageId ? uploaded_asset($imageId) : static_asset('assets/img/placeholder.jpg');
                    $canRedeem = $gift->stock > 0 && $gift->is_active;
                    $hasBalance = ($walletBalance ?? 0) >= $gift->cost;
                @endphp
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 gift-card">
                        <div class="gift-thumb" style="background-image:url('{{ $imageUrl }}')">
                            <div class="gift-badge cost">{{ single_price($gift->cost) }}</div>
                            <div class="gift-badge stock">{{ $gift->stock }} {{ translate('left') }}</div>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title mb-1">{{ $gift->name }}</h5>
                            <p class="text-muted small mb-3 line-clamp-2">{!! $gift->description !!}</p>
                            <div class="mt-auto d-flex flex-column gap-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge badge-soft-{{ $gift->is_active ? 'success' : 'secondary' }}">
                                        {{ $gift->is_active ? translate('Active') : translate('Inactive') }}
                                    </span>
                                    <button type="button"
                                            class="btn btn-outline-secondary btn-sm"
                                            data-toggle="modal"
                                            data-target="#gift-detail-modal"
                                            data-name="{{ $gift->name }}"
                                            data-description="{!! htmlspecialchars($gift->description ?? '', ENT_QUOTES) !!}"
                                            data-cost="{{ single_price($gift->cost) }}"
                                            data-stock="{{ $gift->stock }}"
                                            data-gift-id="{{ $gift->id }}"
                                            data-images='@json(collect($gift->photos ?? [])->map(fn($id) => uploaded_asset($id))->filter()->values())'>
                                        <i class="las la-eye"></i> {{ translate('Details') }}
                                    </button>
                                </div>
                                @unless($canRedeem)
                                <small class="text-danger">{{ translate('Unavailable right now') }}</small>
                                @elseif(!$hasBalance)
                                <small class="text-danger">{{ translate('Insufficient wallet balance') }}</small>
                                @endunless
                                <button type="button"
                                        class="mt-3 btn btn-primary btn-block flex-grow-1 gift-redeem-btn"
                                        data-gift-id="{{ $gift->id }}"
                                        data-cost="{{ $gift->cost }}"
                                        @if(!($canRedeem && $hasBalance)) disabled @endif
                                        >
                                    {{ translate('Redeem') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@push('scripts')

<div class="modal fade" id="gift-detail-modal" tabindex="-1" role="dialog" aria-labelledby="giftDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="giftDetailLabel">{{ translate('Gift Detail') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body gift-modal-body">
                <div class="mt-2">
                    <div class="text-uppercase text-muted small mb-2">{{ translate('Images') }}</div>
                    <div class="row g-2" id="gift-detail-images"></div>
                </div>
                <div class="text-uppercase text-muted small mb-1">{{ translate('Name') }}</div>
                <h4 id="gift-detail-name" class="mb-3"></h4>

                <div class="d-flex flex-wrap align-items-center mb-3">
                    <div class="mr-3 mb-2">
                        <div class="text-uppercase text-muted small mb-1">{{ translate('Price') }}</div>
                        <span class="gift-chip cost mr-2 mb-2" id="gift-detail-cost"></span>
                    </div>
                    <div class="mb-2">
                        <div class="text-uppercase text-muted small mb-1">{{ translate('Stock') }}</div>
                        <span class="gift-chip stock mr-2 mb-2" id="gift-detail-stock"></span>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="text-uppercase text-muted small mb-1">{{ translate('Description') }}</div>
                    <div class="text-body" id="gift-detail-description"></div>
                </div>

                <div class="my-3 pb-5">
                    <button type="button" class="float-right btn btn-primary" id="gift-detail-redeem-btn" data-gift-id="">{{ translate('Redeem') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="gift-address-modal" tabindex="-1" role="dialog" aria-labelledby="giftAddressLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form class="modal-content" id="gift-address-form" action="{{ route('gifts.redeem') }}" method="POST">
            @csrf
            <input type="hidden" name="gift_id" id="address-modal-gift-id" value="">
            <input type="hidden" name="idempotency_key" id="address-modal-idem" value="">
            <input type="hidden" name="quantity" value="1">
            <input type="hidden" name="cost" id="address-modal-cost" value="">
            <div class="modal-header">
                <h6 class="modal-title" id="giftAddressLabel">{{ translate('Select Shipping Address') }}</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @if(isset($addresses) && $addresses->count())
                    @foreach($addresses as $address)
                        <label class="address-tile mb-3 w-100 d-flex">
                            <input class="address-radio" type="radio" name="address_id" value="{{ $address->id }}" @if($address->set_default) checked @endif>
                            <div>
                                <div class="fw-700">{{ $address->address }}</div>
                                <div class="text-muted small">
                                    {{ optional($address->city)->name }}, {{ optional($address->state)->name }},
                                    {{ optional($address->country)->name }} - {{ $address->postal_code }}
                                </div>
                                <div class="text-muted small">{{ translate('Phone') }}: {{ $address->phone }}</div>
                                @if($address->set_default)
                                    <span class="badge badge-soft-primary">{{ translate('Default') }}</span>
                                @endif
                            </div>
                        </label>
                    @endforeach
                @else
                    <div class="alert alert-warning mb-3">{{ translate('No addresses found. Please add one.') }}</div>
                @endif
                <button type="button" class="btn btn-link p-0" onclick="add_new_address('shipping')">{{ translate('Add New Address') }}</button>
                <div class="text-danger small mt-2 d-none" id="address-error">{{ translate('Please select an address.') }}</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                <button type="submit" class="btn btn-primary" id="address-submit-btn" {{ (isset($addresses) && $addresses->count()) ? '' : 'disabled' }}>{{ translate('Confirm & Redeem') }}</button>
            </div>
        </form>
    </div>
</div>

@include('frontend.partials.address.address_modal')
<script>
    function uuidv4() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
            return v.toString(16);
        });
    }

    $('.gift-redeem-btn').on('click', function() {
        var giftId = $(this).data('gift-id');
        var cost = parseFloat($(this).data('cost'));
        var wallet = parseFloat({{ $walletBalance ?? 0 }});
        if (cost > wallet) {
            return;
        }
        $('#address-modal-gift-id').val(giftId);
        $('#address-modal-cost').val(cost);
        $('#address-modal-idem').val(uuidv4());
        $('#address-error').addClass('d-none');
        $('#gift-address-modal input[name="address_id"]').prop('checked', false);
        $('#gift-address-modal').modal('show');
    });

    $('#gift-detail-redeem-btn').on('click', function() {
        var giftId = $(this).data('gift-id');
        var cost = parseFloat($(this).data('cost'));
        var wallet = parseFloat({{ $walletBalance ?? 0 }});
        if (cost > wallet) {
            return;
        }
        $('#address-modal-gift-id').val(giftId);
        $('#address-modal-cost').val(cost);
        $('#address-modal-idem').val(uuidv4());
        $('#address-error').addClass('d-none');
        $('#gift-address-modal input[name="address_id"]').prop('checked', false);
        $('#gift-address-modal').modal('show');
    });

    $('#gift-detail-modal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        $('#gift-detail-name').text(button.data('name'));
        $('#gift-detail-cost').text(button.data('cost'));
        $('#gift-detail-stock').text(button.data('stock') + ' {{ translate('left') }}');
        $('#gift-detail-description').html(button.data('description') || '');
        $('#gift-detail-redeem-btn').data('gift-id', button.data('gift-id'));
        $('#gift-detail-redeem-btn').data('cost', button.data('cost'));

        var images = button.data('images') || [];
        var container = $('#gift-detail-images');
        container.empty();
        if (images.length === 0) {
            container.append('<div class="col-12 text-muted">{{ translate('No images') }}</div>');
        } else {
            images.forEach(function (url) {
                container.append(
                    '<div class="col-4 mb-2"><img src="' + url + '" class="img-fluid rounded border"></div>'
                );
            });
        }
    });

    $('#gift-address-form').on('submit', function(e) {
        var selected = $('#gift-address-modal input[name="address_id"]:checked').length;
        if (!selected) {
            e.preventDefault();
            $('#address-error').removeClass('d-none');
        }
    });
</script>
@include('frontend.partials.address.address_js')
@endpush
