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

        $sortUrl = function (string $column) use ($sortBy, $sortOrder) {
            return url()->current() . '?' . http_build_query(array_merge(
                request()->except(['page', 'sort_by', 'sort_order']),
                [
                    'sort_by' => $column,
                    'sort_order' => $sortBy === $column && $sortOrder === 'asc' ? 'desc' : 'asc',
                ]
            ));
        };

        $sortIcon = function (string $column) use ($sortBy, $sortOrder) {
            if ($sortBy !== $column) {
                return 'la-sort text-muted';
            }

            return $sortOrder === 'asc' ? 'la-sort-amount-up' : 'la-sort-amount-down';
        };
    @endphp

    <style>
        .company-address-column {
            min-width: 230px;
            max-width: 330px;
            white-space: normal;
        }
        .company-category-list {
            min-width: 240px;
            white-space: normal;
        }
        .company-category-list .badge {
            white-space: normal;
            text-align: left;
            line-height: 1.4;
        }
        .company-sort-link {
            color: inherit;
            white-space: nowrap;
        }
    </style>

    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Company Master') }}</h1>
            </div>
            <div class="col-md-6 text-md-right">
                @can('add_customer')
                    <a href="{{ route('companies.create') }}" class="btn btn-circle btn-info">
                        {{ translate('Add New Company') }}
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="card">
        <form action="{{ route('companies.index') }}" method="GET">
            <div class="card-header">
                <div class="row gutters-5 align-items-end w-100">
                    <div class="col-lg-3 col-md-6">
                        <label class="form-label" for="search">{{ translate('Search') }}</label>
                        <input type="text" class="form-control form-control-sm" id="search" name="search"
                            value="{{ $filters['search'] }}"
                            placeholder="{{ translate('Code, name, address, contact, mobile, e-mail or category') }}">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label" for="company_type">{{ translate('Company Type') }}</label>
                        <select class="form-control form-control-sm aiz-selectpicker" id="company_type"
                            name="company_type" data-live-search="true">
                            <option value="">{{ translate('All Company Types') }}</option>
                            @foreach ($companyTypes as $companyType)
                                <option value="{{ $companyType }}" @selected($filters['company_type'] === $companyType)>
                                    {{ translate($companyType) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label" for="category_id">{{ translate('Deal In Category') }}</label>
                        <select class="form-control form-control-sm aiz-selectpicker" id="category_id"
                            name="category_id" data-live-search="true">
                            <option value="">{{ translate('All Categories') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    @selected($filters['category_id'] === (string) $category->id)>
                                    {{ $categoryPath($category) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label" for="sort_by">{{ translate('Sort By') }}</label>
                        <select class="form-control form-control-sm aiz-selectpicker" id="sort_by" name="sort_by">
                            @foreach ([
                                'created_at' => 'Created Date',
                                'code' => 'Code',
                                'company_name' => 'Company Name',
                                'company_type' => 'Company Type',
                                'contact_person' => 'Contact Person',
                                'deal_in_category' => 'Deal In Category',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected($sortBy === $value)>{{ translate($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-1 col-md-3">
                        <label class="form-label" for="sort_order">{{ translate('Order') }}</label>
                        <select class="form-control form-control-sm" id="sort_order" name="sort_order">
                            <option value="asc" @selected($sortOrder === 'asc')>{{ translate('Ascending') }}</option>
                            <option value="desc" @selected($sortOrder === 'desc')>{{ translate('Descending') }}</option>
                        </select>
                    </div>
                    <div class="col-lg-1 col-md-3">
                        <label class="form-label" for="date_from">{{ translate('From') }}</label>
                        <input type="date" class="form-control form-control-sm" id="date_from" name="date_from"
                            value="{{ $filters['date_from'] }}">
                    </div>
                    <div class="col-lg-1 col-md-3">
                        <label class="form-label" for="date_to">{{ translate('To') }}</label>
                        <input type="date" class="form-control form-control-sm" id="date_to" name="date_to"
                            value="{{ $filters['date_to'] }}">
                    </div>
                    <div class="col-auto mt-2">
                        <button type="submit" class="btn btn-sm btn-primary">{{ translate('Filter') }}</button>
                        <a href="{{ route('companies.index') }}" class="btn btn-sm btn-soft-secondary">
                            {{ translate('Reset') }}
                        </a>
                    </div>
                </div>
            </div>
        </form>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            @foreach ([
                                'id' => 'Sr No.',
                                'code' => 'Code',
                                'company_name' => 'Company Name',
                                'full_address' => 'Full Address',
                                'contact_person' => 'Contact Person',
                                'designation' => 'Designation',
                                'mobile' => 'Mobile',
                                'whatsapp' => 'WhatsApp',
                                'email' => 'E-mail',
                                'company_type' => 'Company Type',
                                'deal_in_category' => 'Deal In Category',
                                'created_at' => 'Created Date',
                            ] as $column => $label)
                                <th>
                                    <a class="company-sort-link" href="{{ $sortUrl($column) }}">
                                        {{ translate($label) }}
                                        <i class="las {{ $sortIcon($column) }}"></i>
                                    </a>
                                </th>
                            @endforeach
                            <th class="text-right">{{ translate('Options') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companies as $key => $company)
                            <tr>
                                <td>{{ $companies->firstItem() + $key }}</td>
                                <td>{{ $company->code }}</td>
                                <td>
                                    <a href="{{ route('companies.show', $company) }}" class="text-reset font-weight-bold">
                                        {{ $company->company_name }}
                                    </a>
                                </td>
                                <td class="company-address-column">{{ $company->full_address }}</td>
                                <td>{{ $company->contact_person ?: '-' }}</td>
                                <td>{{ $company->designation ?: '-' }}</td>
                                <td>
                                    @if ($company->mobile)
                                        <a href="tel:{{ $company->mobile }}">{{ $company->mobile }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $company->whatsapp ?: '-' }}</td>
                                <td>
                                    @if ($company->email)
                                        <a href="mailto:{{ $company->email }}">{{ $company->email }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $company->company_type }}</td>
                                <td class="company-category-list">
                                    @forelse ($company->categories->sortBy(fn ($category) => $categoryPath($category)) as $category)
                                        <span class="badge badge-inline badge-soft-info mb-1">
                                            {{ $categoryPath($category) }}
                                        </span>
                                    @empty
                                        -
                                    @endforelse
                                </td>
                                <td>{{ optional($company->created_at)->format('d M Y') }}</td>
                                <td class="text-right">
                                    <a class="btn btn-soft-info btn-icon btn-circle btn-sm"
                                        href="{{ route('companies.show', $company) }}" title="{{ translate('View') }}">
                                        <i class="las la-eye"></i>
                                    </a>
                                    @can('view_all_customers')
                                        <a class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                            href="{{ route('companies.edit', $company) }}" title="{{ translate('Edit') }}">
                                            <i class="las la-edit"></i>
                                        </a>
                                    @endcan
                                    @can('delete_customer')
                                        <form action="{{ route('companies.destroy', $company) }}" method="POST"
                                            class="d-inline company-delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="btn btn-soft-danger btn-icon btn-circle btn-sm"
                                                title="{{ translate('Delete') }}">
                                                <i class="las la-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center">{{ translate('No companies found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="aiz-pagination">
                {{ $companies->links() }}
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $('.company-delete-form').on('submit', function (event) {
            if (!window.confirm(@json(translate('Are you sure you want to delete this company?')))) {
                event.preventDefault();
            }
        });
    </script>
@endsection
