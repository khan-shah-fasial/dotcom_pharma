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
        if ($this->has('rows')) {
            return;
        }

        $role = [];
        foreach (BatchMaster::PRICE_COLUMNS as $key => $column) {
            $role[$key] = $this->input('role_price.' . $key, $this->input($column, $this->input($key)));
        }

        $merged = BatchMaster::normalize(array_merge($this->all(), [
            'role_price' => $role,
        ]));

        $this->merge($merged);
    }

    public function rules(): array
    {
        if ($this->has('rows')) {
            return [
                'product_id' => ['required', 'integer'],
                'product_stock_id' => ['required', 'integer'],
                'rows' => ['required', 'array', 'min:1'],
                'rows.*.batch_code' => ['required', 'string', 'max:255'],
                'rows.*.qty' => ['nullable', 'numeric', 'min:0'],
                'rows.*.free_qty' => ['nullable', 'numeric', 'min:0'],
                'rows.*.mrp_price' => ['nullable', 'numeric', 'min:0'],
                'rows.*.purchase_rate' => ['nullable', 'numeric', 'min:0'],
                'rows.*.id' => ['nullable', 'integer'],
            ];
        }

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
            if ($this->has('rows')) {
                $codes = [];
                foreach ((array) $this->input('rows', []) as $index => $row) {
                    $code = trim((string) ($row['batch_code'] ?? ''));
                    if ($code === '') {
                        continue;
                    }
                    if (isset($codes[$code])) {
                        $validator->errors()->add('rows.' . $index . '.batch_code', translate('This Batch Code is repeated on this screen.'));
                    }
                    $codes[$code] = true;

                    $mfg = BatchMaster::normalizeMonth($row['manufacturing_date'] ?? null, false);
                    $exp = BatchMaster::normalizeMonth($row['expiry_date'] ?? null, true);
                    if ($mfg && $exp && $exp < $mfg) {
                        $validator->errors()->add('rows.' . $index . '.expiry_date', translate('Expiry month cannot be before manufacturing month.'));
                    }
                }

                return;
            }

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
            'rows.required' => translate('Add at least one batch row.'),
            'rows.*.batch_code.required' => translate('Please enter Batch Code.'),
        ];
    }
}
