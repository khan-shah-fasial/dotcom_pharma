@extends('backend.layouts.app')

@section('content')

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{translate('Minimum Order Amount Settings')}}</h5>
            </div>
            <form action="{{ route('business_settings.update') }}" method="POST" enctype="multipart/form-data">
              <div class="card-body">
                   @csrf
                    <div class="form-group row">
                        <div class="col-md-4">
                            <label class="control-label">{{translate('Minimum Order Amount Check')}}</label>
                        </div>
                        <div class="col-md-8">
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input type="hidden" name="types[]" value="minimum_order_amount_check">
                                <input value="1" name="minimum_order_amount_check" type="checkbox" @if (get_setting('minimum_order_amount_check') == 1)
                                    checked
                                @endif>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group row">
                        <input type="hidden" name="types[]" value="minimum_order_amount">
                        <div class="col-md-4">
                            <label class="control-label">{{translate('Set Minimum Order Amount')}}</label>
                        </div>
                        <div class="col-md-8">
                            <input type="number" min="0" step="0.01" class="form-control" name="minimum_order_amount" value="{{ get_setting('minimum_order_amount') }}" placeholder="{{ translate('Minimum Order Amount') }}" required>
                        </div>
                    </div>
                    <div class="form-group mb-0 text-right">
                        <button type="submit" class="btn btn-sm btn-primary">{{translate('Save')}}</button>
                    </div>
              </div>
            </form>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Referral Rewards Settings') }}</h5>
            </div>
            <form action="{{ route('business_settings.update') }}" method="POST" enctype="multipart/form-data">
                <div class="card-body">
                    @csrf

                    <div class="form-group row">
                        <div class="col-md-4">
                            <label class="control-label">{{ translate('Enable Referral Discount') }}</label>
                        </div>
                        <div class="col-md-8">
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input type="hidden" name="types[]" value="referral_discount_enabled">
                                <input value="1" name="referral_discount_enabled" type="checkbox" @if (get_setting('referral_discount_enabled') == 1) checked @endif>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group row">
                        <input type="hidden" name="types[]" value="referral_discount_amount">
                        <div class="col-md-4">
                            <label class="control-label">{{ translate('Discount Amount For Referred User') }}</label>
                        </div>
                        <div class="col-md-8">
                            <input type="number" min="0" step="0.01" class="form-control" name="referral_discount_amount" value="{{ get_setting('referral_discount_amount') ?? 0 }}">
                        </div>
                    </div>

                    {{-- <div class="form-group row">
                        <input type="hidden" name="types[]" value="referral_discount_min_order_amount">
                        <div class="col-md-4">
                            <label class="control-label">{{ translate('Minimum Order Amount For Referral Discount') }}</label>
                        </div>
                        <div class="col-md-8">
                            <input type="number" min="0" step="0.01" class="form-control" name="referral_discount_min_order_amount" value="{{ get_setting('referral_discount_min_order_amount') ?? 0 }}">
                        </div>
                    </div> --}}

                    <div class="form-group row">
                        <input type="hidden" name="types[]" value="referral_reward_amount_for_referrer">
                        <div class="col-md-4">
                            <label class="control-label">{{ translate('Reward Amount For Referrer (Wallet Credit)') }}</label>
                        </div>
                        <div class="col-md-8">
                            <input type="number" min="0" step="0.01" class="form-control" name="referral_reward_amount_for_referrer" value="{{ get_setting('referral_reward_amount_for_referrer') ?? 0 }}">
                        </div>
                    </div>

                    <div class="form-group mb-0 text-right">
                        <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0 h6">{{ translate('Wallet Reward Tiers') }}</h5>
                <div class="d-flex align-items-center">
                    <label class="mb-0 mr-2">{{ translate('Enable') }}</label>
                    <label class="aiz-switch aiz-switch-success mb-0">
                        <input type="hidden" name="types[]" form="wallet-reward-form" value="gift_reward_enabled">
                        <input value="1" name="gift_reward_enabled" form="wallet-reward-form" type="checkbox" @if (get_setting('gift_reward_enabled') == 1) checked @endif>
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
            <form id="wallet-reward-form" action="{{ route('business_settings.update') }}" method="POST">
                @csrf
                <div class="card-body">
                    <input type="hidden" name="types[]" value="gift_reward_tiers">

                    @php
                        $giftRewardTiers = json_decode(get_setting('gift_reward_tiers'), true) ?? [];
                        if (empty($giftRewardTiers)) {
                            $giftRewardTiers = [['min' => '', 'reward' => '']];
                        }
                    @endphp

                    <div class="table-responsive">
                        <table class="table table-bordered mb-3">
                            <thead>
                                <tr>
                                    <th width="35%">{{ translate('Minimum Order Amount') }}</th>
                                    <th width="35%">{{ translate('Reward Amount') }}</th>
                                    <th width="20%">{{ translate('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody id="gift-tier-rows">
                                @foreach ($giftRewardTiers as $tier)
                                    <tr class="gift-tier-row">
                                        <td>
                                            <input type="number" min="0" step="0.01" class="form-control" name="gift_reward_tiers[][min]" value="{{ $tier['min'] ?? '' }}" required>
                                        </td>
                                        <td>
                                            <input type="number" min="0" step="0.01" class="form-control" name="gift_reward_tiers[][reward]" value="{{ $tier['reward'] ?? '' }}" required>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-danger" data-toggle="remove-parent" data-parent="tr">{{ translate('Delete') }}</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button"
                                class="btn btn-sm btn-soft-primary"
                                data-toggle="add-more"
                                data-target="#gift-tier-rows"
                                data-content='
                                <tr class="gift-tier-row">
                                    <td>
                                        <input type="number" min="0" step="0.01" class="form-control" name="gift_reward_tiers[][min]" required>
                                    </td>
                                    <td>
                                        <input type="number" min="0" step="0.01" class="form-control" name="gift_reward_tiers[][reward]" required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger" data-toggle="remove-parent" data-parent="tr">{{ translate('Delete') }}</button>
                                    </td>
                                </tr>
                                '>{{ translate('Add Tier') }}</button>

                        <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
    (function () {
        const tierRows = $('#gift-tier-rows');

        function reindexRows() {
            tierRows.find('.gift-tier-row').each(function (idx, row) {
                const $row = $(row);
                $row.find('input').each(function () {
                    const $input = $(this);
                    if ($input.attr('name').includes('[min]')) {
                        $input.attr('name', `gift_reward_tiers[${idx}][min]`);
                    } else {
                        $input.attr('name', `gift_reward_tiers[${idx}][reward]`);
                    }
                });
            });
        }

        reindexRows();

        $('[data-toggle="add-more"][data-target="#gift-tier-rows"]').on('click', function () {
            setTimeout(reindexRows, 0);
        });

        tierRows.on('click', '[data-toggle="remove-parent"]', function () {
            setTimeout(reindexRows, 0);
        });
    })();
</script>
@endsection
