<?php

namespace App\Http\Requests;

use App\Models\TaxMaster;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TaxMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(TaxMaster::normalize($this->all()));
    }

    public function rules(): array
    {
        $id = $this->route('id');

        $taxCodeRules = [
            'required',
            'string',
            'max:20',
            'regex:/^[A-Z0-9][A-Z0-9\-_]*$/',
        ];

        if (TaxMaster::tableReady()) {
            $taxCodeRules[] = Rule::unique('tax_masters', 'tax_code')->ignore($id);
        }

        return [
            'kind' => ['required', Rule::in(array_keys(TaxMaster::KINDS))],
            'tax_code' => $taxCodeRules,
            'description' => ['nullable', 'string', 'max:255'],
            'hsn_code' => ['nullable', 'string', 'max:50'],
            'hs_code' => ['nullable', 'string', 'max:50'],
            'applied_on_category' => ['nullable', 'string', 'max:255'],
            'applied_on_sku' => ['nullable', 'string', 'max:255'],
            'applied_on_product' => ['nullable', 'string', 'max:255'],
            'applied_on_variant' => ['nullable', 'string', 'max:255'],
            'purchase_tax' => ['required', 'numeric', 'min:0', 'max:100'],
            'purchase_cgst' => ['required', 'numeric', 'min:0', 'max:100'],
            'purchase_sgst' => ['required', 'numeric', 'min:0', 'max:100'],
            'purchase_ut_cgst' => ['required', 'numeric', 'min:0', 'max:100'],
            'purchase_utgst' => ['required', 'numeric', 'min:0', 'max:100'],
            'purchase_igst' => ['required', 'numeric', 'min:0', 'max:100'],
            'sale_same_as_purchase' => ['required', 'boolean'],
            'sale_tax' => ['required', 'numeric', 'min:0', 'max:100'],
            'sale_cgst' => ['required', 'numeric', 'min:0', 'max:100'],
            'sale_sgst' => ['required', 'numeric', 'min:0', 'max:100'],
            'sale_ut_cgst' => ['required', 'numeric', 'min:0', 'max:100'],
            'sale_utgst' => ['required', 'numeric', 'min:0', 'max:100'],
            'sale_igst' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $this->all();

            $checkUt = TaxMaster::utColumnsReady() || !TaxMaster::tableReady();
            foreach (['purchase', 'sale'] as $side) {
                if (!TaxMaster::pathsMatch(
                    TaxMaster::toDecimal($data[$side . '_tax'] ?? 0),
                    TaxMaster::toDecimal($data[$side . '_cgst'] ?? 0),
                    TaxMaster::toDecimal($data[$side . '_sgst'] ?? 0),
                    TaxMaster::toDecimal($data[$side . '_ut_cgst'] ?? 0),
                    TaxMaster::toDecimal($data[$side . '_utgst'] ?? 0),
                    TaxMaster::toDecimal($data[$side . '_igst'] ?? 0),
                    $checkUt
                )) {
                    $validator->errors()->add(
                        $side . '_utgst',
                        translate($side === 'purchase'
                            ? 'Purchase UT CGST % + UTGST % must equal Tax %.'
                            : 'Sale UT CGST % + UTGST % must equal Tax %.')
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'kind.required' => translate('Please select Tax Type.'),
            'tax_code.required' => translate('Please enter Tax Code.'),
            'tax_code.unique' => translate('This Tax Code already exists.'),
            'tax_code.regex' => translate('Tax Code may use letters, numbers, hyphen, and underscore only.'),
        ];
    }
}
