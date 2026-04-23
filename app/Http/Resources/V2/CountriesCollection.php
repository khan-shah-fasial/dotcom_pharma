<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CountriesCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function($data) {
                $defaultCurrency = $data->defaultCurrency;
                $defaultLanguage = $data->defaultLanguage;

                return [
                    'id'      => (int) $data->id,
                    'code' => $data->code,
                    'name' => $data->name,
                    'status' => (int) $data->status,
                    'default_currency' => $defaultCurrency ? [
                        'id' => (int) $defaultCurrency->id,
                        'code' => $defaultCurrency->code,
                        'name' => $defaultCurrency->name,
                        'symbol' => $defaultCurrency->symbol,
                    ] : null,
                    'default_language' => $defaultLanguage ? [
                        'id' => (int) $defaultLanguage->id,
                        'code' => $defaultLanguage->code,
                        'name' => translate($defaultLanguage->name),
                        'app_lang_code' => $defaultLanguage->app_lang_code,
                        'rtl' => (bool) ($defaultLanguage->rtl == 1),
                    ] : null,
                ];
            })
        ];
    }

    public function with($request)
    {
        return [
            'success' => true,
            'status' => 200
        ];
    }
}
