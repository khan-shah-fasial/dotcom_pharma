@extends('backend.layouts.app')

@section('content')

    <div class="card">
        <form class="" id="" action="" method="GET">
            <div class="card-header row gutters-5">
                <div class="col-md-12 mb-2">
                    <h5 class="mb-0 h6">{{ translate('Countries') }}</h5>
                </div>
                <div class="col-md-2 mb-2">
                    <button type="submit" form="refresh-forex-form" class="btn btn-soft-success btn-block">
                        <i class="las la-sync"></i> {{ translate('Refresh Forex') }}
                    </button>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="mb-0 small">{{ translate('System Default Country') }}</label>
                    <select id="system_default_country" class="form-control aiz-selectpicker" data-live-search="true" data-skip-country-default="1" onchange="update_system_default_country(this)">
                        <option value="">{{ translate('Select') }}</option>
                        @foreach($enabled_countries as $enabled_country)
                            <option value="{{ $enabled_country->id }}" @selected((string)$system_default_country_id === (string)$enabled_country->id)>
                                {{ $enabled_country->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="mb-0 small">{{ translate('Country') }}</label>
                    <select name="country_id" class="form-control aiz-selectpicker" data-live-search="true" data-skip-country-default="1">
                        <option value="">{{ translate('All Countries') }}</option>
                        @foreach($filter_countries as $filterCountry)
                            <option value="{{ $filterCountry->id }}" @selected((string) $countryId === (string) $filterCountry->id)>{{ $filterCountry->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="mb-0 small">{{ translate('Status') }}</label>
                    <select name="status" class="form-control aiz-selectpicker">
                        <option value="">{{ translate('All Statuses') }}</option>
                        <option value="1" @selected((string) $status === '1')>{{ translate('Shown') }}</option>
                        <option value="0" @selected((string) $status === '0')>{{ translate('Hidden') }}</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="mb-0 small">{{ translate('Default Currency') }}</label>
                    <select name="default_currency_id" class="form-control aiz-selectpicker" data-live-search="true">
                        <option value="">{{ translate('All Currencies') }}</option>
                        @foreach($active_currencies as $currency)
                            <option value="{{ $currency->id }}" @selected((string) $currencyId === (string) $currency->id)>{{ $currency->name }} ({{ $currency->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="mb-0 small">{{ translate('Default Language') }}</label>
                    <select name="default_language_id" class="form-control aiz-selectpicker" data-live-search="true">
                        <option value="">{{ translate('All Languages') }}</option>
                        @foreach($active_languages as $language)
                            <option value="{{ $language->id }}" @selected((string) $languageId === (string) $language->id)>{{ $language->name }} ({{ $language->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="mb-0 small">{{ translate('Search') }}</label>
                    <input type="text" class="form-control" id="sort_country" name="sort_country" @isset($sort_country) value="{{ $sort_country }}" @endisset placeholder="{{ translate('Name, ISO2, ISO3 or capital') }}">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="mb-0 small">{{ translate('Sort By') }}</label>
                    <select name="sort_by" class="form-control aiz-selectpicker">
                        @foreach([
                            'status' => 'Status',
                            'name' => 'Name',
                            'code' => 'ISO2',
                            'iso3' => 'ISO3',
                            'capital' => 'Capital',
                            'forex_rate' => 'Forex Rate',
                            'default_currency_id' => 'Default Currency',
                            'default_language_id' => 'Default Language',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected($sortBy === $value)>{{ translate($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 mb-2">
                    <label class="mb-0 small">{{ translate('Order') }}</label>
                    <select name="sort_order" class="form-control">
                        <option value="asc" @selected($sortOrder === 'asc')>{{ translate('Asc') }}</option>
                        <option value="desc" @selected($sortOrder === 'desc')>{{ translate('Desc') }}</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2 d-flex align-items-end">
                    <button class="btn btn-primary btn-block" type="submit">{{ translate('Filter') }}</button>
                </div>
                <div class="col-md-1 mb-2 d-flex align-items-end">
                    <a href="{{ route('countries.index') }}?country_id=" class="btn btn-soft-secondary btn-block">{{ translate('Reset') }}</a>
                </div>
            </div>
        </form>
        <form id="refresh-forex-form" action="{{ route('countries.refresh_forex') }}" method="POST" class="d-none">
            @csrf
        </form>
        <div class="card-body">
            <div class="table-responsive">
            <table class="table table-striped table-bordered" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th width="10%">#</th>
                        @include('backend.inc.sort_th', ['column' => 'name', 'label' => translate('Name'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                        @include('backend.inc.sort_th', ['column' => 'code', 'label' => translate('ISO2'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                        @include('backend.inc.sort_th', ['column' => 'iso3', 'label' => translate('ISO3'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                        @include('backend.inc.sort_th', ['column' => 'capital', 'label' => translate('Capital Of Country'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                        <th class="d-none" data-breakpoints="lg">{{ translate('Timezone') }}</th>
                        <th>{{ translate('Live Current Date / Day & Time') }}</th>
                        @include('backend.inc.sort_th', ['column' => 'forex_rate', 'label' => translate('Live Forex Rate'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                        @include('backend.inc.sort_th', ['column' => 'default_currency_id', 'label' => translate('Default Currency'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                        @include('backend.inc.sort_th', ['column' => 'default_language_id', 'label' => translate('Default Language'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                        <th>{{ translate('Regional Languages') }}</th>
                        @include('backend.inc.sort_th', ['column' => 'status', 'label' => translate('Show/Hide'), 'sortBy' => $sortBy, 'sortOrder' => $sortOrder])
                    </tr>
                </thead>
                <tbody>
                    @foreach($countries as $key => $country)
                        <tr>
                            <td>{{ ($key+1) + ($countries->currentPage() - 1)*$countries->perPage() }}</td>
                            <td>{{ $country->name }}</td>
                            <td>{{ $country->code }}</td>
                            <td>
                                <input id="iso3_{{ $country->id }}" class="form-control form-control-sm text-uppercase"
                                    maxlength="3" value="{{ $country->iso3 }}"
                                    onchange="update_defaults({{ $country->id }}, false, this)">
                            </td>
                            <td>
                                <input id="capital_{{ $country->id }}" class="form-control form-control-sm"
                                    maxlength="191" value="{{ $country->capital }}"
                                    onchange="update_defaults({{ $country->id }}, false, this)">
                            </td>
                            <td class="d-none">
                                <span class="text-nowrap">{{ $country->timezone ?: translate('Not available') }}</span>
                            </td>
                            <td>
                                @php($localDateTime = $country->localDateTime())
                                <span class="country-live-time"
                                    data-country-id="{{ $country->id }}"
                                    data-timezone="{{ $country->timezone }}">
                                    {{ $localDateTime ? $localDateTime->format('d M Y, l, h:i A') : translate('Timezone not set') }}
                                </span>
                            </td>
                            <td>
                                @if($country->forex_rate && $country->defaultCurrency)
                                    <strong>
                                        1 {{ $country->defaultCurrency->code }}
                                        = {{ rtrim(rtrim(number_format((float) $country->forex_rate, 8, '.', ''), '0'), '.') }}
                                        {{ $country->forex_base_currency_code }}
                                    </strong>
                                    @if($country->forex_rate_updated_at)
                                        <div class="small text-muted">
                                            {{ translate('Updated') }}:
                                            {{ $country->forex_rate_updated_at->timezone($display_timezone)->format('d M Y, h:i A') }}
                                        </div>
                                    @endif
                                @elseif(!$country->defaultCurrency)
                                    <span class="text-muted">{{ translate('Set default currency') }}</span>
                                @else
                                    <span class="text-muted">{{ translate('Not refreshed') }}</span>
                                @endif
                            </td>
                            <td>
                                <select id="default_currency_id_{{ $country->id }}"
                                    class="form-control form-control-sm aiz-selectpicker"
                                    data-live-search="true" data-width="100%"
                                    onchange="update_defaults({{ $country->id }}, false, this)">
                                    <option value="">{{ translate('Select') }}</option>
                                    @foreach($active_currencies as $currency)
                                        <option value="{{ $currency->id }}" @selected((string)$country->default_currency_id === (string)$currency->id)>
                                            {{ $currency->name }} ({{ $currency->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select id="default_language_id_{{ $country->id }}"
                                    class="form-control form-control-sm aiz-selectpicker"
                                    data-live-search="true" data-width="100%"
                                    onchange="update_defaults({{ $country->id }}, false, this)">
                                    <option value="">{{ translate('Select') }}</option>
                                    @foreach($active_languages as $language)
                                        <option value="{{ $language->id }}" @selected((string)$country->default_language_id === (string)$language->id)>
                                            {{ $language->name }} ({{ $language->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select id="regional_language_ids_{{ $country->id }}"
                                    class="form-control form-control-sm aiz-selectpicker"
                                    data-live-search="true" data-width="100%"
                                    data-selected-text-format="count"
                                    data-placeholder="{{ translate('Select regional languages') }}"
                                    multiple
                                    onchange="update_defaults({{ $country->id }}, false, this)">
                                    @foreach($active_languages as $language)
                                        <option value="{{ $language->id }}" @selected(collect($country->regional_language ?? [])->map(fn ($id) => (string) $id)->contains((string) $language->id, true))>
                                            {{ $language->name }} ({{ $language->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                              <label class="aiz-switch aiz-switch-success mb-0">
                                <input onchange="update_status(this)" value="{{ $country->id }}" type="checkbox" <?php if($country->status == 1) echo "checked";?> >
                                <span class="slider round"></span>
                              </label>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            <div class="aiz-pagination">
                {{ $countries->appends(request()->input())->links() }}
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        var SYSTEM_DEFAULT_CURRENCY_ID = @json($system_default_currency_id ? (int) $system_default_currency_id : null);
        var DEFAULT_LANGUAGE_ID = @json($default_language_id ? (int) $default_language_id : null);

        function update_status(el){

            if('{{env('DEMO_MODE')}}' == 'On'){
                AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
                return;
            }

            if(el.checked){
                var status = 1;
            }
            else{
                var status = 0;
            }
            $.post('{{ route('countries.status') }}', {_token:'{{ csrf_token() }}', id:el.value, status:status}, function(data){
                if(data == 1){
                    AIZ.plugins.notify('success', '{{ translate('Country status updated successfully') }}');
                }
                else{
                    AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                }
            });
        }

        function update_defaults(countryId, silent, changedEl){
            if('{{env('DEMO_MODE')}}' == 'On'){
                AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
                return;
            }

            var currencyId = $('#default_currency_id_' + countryId).val();
            var languageId = $('#default_language_id_' + countryId).val();
            var regionalLanguageIds = $('#regional_language_ids_' + countryId).val() || [];
            var iso3 = ($('#iso3_' + countryId).val() || '').toUpperCase();
            var capital = $('#capital_' + countryId).val() || '';

            // Convenience: if admin selects only one field, auto-fill the other with system defaults
            // to avoid requiring two manual selections.
            var changedId = changedEl && changedEl.id ? changedEl.id : null;
            if (changedId && changedId.indexOf('default_currency_id_') === 0) {
                if (currencyId && !languageId && DEFAULT_LANGUAGE_ID) {
                    languageId = DEFAULT_LANGUAGE_ID;
                    $('#default_language_id_' + countryId).val(languageId);
                    $('#default_language_id_' + countryId).selectpicker('refresh');
                }
            } else if (changedId && changedId.indexOf('default_language_id_') === 0) {
                if (languageId && !currencyId && SYSTEM_DEFAULT_CURRENCY_ID) {
                    currencyId = SYSTEM_DEFAULT_CURRENCY_ID;
                    $('#default_currency_id_' + countryId).val(currencyId);
                    $('#default_currency_id_' + countryId).selectpicker('refresh');
                }
            }

            $.ajax({
                url: '{{ route('countries.defaults') }}',
                type: 'POST',
                data: {
                    _token:'{{ csrf_token() }}',
                    id: countryId,
                    iso3: iso3,
                    capital: capital,
                    default_currency_id: currencyId,
                    default_language_id: languageId,
                    regional_language_ids: regionalLanguageIds
                }
            }).done(function (data) {
                if (data == 1) {
                    if (!silent) {
                        AIZ.plugins.notify('success', '{{ translate('Updated successfully') }}');
                    }
                } else {
                    if (!silent) {
                        AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                    }
                }
            }).fail(function (xhr) {
                if (!silent) {
                    var msg = '{{ translate('Something went wrong') }}';
                    if (xhr && xhr.status) {
                        msg = msg + ' (' + xhr.status + ')';
                    }
                    AIZ.plugins.notify('danger', msg);
                }
            });
        }

        function update_system_default_country(el){
            if('{{env('DEMO_MODE')}}' == 'On'){
                AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
                return;
            }

            var countryId = $(el).val();
            if(!countryId){
                AIZ.plugins.notify('warning', '{{ translate('Please select a country') }}');
                return;
            }

            $.post('{{ route('countries.system_default') }}', {
                _token:'{{ csrf_token() }}',
                country_id: countryId
            }, function(data){
                if(data == 1){
                    AIZ.plugins.notify('success', '{{ translate('Updated successfully') }}');

                    // If that country has no defaults set yet, auto-fill to avoid blank defaults.
                    var $currencySel = $('#default_currency_id_' + countryId);
                    var $languageSel = $('#default_language_id_' + countryId);
                    if ($currencySel.length && $languageSel.length) {
                        var needsUpdate = false;
                        if (!$currencySel.val() && SYSTEM_DEFAULT_CURRENCY_ID) {
                            $currencySel.val(SYSTEM_DEFAULT_CURRENCY_ID);
                            $currencySel.selectpicker('refresh');
                            needsUpdate = true;
                        }
                        if (!$languageSel.val() && DEFAULT_LANGUAGE_ID) {
                            $languageSel.val(DEFAULT_LANGUAGE_ID);
                            $languageSel.selectpicker('refresh');
                            needsUpdate = true;
                        }
                        if (needsUpdate) {
                            update_defaults(countryId, true);
                        }
                    }
                }
                else{
                    AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                }
            });
        }

        function updateCountryClocks() {
            var now = new Date();

            $('.country-live-time').each(function () {
                var timezone = $(this).attr('data-timezone');
                if (!timezone) {
                    $(this).text('{{ translate('Timezone not set') }}');
                    return;
                }

                try {
                    var formatted = new Intl.DateTimeFormat('en-GB', {
                        timeZone: timezone,
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                        weekday: 'long',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    }).format(now);
                    $(this).text(formatted);
                } catch (error) {
                    $(this).text('{{ translate('Invalid timezone') }}');
                }
            });
        }

        updateCountryClocks();
        setInterval(updateCountryClocks, 60000);

    </script>
@endsection
