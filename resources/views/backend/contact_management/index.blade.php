@extends('backend.layouts.app')

@section('content')
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
        @php
            $filtersApplied = collect($filters)->filter(function ($value) {
                return $value !== null && $value !== '';
            })->isNotEmpty();
        @endphp
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
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="contactFilterModalLabel">{{ translate('Filter Contacts') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row gutters-5">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="contact_search">{{ translate('Search') }}</label>
                                <input type="text" id="contact_search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ translate('Contact no / name / company / email / phone / WhatsApp') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="contact_tags">{{ translate('Tags') }}</label>
                                <input type="text" id="contact_tags" name="tags" class="form-control" value="{{ $filters['tags'] ?? '' }}" placeholder="{{ translate('Tag') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="contact_group_id">{{ translate('Group') }}</label>
                                <select id="contact_group_id" name="group_id" class="form-control aiz-selectpicker" data-live-search="true">
                                    <option value="">{{ translate('All Groups') }}</option>
                                    @foreach ($groups as $group)
                                        <option value="{{ $group->id }}" @selected((string) ($filters['group_id'] ?? '') === (string) $group->id)>{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="contact_industry_id">{{ translate('Industry') }}</label>
                                <select id="contact_industry_id" name="industry_id" class="form-control aiz-selectpicker" data-live-search="true">
                                    <option value="">{{ translate('All Industries') }}</option>
                                    @foreach ($industries as $industry)
                                        <option value="{{ $industry->id }}" @selected((string) ($filters['industry_id'] ?? '') === (string) $industry->id)>{{ $industry->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="contact_department_id">{{ translate('Department') }}</label>
                                <select id="contact_department_id" name="department_id" class="form-control aiz-selectpicker" data-live-search="true">
                                    <option value="">{{ translate('All Departments') }}</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $department->id)>{{ $department->name }}</option>
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
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ translate('Contact No') }}</th>
                        <th>{{ translate('Name') }}</th>
                        <th>{{ translate('Company') }}</th>
                        <th>{{ translate('Group') }}</th>
                        <th>{{ translate('Industry') }}</th>
                        <th>{{ translate('Created') }}</th>
                        <th>{{ translate('Updated By') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contacts as $key => $contact)
                        @php
                            $phoneHref = $contact->phone ? preg_replace('/\s+/', '', $contact->phone) : null;
                            $whatsappHref = $contact->whatsapp_number ? preg_replace('/\D+/', '', $contact->whatsapp_number) : null;
                        @endphp
                        <tr>
                            <td>{{ $contacts->firstItem() + $key }}</td>
                            <td class="fw-700">{{ $contact->contact_no }}</td>
                            <td>
                                <div>{{ $contact->name }}</div>
                                @if ($contact->email)
                                    <small class="d-block text-muted">
                                        <a href="mailto:{{ $contact->email }}" class="text-muted">{{ $contact->email }}</a>
                                    </small>
                                @endif
                                @if ($contact->phone)
                                    <small class="d-block text-muted">
                                        {{ translate('Phone') }}:
                                        <a href="tel:{{ $phoneHref }}" class="text-muted">{{ $contact->phone }}</a>
                                    </small>
                                @endif
                                @if ($contact->whatsapp_number && $whatsappHref)
                                    <small class="d-block text-muted">
                                        {{ translate('WhatsApp') }}:
                                        <a href="https://wa.me/{{ $whatsappHref }}" class="text-muted" target="_blank" rel="noopener">{{ $contact->whatsapp_number }}</a>
                                    </small>
                                @endif
                            </td>
                            <td>{{ $contact->company_name ?? '-' }}</td>
                            <td>{{ optional($contact->group)->name ?? '-' }}</td>
                            <td>{{ optional($contact->industry)->name ?? '-' }}</td>
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
                            <td colspan="9" class="text-center">{{ translate('No contacts found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
