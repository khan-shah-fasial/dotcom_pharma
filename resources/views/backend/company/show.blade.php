@extends('backend.layouts.app')

@section('content')
    @php
        $categoryById = $categories->keyBy('id');
        $categoryPath = function ($category) use ($categoryById) {
            $path = collect();
            $seen = [];

            while ($category && !in_array((int) $category->id, $seen, true)) {
                $seen[] = (int) $category->id;
                $path->prepend($category->getTranslation('name'));
                $category = $category->parent_id ? $categoryById->get($category->parent_id) : null;
            }

            return $path->implode(' > ');
        };
    @endphp

    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ $company->company_name }}</h1>
            </div>
            <div class="col-md-6 text-md-right">
                <a href="{{ route('companies.index') }}" class="btn btn-soft-secondary">
                    {{ translate('Company Master') }}
                </a>
                @can('view_all_customers')
                    <a href="{{ route('companies.edit', $company) }}" class="btn btn-primary">
                        {{ translate('Edit Company') }}
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Company Details') }}</h5>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach ([
                    'Code' => $company->code,
                    'Company Name' => $company->company_name,
                    'Company Type' => $company->company_type,
                    'Contact Person' => $company->contact_person,
                    'Designation' => $company->designation,
                    'Mobile' => $company->mobile,
                    'WhatsApp' => $company->whatsapp,
                    'E-mail' => $company->email,
                ] as $label => $value)
                    <div class="col-md-6 mb-3">
                        <div class="text-muted fs-12">{{ translate($label) }}</div>
                        <div class="font-weight-bold">{{ $value ?: '-' }}</div>
                    </div>
                @endforeach

                <div class="col-12 mb-3">
                    <div class="text-muted fs-12">{{ translate('Full Address') }}</div>
                    <div class="font-weight-bold" style="white-space: pre-line;">{{ $company->full_address }}</div>
                </div>

                <div class="col-12 mb-3">
                    <div class="text-muted fs-12">{{ translate('Deal In Category') }}</div>
                    <div class="mt-2">
                        @forelse ($company->categories->sortBy(fn ($category) => $categoryPath($category)) as $category)
                            <span class="badge badge-inline badge-soft-info mb-1">
                                {{ $categoryPath($category) }}
                            </span>
                        @empty
                            -
                        @endforelse
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted fs-12">{{ translate('Created By') }}</div>
                    <div>{{ optional($company->creator)->name ?: '-' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted fs-12">{{ translate('Created Date') }}</div>
                    <div>{{ optional($company->created_at)->format('d M Y, h:i A') ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
