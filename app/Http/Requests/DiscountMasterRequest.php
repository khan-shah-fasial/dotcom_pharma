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
        $nullable = [
            'product_id',
            'product_stock_id',
            'batch_id',
            'category_id',
            'group_id',
            'customer_id',
            'role_key',
            'qty_slab_from',
            'qty_slab_to',
            'rate',
            'amount',
            'effective_rate',
            'value_type',
            'value_amount',
            'value_percent',
            'earn',
            'invoice_amount',
            'scheme_free_qty',
            'scheme_percent',
            'scheme_value',
            'scheme_product_stock_id',
            'from_date',
            'to_date',
        ];

        $data = [];
        foreach ($nullable as $field) {
            $value = $this->input($field);
            if ($value === '' || $value === null) {
                $data[$field] = null;
            }
        }

        if ($this->has('scheme_product_is_same')) {
            $data['scheme_product_is_same'] = $this->boolean('scheme_product_is_same');
        }

        $data['status'] = $this->boolean('status');

        $this->merge($data);
    }

    public function rules(): array
    {
        $appliedOn = (string) $this->input('applied_on');
        $discountType = (string) $this->input('discount_type');

        $rules = [
            'applied_on' => ['required', Rule::in(array_keys(DiscountMaster::APPLIED_ON))],
            'discount_type' => ['required', Rule::in(array_keys(DiscountMaster::DISCOUNT_TYPES))],
            'product_id' => ['nullable', 'integer'],
            'product_stock_id' => ['nullable', 'integer'],
            'batch_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'group_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'role_key' => ['nullable', Rule::in(array_keys(DiscountMaster::ROLE_KEYS))],
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
        ];

        if (in_array($appliedOn, ['sku', 'full_variant'], true)) {
            $rules['product_stock_id'] = ['required', 'integer'];
        } elseif ($appliedOn === 'batch') {
            $rules['batch_id'] = ['required', 'integer'];
        } elseif ($appliedOn === 'category') {
            $rules['category_id'] = ['required', 'integer'];
        } elseif ($appliedOn === 'group') {
            $rules['group_id'] = ['required', 'integer'];
        } elseif ($appliedOn === 'customer') {
            $rules['customer_id'] = ['required', 'integer'];
        }

        if (in_array($discountType, ['batchwise', 'productwise', 'amount_wise'], true)) {
            $rules['value_type'] = ['required', Rule::in(['flat', 'percent'])];
            if ($this->input('value_type') === 'percent') {
                $rules['value_percent'] = ['required', 'numeric', 'min:0', 'max:100'];
            } else {
                $rules['value_amount'] = ['required', 'numeric', 'min:0'];
            }
        }

        if ($discountType === 'pointwise') {
            $rules['earn'] = ['required', 'numeric', 'min:0'];
        }

        if ($discountType === 'amount_wise') {
            $rules['invoice_amount'] = ['required', 'numeric', 'min:0'];
        }

        if ($discountType === 'schemewise') {
            $rules['scheme_free_qty'] = ['nullable', 'numeric', 'min:0'];
            $rules['scheme_percent'] = ['nullable', 'numeric', 'min:0', 'max:100'];
            $rules['scheme_value'] = ['nullable', 'numeric', 'min:0'];
            if ($this->has('scheme_product_is_same') && !$this->boolean('scheme_product_is_same')) {
                $rules['scheme_product_stock_id'] = ['required', 'integer'];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'applied_on.required' => translate('Please select Discount Applied On.'),
            'discount_type.required' => translate('Please select Discount Type.'),
            'product_stock_id.required' => translate('Please select a SKU or Full Variant.'),
            'batch_id.required' => translate('Please select a Batch / Lot.No.'),
            'category_id.required' => translate('Please select Category (Main).'),
            'group_id.required' => translate('Please select Group.'),
            'customer_id.required' => translate('Please select Customer Name.'),
            'from_date.required' => translate('Please select From Date.'),
            'to_date.after_or_equal' => translate('To Date cannot be before From Date.'),
            'scheme_product_stock_id.required' => translate('Please select the scheme product.'),
        ];
    }
}
