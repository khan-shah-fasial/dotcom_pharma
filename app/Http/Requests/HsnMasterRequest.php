<?php

namespace App\Http\Requests;

use App\Models\HsnMaster;
use App\Models\TaxMaster;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HsnMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawTaxable = (string) $this->input('taxable');
        $merge = [
            'hsn_code' => HsnMaster::digits($this->input('hsn_code')),
            'description' => trim((string) $this->input('description')),
            'status' => $this->has('status') ? 1 : 0,
        ];
        if ($rawTaxable === '1') {
            $merge['taxable'] = 1;
        } elseif ($rawTaxable === '0') {
            $merge['taxable'] = 0;
            $merge['tax_percent'] = 0;
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $codeRules = ['required', 'string', 'regex:/^[0-9]{2,50}$/'];
        if (HsnMaster::tableReady()) {
            $codeRules[] = Rule::unique('hsn_masters', 'hsn_code')->ignore($id);
        }

        return [
            'hsn_code' => $codeRules,
            'description' => ['nullable', 'string', 'max:255'],
            'taxable' => ['required', Rule::in([0, 1, '0', '1'])],
            'tax_type' => ['required', Rule::in(array_keys(TaxMaster::KINDS))],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', Rule::in([0, 1, '0', '1'])],
        ];
    }

    public function messages(): array
    {
        return [
            'hsn_code.required' => translate('Please enter HSN Code.'),
            'hsn_code.regex' => translate('HSN Code must be 2 to 50 digits.'),
            'hsn_code.unique' => translate('This HSN Code already exists.'),
            'taxable.required' => translate('Please select Taxable.'),
            'tax_type.required' => translate('Please select Tax Type.'),
            'tax_percent.required' => translate('Please enter Tax %.'),
        ];
    }
}
