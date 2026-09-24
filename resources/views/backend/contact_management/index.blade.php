@extends('backend.layouts.app')

@section('content')
@php
    $sortBy = $sortBy ?? '';
    $sortDir = $sortDir ?? 'asc';
    $hasRegion = $hasRegion ?? false;
    $filtersApplied = collect($filters)->contains(function ($value) {
        return $value !== null && $value !== '';
    });
    $columns = [
        'name' => 'Full Name',
        'company_name' => 'Company Name',
        'designation' => 'Designation',
        'group' => 'Group',
        'category' => 'Category',
        'subcategory' => 'Subcategory',
        'type' => 'Type',
        'subject' => 'Subject',
        'industry' => 'Industry',
        'work_profile' => 'Work Profile',
        'department' => 'Department',
        'purpose' => 'Purpose',
        'phone' => 'Mobile',
        'whatsapp_number' => 'WhatsApp',
        'alternate_mobile_number' => 'Alternate Mobile',
        'email' => 'Email',
        'instagram' => 'Insta ID',
        'linkedin' => 'LinkedIn ID',
        'country' => 'Country',
        'state' => 'State',
    ];
    if ($hasRegion) {
        $columns['region'] = 'Region';
    }
    $columns += [
        'district' => 'District',
        'post' => 'Post',
        'tags' => 'Tags',
    ];
@endphp
<style>
    .contact-list-table { min-width: 2800px; }
    .contact-list-table th, .contact-list-table td { vertical-align: top; white-space: nowrap; }
    .contact-list-table .contact-wrap { white-space: normal; min-width: 140px; max-width: 220px; }
</style>

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">{{ translate('Contacts') }}</h1></div>
        <div class="col-md-6 text-md-right">
            @can('add_contact_directory')
                <a href="{{ route('contact-directory.create') }}" class="btn btn-circle btn-info">{{ translate('Add New Contact') }}</a>
            @endcan
        </div>
    </div>
</div>

