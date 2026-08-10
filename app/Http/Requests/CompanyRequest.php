<?php

namespace App\Http\Requests;

use App\Models\UserDetails;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $fields = [
            'code',
            'company_name',
            'full_address',
            'contact_person',
            'designation',
            'mobile',
            'whatsapp',
            'email',
            'company_type',
        ];

        $data = [];
        foreach ($fields as $field) {
            $value = trim((string) $this->input($field));
            $data[$field] = $value === '' ? null : $value;
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $company = $this->route('company');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('companies', 'code')->ignore($company),
            ],
            'company_name' => ['required', 'string', 'max:255'],
            'full_address' => ['required', 'string', 'max:5000'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s.]+$/'],
            'whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s.]+$/'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'company_type' => ['required', Rule::in(UserDetails::CUSTOMER_TYPES)],
            'logo' => ['nullable', 'integer', 'exists:uploads,id'],
            'stamp' => ['nullable', 'integer', 'exists:uploads,id'],
            'sign' => ['nullable', 'integer', 'exists:uploads,id'],
            'deal_in_category_ids' => ['required', 'array', 'min:1'],
            'deal_in_category_ids.*' => ['required', 'integer', 'distinct', 'exists:categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'deal_in_category_ids.required' => translate('Please select at least one Deal In Category.'),
            'deal_in_category_ids.min' => translate('Please select at least one Deal In Category.'),
        ];
    }
}
