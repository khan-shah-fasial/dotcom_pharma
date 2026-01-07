@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Financial Archive') }}</h1>
                <p class="text-muted mb-0">
                    {{ translate('Manage archives for') }}:
                    <strong>{{ $user->name }}</strong>
                    @if(optional($user->details)->company_name)
                        <span class="ml-2 text-secondary">| {{ optional($user->details)->company_name }}</span>
                    @endif
                </p>
            </div>
            <div class="col-md-6 text-md-right">
                <a href="{{ route('customers.business') }}" class="btn btn-soft-secondary">
                    {{ translate('Back to Business Customers') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{ translate('Add New Archive') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('financial-archives.customer.store', $user->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                        <div class="form-group">
                            <label class="form-label">{{ translate('Type') }}</label>
                            <select name="type" class="form-control aiz-selectpicker" required>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}">{{ translate($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ translate('Attachment') }}</label>
                            <input type="file" name="file" class="form-control" required
                                accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.svg,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.xml,.zip,.rar,.7z,image/*">
                            <small class="text-muted d-block mt-1">{{ translate('Images and documents are accepted.') }}</small>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex w-100 align-items-center justify-content-between">
                        <h5 class="mb-0 h6">{{ translate('Archive List') }}</h5>
                        <form class="form-inline" method="GET">
                            <div class="form-group mr-2 mb-0">
                                <select name="type" class="form-control aiz-selectpicker" data-live-search="true">
                                    <option value="">{{ translate('All Types') }}</option>
                                    @foreach ($types as $value => $label)
                                        <option value="{{ $value }}" @selected($filterType == $value)>{{ translate($label) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mr-2 mb-0">
                                <input type="text" name="search" class="form-control" value="{{ $filterSearch }}" placeholder="{{ translate('Search by file name') }}">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">{{ translate('Filter') }}</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table aiz-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ translate('Type') }}</th>
                                <th>{{ translate('File') }}</th>
                                <th>{{ translate('Created At') }}</th>
                                <th class="text-right">{{ translate('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($archives as $key => $archive)
                                <tr>
                                    <td>{{ $key + 1 + ($archives->currentPage() - 1) * $archives->perPage() }}</td>
                                    <td>{{ translate($types[$archive->type] ?? $archive->type) }}</td>
                                    <td>
                                        @if ($archive->upload)
                                            <a href="{{ uploaded_asset($archive->upload_id) }}" target="_blank" rel="noopener">
                                                {{ $archive->upload->file_original_name ?? translate('View File') }}
                                                @if ($archive->upload->extension)
                                                    .{{ $archive->upload->extension }}
                                                @endif
                                            </a>
                                        @else
                                            <span class="text-danger">{{ translate('File missing') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $archive->created_at?->format('d M Y') }}</td>
                                    <td class="text-right">
                                        <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete"
                                            data-href="{{ route('financial-archives.destroy', $archive->id) }}"
                                            title="{{ translate('Delete') }}">
                                            <i class="las la-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">{{ translate('No archives found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="aiz-pagination">
                        {{ $archives->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection
