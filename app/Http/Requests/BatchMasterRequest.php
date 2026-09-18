<?php

namespace App\Http\Requests;

use App\Models\BatchMaster;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BatchMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $role = [];
        foreach (array_keys(BatchMaster::ROLE_KEYS) as $key) {
            $role[$key] = $this->input('role_price.' . $key, $this->input($key));
        }

        $merged = BatchMaster::normalize(array_merge($this->all(), [
            'role_price' => $role,
        ]));

        $this->merge($merged);
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $stockId = $this->input('product_stock_id');

        $codeRules = ['required', 'string', 'max:255'];
        if (BatchMaster::tableReady() && $stockId) {
            $codeRules[] = Rule::unique('batch_masters', 'batch_code')
                ->ignore($id)
                ->where(fn ($query) => $query->where('product_stock_id', $stockId));
        }

        return [
            'product_id' => ['required', 'integer'],
            'product_stock_id' => ['required', 'integer'],
            'batch_code' => $codeRules,
            'is_non_batch' => ['nullable', 'boolean'],
            'manufacturing_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'mrp_price' => ['nullable', 'numeric', 'min:0'],
            'qty' => ['required', 'numeric', 'min:0'],
            'role_price' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $mfg = $this->input('manufacturing_date');
            $exp = $this->input('expiry_date');
            if ($mfg && $exp && $exp < $mfg) {
                $validator->errors()->add('expiry_date', translate('Expiry month cannot be before manufacturing month.'));
            }
        });
    }

    public function messages(): array
    {
        return [
            'product_stock_id.required' => translate('Please select a SKU / Full Variant.'),
            'batch_code.required' => translate('Please enter Batch Code.'),
            'batch_code.unique' => translate('This Batch Code already exists for the selected SKU.'),
        ];
    }
}