<div class="card">
    <form id="sort_contacts" method="GET">
        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <div class="mb-2">
                <h5 class="mb-0 h6">{{ translate('Contact List') }}</h5>
                @if ($filtersApplied)
                    <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
                @endif
            </div>
            <div class="d-flex flex-wrap align-items-center">
                <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal" data-target="#contactFilterModal">
                    {{ translate('Open Filters') }}
                </button>
                <a href="{{ route('contact-directory.index') }}" class="btn btn-danger mb-2">{{ translate('Reset') }}</a>
            </div>
        </div>

        <div class="modal fade" id="contactFilterModal" tabindex="-1" role="dialog" aria-labelledby="contactFilterModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="contactFilterModalLabel">{{ translate('Filter Contacts') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row gutters-5">
                            @foreach ([
                                'name' => 'Full Name',
                                'company_name' => 'Company Name',
                                'designation' => 'Designation',
                                'phone' => 'Mobile',
                                'whatsapp_number' => 'WhatsApp',
                                'alternate_mobile_number' => 'Alternate Mobile',
                                'email' => 'Email',
                                'instagram' => 'Insta ID',
                                'linkedin' => 'LinkedIn ID',
                                'district' => 'District',
                                'post' => 'Post',
                                'tags' => 'Tags',
                            ] as $field => $label)
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="contact_filter_{{ $field }}">{{ translate($label) }}</label>
                                    <input type="text" id="contact_filter_{{ $field }}" name="{{ $field }}" class="form-control" value="{{ $filters[$field] ?? '' }}">
                                </div>
                            @endforeach
                            @if ($hasRegion)
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="contact_filter_region">{{ translate('Region') }}</label>
                                    <input type="text" id="contact_filter_region" name="region" class="form-control" value="{{ $filters['region'] ?? '' }}">
                                </div>
                            @endif
                            @foreach ([
                                'group_id' => ['Group', $groups],
                                'category_id' => ['Category', $categories],
                                'subcategory_id' => ['Subcategory', $subcategories],
                                'type_id' => ['Type', $types],
                                'subject_id' => ['Subject', $subjects],
                                'industry_id' => ['Industry', $industries],
                                'work_profile_id' => ['Work Profile', $workProfiles],
                                'department_id' => ['Department', $departments],
                                'purpose_id' => ['Purpose', $purposes],
                            ] as $field => [$label, $options])
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="contact_filter_{{ $field }}">{{ translate($label) }}</label>
                                    <select id="contact_filter_{{ $field }}" name="{{ $field }}" class="form-control aiz-selectpicker" data-live-search="true" data-container="body">
                                        <option value="">{{ translate('All') }}</option>
                                        @foreach ($options as $option)
                                            <option value="{{ $option->id }}" @selected((string) ($filters[$field] ?? '') === (string) $option->id)>{{ $option->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="contact_filter_country">{{ translate('Country') }}</label>
                                <select id="contact_filter_country" name="country_id" class="form-control aiz-selectpicker" data-live-search="true" data-container="body">
                                    <option value="">{{ translate('All') }}</option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->id }}" @selected((string) ($filters['country_id'] ?? '') === (string) $country->id)>{{ $country->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="contact_filter_state">{{ translate('State') }}</label>
                                <select id="contact_filter_state" name="state_id" class="form-control aiz-selectpicker" data-live-search="true" data-container="body">
                                    <option value="">{{ translate('All') }}</option>
                                    @foreach ($states as $state)
                                        <option value="{{ $state->id }}" @selected((string) ($filters['state_id'] ?? '') === (string) $state->id)>{{ $state->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Close') }}</button>
                        <button type="button" class="btn btn-primary btn-apply-contact-filters">{{ translate('Apply Filters') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
            <table class="table aiz-table mb-0 contact-list-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ translate('Contact No') }}</th>
                        @foreach ($columns as $column => $label)
                            @include('backend.inc.sortable_th', ['column' => $column, 'label' => translate($label), 'routeName' => 'contact-directory.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @endforeach
                        <th>{{ translate('Created') }}</th>
                        <th>{{ translate('Updated By') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contacts as $key => $contact)
                        <tr>
                            <td>{{ $contacts->firstItem() + $key }}</td>
                            <td class="fw-700">{{ $contact->contact_no }}</td>
                            <td class="contact-wrap">{{ $contact->name }}</td>
                            <td class="contact-wrap">{{ $contact->company_name ?: '-' }}</td>
                            <td>{{ $contact->designation ?: '-' }}</td>
                            <td>{{ optional($contact->group)->name ?? '-' }}</td>
                            <td>{{ optional($contact->category)->name ?? '-' }}</td>
                            <td>{{ optional($contact->subcategory)->name ?? '-' }}</td>
                            <td>{{ optional($contact->type)->name ?? '-' }}</td>
                            <td>{{ optional($contact->subject)->name ?? '-' }}</td>
                            <td>{{ optional($contact->industry)->name ?? '-' }}</td>
                            <td>{{ optional($contact->workProfile)->name ?? '-' }}</td>
                            <td>{{ optional($contact->department)->name ?? '-' }}</td>
                            <td>{{ optional($contact->purpose)->name ?? '-' }}</td>
                            <td>{{ $contact->phone ?: '-' }}</td>
                            <td>{{ $contact->whatsapp_number ?: '-' }}</td>
                            <td>{{ $contact->alternate_mobile_number ?: '-' }}</td>
                            <td>{{ $contact->email ?: '-' }}</td>
                            <td>{{ $contact->socialValue('instagram') ?: '-' }}</td>
                            <td>{{ $contact->socialValue('linkedin') ?: '-' }}</td>
                            <td>{{ optional($contact->country)->name ?? '-' }}</td>
                            <td>{{ optional($contact->state)->name ?? '-' }}</td>
                            @if ($hasRegion)
                                <td>{{ $contact->region ?: '-' }}</td>
                            @endif
                            <td>{{ $contact->district ?: '-' }}</td>
                            <td>{{ $contact->post ?: '-' }}</td>
                            <td class="contact-wrap">{{ empty($contact->tags) ? '-' : implode(', ', $contact->tags) }}</td>
                            <td>{{ $contact->created_at ? $contact->created_at->format('d-m-Y h:i A') : '-' }}</td>
                            <td>{{ optional($contact->updater)->name ?? optional($contact->creator)->name ?? '-' }}</td>
                            <td class="text-right">
                                <a href="{{ route('contact-directory.show', $contact->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm" title="{{ translate('View') }}">
                                    <i class="las la-eye"></i>
                                </a>
                                @can('edit_contact_directory')
                                    <a href="{{ route('contact-directory.edit', $contact->id) }}" class="btn btn-soft-info btn-icon btn-circle btn-sm" title="{{ translate('Edit') }}">
                                        <i class="las la-edit"></i>
                                    </a>
                                @endcan
                                @can('delete_contact_directory')
                                    <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('contact-directory.destroy', $contact->id) }}" title="{{ translate('Delete') }}">
                                        <i class="las la-trash"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 5 }}" class="text-center">{{ translate('No contacts found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            <div class="aiz-pagination">
                {{ $contacts->appends(request()->input())->links() }}
            </div>
        </div>
    </form>
</div>
@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection

@section('script')
<script>
    $('.btn-apply-contact-filters').on('click', function () {
        $('#contactFilterModal').modal('hide');
        $('#sort_contacts').submit();
    });
</script>
@endsection
