<?php

namespace App\Http\Requests;

use App\Models\DiscountMaster;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DiscountMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $type = (string) $this->input('discount_type');
        $applied = [
            'productwise' => 'sku',
            'batchwise' => 'batch',
            'schemewise' => 'batch',
            'amount_wise' => 'invoice',
            'pointwise' => 'invoice',
            'couponwise' => 'invoice',
        ][$type] ?? '';

        $roles = $this->input('roles', []);
        $slab = $roles[0]['slabs'][0] ?? [];
        $amounts = $this->input('amounts', []);
        $amountRow = $amounts[0] ?? [];
        $batchIds = array_values(array_filter((array) $this->input('batch_ids', [])));

        $merge = [
            'applied_on' => $applied,
            'status' => $this->boolean('status'),
            'same_discount' => $this->boolean('same_discount'),
            'near_expiry' => $this->boolean('near_expiry'),
        ];

        if (in_array($type, ['productwise', 'batchwise'], true)) {
            $merge['role_key'] = $roles[0]['role_key'] ?? null;
            $merge['qty_slab_from'] = $slab['qty'] ?? null;
            $merge['qty_slab_to'] = null;
            $merge['value_type'] = $slab['value_type'] ?? 'flat';
            $merge['value_amount'] = $slab['amount'] ?? null;
            $merge['value_percent'] = $slab['percent'] ?? null;
            $merge['rate'] = $slab['rate'] ?? null;
            $merge['amount'] = $slab['line_amount'] ?? null;
            $merge['effective_rate'] = $slab['effective_rate'] ?? null;
        }

        if ($type === 'schemewise') {
            $merge['role_key'] = $roles[0]['role_key'] ?? null;
            $merge['qty_slab_from'] = $slab['qty'] ?? null;
            $merge['qty_slab_to'] = null;
            $merge['rate'] = $slab['rate'] ?? null;
            $merge['amount'] = $slab['line_amount'] ?? null;
            $merge['effective_rate'] = $slab['effective_rate'] ?? null;
            $merge['scheme_free_qty'] = $slab['free_qty'] ?? null;
            $merge['scheme_percent'] = $slab['scheme_percent'] ?? null;
            $merge['scheme_value'] = $slab['scheme_value'] ?? null;
            $merge['value_type'] = null;
            $merge['value_amount'] = null;
            $merge['value_percent'] = null;
        }

        if (in_array($type, DiscountMaster::INVOICE_TYPES, true)) {
            $merge['invoice_amount'] = $amountRow['to'] ?? $amountRow['slab'] ?? null;
            $merge['qty_slab_from'] = $amountRow['from'] ?? null;
            $merge['qty_slab_to'] = $amountRow['to'] ?? null;
            $merge['value_type'] = $amountRow['value_type'] ?? 'flat';
            $merge['value_percent'] = $amountRow['percent'] ?? null;
            $merge['value_amount'] = $type === 'pointwise' ? null : ($amountRow['amount'] ?? null);
            $merge['earn'] = $type === 'pointwise' ? ($amountRow['amount'] ?? null) : null;
            $merge['rate'] = null;
            $merge['amount'] = $amountRow['amount'] ?? null;
            $merge['effective_rate'] = null;
            $merge['role_key'] = $this->input('scope_role') ?: null;
        }

        if ($batchIds) {
            $merge['batch_id'] = $batchIds[0];
        }

        if ($this->has('scheme_product_is_same')) {
            $merge['scheme_product_is_same'] = $this->boolean('scheme_product_is_same');
        }

        $nullable = [
            'product_id', 'product_stock_id', 'batch_id', 'category_id', 'group_id', 'customer_id',
            'role_key', 'qty_slab_from', 'qty_slab_to', 'rate', 'amount', 'effective_rate',
            'value_type', 'value_amount', 'value_percent', 'earn', 'invoice_amount',
            'scheme_free_qty', 'scheme_percent', 'scheme_value', 'scheme_product_stock_id',
            'from_date', 'to_date', 'coupon_code', 'scope_role',
        ];
        foreach ($nullable as $field) {
            $value = array_key_exists($field, $merge) ? $merge[$field] : $this->input($field);
            if ($value === '' || $value === null) {
                $merge[$field] = null;
            }
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        $type = (string) $this->input('discount_type');

        $rules = [
            'applied_on' => ['required', Rule::in(array_keys(DiscountMaster::APPLIED_ON))],
            'discount_type' => ['required', Rule::in(array_keys(DiscountMaster::DISCOUNT_TYPES))],
            'product_id' => ['nullable', 'integer'],
            'product_stock_id' => ['nullable', 'integer'],
            'batch_id' => ['nullable', 'integer'],
            'batch_ids' => ['nullable', 'array'],
            'batch_ids.*' => ['integer'],
            'category_id' => ['nullable', 'integer'],
            'group_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'role_key' => ['nullable', Rule::in(array_keys(DiscountMaster::ROLE_KEYS))],
            'scope_role' => ['nullable', Rule::in(array_keys(DiscountMaster::ROLE_KEYS))],
            'qty_slab_from' => ['nullable', 'numeric', 'min:0'],
            'qty_slab_to' => ['nullable', 'numeric', 'min:0'],
            'rate' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'effective_rate' => ['nullable', 'numeric', 'min:0'],
            'value_type' => ['nullable', Rule::in(['flat', 'percent'])],
            'value_amount' => ['nullable', 'numeric', 'min:0'],
            'value_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'earn' => ['nullable', 'numeric', 'min:0'],
            'invoice_amount' => ['nullable', 'numeric', 'min:0'],
            'scheme_free_qty' => ['nullable', 'numeric', 'min:0'],
            'scheme_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scheme_value' => ['nullable', 'numeric', 'min:0'],
            'scheme_product_is_same' => ['nullable', 'boolean'],
            'scheme_product_stock_id' => ['nullable', 'integer'],
            'from_date' => ['required', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'status' => ['nullable', 'boolean'],
            'same_discount' => ['nullable', 'boolean'],
            'near_expiry' => ['nullable', 'boolean'],
            'coupon_code' => ['nullable', 'string', 'max:80'],
            'roles' => ['nullable', 'array'],
            'amounts' => ['nullable', 'array'],
            'scope_products' => ['nullable', 'array'],
            'scope_products.*' => ['integer'],
            'product_label' => ['nullable', 'string', 'max:255'],
        ];

        if ($type === '') {
            $rules['applied_on'] = ['nullable'];
        }

        if (in_array($type, DiscountMaster::PRODUCT_TYPES, true)) {
            $rules['product_stock_id'] = ['required', 'integer'];
        }
        if (in_array($type, DiscountMaster::BATCH_TYPES, true)) {
            $rules['batch_id'] = ['required', 'integer'];
        }
        if (in_array($type, ['productwise', 'batchwise'], true)) {
            $rules['value_type'] = ['required', Rule::in(['flat', 'percent'])];
            if ($this->input('value_type') === 'percent') {
                $rules['value_percent'] = ['required', 'numeric', 'min:0', 'max:100'];
            } else {
                $rules['value_amount'] = ['required', 'numeric', 'min:0'];
            }
        }
        if ($type === 'schemewise') {
            $rules['scheme_free_qty'] = ['required', 'numeric', 'min:0'];
            $rules['qty_slab_from'] = ['required', 'numeric', 'min:0.001'];
            if ($this->has('scheme_product_is_same') && !$this->boolean('scheme_product_is_same')) {
                $rules['scheme_product_stock_id'] = ['required', 'integer'];
            }
        }
        if (in_array($type, DiscountMaster::INVOICE_TYPES, true)) {
            $rules['value_type'] = ['required', Rule::in(['flat', 'percent'])];
            $rules['amounts'] = ['required', 'array', 'min:1'];
            if ($this->input('value_type') === 'percent') {
                $rules['value_percent'] = ['required', 'numeric', 'min:0', 'max:100'];
            } elseif ($type === 'pointwise') {
                $rules['earn'] = ['required', 'numeric', 'min:0'];
            } else {
                $rules['value_amount'] = ['required', 'numeric', 'min:0'];
            }
        }
        if ($type === 'couponwise') {
            $rules['coupon_code'] = ['required', 'string', 'max:80'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'discount_type.required' => translate('Please select Discount Type.'),
            'product_stock_id.required' => translate('Please select a product.'),
            'batch_id.required' => translate('Please select a Batch / Lot No.'),
            'from_date.required' => translate('Please select From Date.'),
            'to_date.after_or_equal' => translate('To Date cannot be before From Date.'),
            'scheme_product_stock_id.required' => translate('Please select the scheme product.'),
            'scheme_free_qty.required' => translate('Please fill the scheme free quantity.'),
            'coupon_code.required' => translate('Please enter the coupon number.'),
            'value_amount.required' => translate('Please fill the amount.'),
            'value_percent.required' => translate('Please fill the percent.'),
            'earn.required' => translate('Please fill the points.'),
        ];
    }
}
