<?php

namespace App\Http\Requests;

use App\Models\CompanyConfiguration;
use App\Models\CompanyFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CompanyConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $fields = [
            'code',
            'full_address',
            'district',
            'post',
            'village',
            'pincode',
            'contact_person',
            'designation',
            'mobile',
            'whatsapp',
            'email',
            'company_type',
            'company_type_manual',
            'security_password',
        ];

        $data = [];
        foreach ($fields as $field) {
            $value = trim((string) $this->input($field));
            $data[$field] = $value === '' ? null : $value;
        }

        if (($data['company_type'] ?? null) === '__not_in_list__') {
            $data['company_type'] = $data['company_type_manual'];
        }
        unset($data['company_type_manual']);

        $data['company_name'] = CompanyConfiguration::BILLING_NAME;

        if (CompanyFile::tableReady()) {
            $data['certificates'] = CompanyFile::normalizeRows($this->input('certificates'));
            $data['documents'] = CompanyFile::normalizeRows($this->input('documents'));
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $existing = CompanyConfiguration::tableReady() ? CompanyConfiguration::current() : null;

        $rules = [
            'code' => [
                'required',
                'string',
                'max:50',
            ],
            'company_name' => ['required', 'string', 'max:255'],
            'full_address' => ['required', 'string', 'max:5000'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s.]+$/'],
            'whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s.]+$/'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'company_type' => ['required', 'string', 'max:100'],
            'logo' => ['nullable', 'integer', 'exists:uploads,id'],
            'stamp' => ['nullable', 'integer', 'exists:uploads,id'],
            'sign' => ['nullable', 'integer', 'exists:uploads,id'],
            'deal_in_category_ids' => ['required', 'array', 'min:1'],
            'deal_in_category_ids.*' => ['required', 'integer', 'distinct', 'exists:categories,id'],
            'security_password' => ['required', 'string', 'max:255'],
        ];

        if (CompanyFile::tableReady()) {
            foreach (['certificates', 'documents'] as $key) {
                $rules[$key] = ['nullable', 'array'];
                $rules[$key . '.*.name'] = ['required', 'string', 'max:255'];
                $rules[$key . '.*.valid_until'] = ['nullable', 'date'];
                $rules[$key . '.*.upload_id'] = ['required', 'integer', 'exists:uploads,id'];
            }
        }

        if (CompanyConfiguration::tableReady()) {
            $rules['code'][] = Rule::unique('company_configurations', 'code')->ignore($existing);
            $rules['country_id'] = ['required', 'integer', 'exists:countries,id'];
            $rules['state_id'] = ['required', 'integer', 'exists:states,id'];
            $rules['city_id'] = ['required', 'integer', 'exists:cities,id'];
            $rules['district'] = ['required', 'string', 'max:255'];
            $rules['post'] = ['required', 'string', 'max:255'];
            $rules['village'] = ['required', 'string', 'max:255'];
            $rules['pincode'] = ['required', 'string', 'max:20'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $expected = (string) config('app.billing_company_password');
            $given = (string) $this->input('security_password');

            if ($expected === '') {
                $validator->errors()->add(
                    'security_password',
                    translate('Billing company password is not configured.')
                );

                return;
            }

            if ($given === '') {
                return;
            }

            $matches = strlen($expected) === strlen($given) && hash_equals($expected, $given);
            if (!$matches) {
                $validator->errors()->add(
                    'security_password',
                    translate('Security password is incorrect.')
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'deal_in_category_ids.required' => translate('Please select at least one Deal In Category.'),
            'deal_in_category_ids.min' => translate('Please select at least one Deal In Category.'),
        ];
    }
}
