@extends('frontend.layouts.user_panel')

@section('panel_content')
    <div class="card shadow-none rounded-0 border">
        <div class="card-header border-bottom-0">
            <div class="d-flex flex-wrap align-items-center justify-content-between w-100">
                <h5 class="mb-0 fs-20 fw-700 text-dark">{{ translate('Financial Archive') }}</h5>
                <form class="form-inline" method="GET">
                    <div class="form-group mr-2 mb-2 mb-sm-0">
                        <select name="type" class="form-control aiz-selectpicker" data-live-search="true">
                            <option value="">{{ translate('All Types') }}</option>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected($filterType == $value)>{{ translate($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mr-2 mb-2 mb-sm-0">
                        <input type="text" name="search" class="form-control" value="{{ $filterSearch }}" placeholder="{{ translate('Search by file name') }}">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">{{ translate('Filter') }}</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead class="text-gray fs-12">
                    <tr>
                        <th class="pl-0">{{ translate('Type') }}</th>
                        <th>{{ translate('File') }}</th>
                        <th data-breakpoints="md">{{ translate('Created At') }}</th>
                    </tr>
                </thead>
                <tbody class="fs-14">
                    @forelse ($archives as $archive)
                        <tr>
                            <td class="pl-0">{{ translate($types[$archive->type] ?? $archive->type) }}</td>
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">{{ translate('No archives found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination mt-2">
                {{ $archives->links() }}
            </div>
        </div>
    </div>
@endsection
