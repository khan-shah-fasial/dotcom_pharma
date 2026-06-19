@extends('backend.layouts.app')

@section('content')

    <div class="card">
        <form class="" id="" action="" method="GET">
            <div class="card-header row gutters-5">
                <div class="col text-center text-md-left">
                    <h5 class="mb-md-0 h6">{{ translate('Countries') }}</h5>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label class="mb-0">{{ translate('System Default Country') }}</label>
                        <select id="system_default_country" class="form-control" onchange="update_system_default_country(this)">
                            <option value="">{{ translate('Select') }}</option>
                            @foreach($enabled_countries as $enabled_country)
                                <option value="{{ $enabled_country->id }}" @selected((string)$system_default_country_id === (string)$enabled_country->id)>
                                    {{ $enabled_country->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" id="sort_country" name="sort_country" @isset($sort_country) value="{{ $sort_country }}" @endisset placeholder="{{ translate('Type country name') }}">
                </div>
                <div class="col-md-1">
                    <button class="btn btn-primary" type="submit">{{ translate('Filter') }}</button>
                </div>
            </div>
        </form>
        <div class="card-body">
            <div class="table-responsive">
            <table class="table table-striped table-bordered" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th width="10%">#</th>
                        <th>{{translate('Name')}}</th>
                        <th data-breakpoints="lg">{{translate('Code')}}</th>
                        <th>{{ translate('Default Currency') }}</th>
                        <th>{{ translate('Default Language') }}</th>
                        <th>{{ translate('Regional Languages') }}</th>
                        <th>{{translate('Show/Hide')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($countries as $key => $country)
                        <tr>
                            <td>{{ ($key+1) + ($countries->currentPage() - 1)*$countries->perPage() }}</td>
                            <td>{{ $country->name }}</td>
                            <td>{{ $country->code }}</td>
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
                                @php
                                    $selectedRegionalLanguages = collect($country->regional_language ?? [])->map(function ($id) {
                                        return (string) $id;
                                    })->all();
                                @endphp
                                <select id="regional_language_ids_{{ $country->id }}"
                                    class="form-control form-control-sm aiz-selectpicker"
                                    data-live-search="true" data-width="100%"
                                    data-selected-text-format="count"
                                    data-placeholder="{{ translate('Select regional languages') }}"
                                    multiple
                                    onchange="update_defaults({{ $country->id }}, false, this)">
                                    @foreach($active_languages as $language)
                                        <option value="{{ $language->id }}" @selected(in_array((string)$language->id, $selectedRegionalLanguages, true))>
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

    </script>
@endsection
