@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('All uploaded files') }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            @if($foldersReady ?? false)
                <button type="button" class="btn btn-circle btn-soft-warning mr-2 open-move-all">
                    <span>{{ translate('Move everything here') }}</span>
                </button>
                @if(!empty($currentFolder))
                    <a href="javascript:void(0)" class="btn btn-circle btn-soft-danger mr-2 confirm-delete"
                       data-href="{{ route('uploaded-files.folders.destroy', $currentFolder->id) }}?with_files=1"
                       data-message="{{ translate('Delete this folder and every file and folder inside it? Files that are in use will be kept.') }}">
                        <span>{{ translate('Delete folder and files') }}</span>
                    </a>
                @endif
                <button type="button" class="btn btn-circle btn-soft-primary mr-2" data-toggle="modal" data-target="#create-folder-modal">
                    <span>{{ translate('New Folder') }}</span>
                </button>
            @endif
            <a href="{{ route('uploaded-files.create', !empty($currentFolder) ? ['folder' => $currentFolder->id] : []) }}" class="btn btn-circle btn-info">
                <span>{{ translate('Upload New File') }}</span>
            </a>
        </div>
    </div>
</div>

@if($foldersReady ?? false)
    @php
        $folderLink = function ($folderId = null) {
            $query = request()->except(['page', 'folder']);
            if ($folderId) {
                $query['folder'] = $folderId;
            }
            $base = route('uploaded-files.index');
            return $query ? $base . '?' . http_build_query($query) : $base;
        };
    @endphp
    <nav aria-label="{{ translate('Folders') }}" class="mb-3">
        <ol class="breadcrumb bg-white mb-0 py-2 px-3">
            <li class="breadcrumb-item {{ empty($currentFolder) ? 'active' : '' }}">
                @if(empty($currentFolder))
                    {{ translate('All files') }}
                @else
                    <a href="{{ $folderLink() }}">{{ translate('All files') }}</a>
                @endif
            </li>
            @foreach($breadcrumbs as $crumb)
                @if($loop->last)
                    <li class="breadcrumb-item active">{{ $crumb->name }}</li>
                @else
                    <li class="breadcrumb-item"><a href="{{ $folderLink($crumb->id) }}">{{ $crumb->name }}</a></li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif

<style>
.w-20-percentage {
    width: 14.28%;
}
.uploaded-list-image {
    width: 70px !important;
}

</style>

<div class="card">
    <form id="sort_uploads" action="" method="GET">
        <input type="hidden" name="view" id="view_mode_input" value="{{ $viewMode ?? 'grid' }}">
        @if($foldersReady ?? false)
            <input type="hidden" name="folder" value="{{ $currentFolder->id ?? '' }}">
        @endif

        @php
            $filtersApplied = collect([
                $search,
                $typeFilter ?? null,
                $extension ?? null,
                $sizeMin ?? null,
                $sizeMax ?? null,
                request('date_from'),
                request('date_to'),
                ($uploader ?? null),
            ])->contains(fn ($value) => $value !== null && $value !== '')
                || (($sortBy ?? 'created_at') !== 'created_at')
                || (($sortOrder ?? 'desc') !== 'desc')
                || ((int) ($perPage ?? 60) !== 60);
        @endphp
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <div class="mb-2">
                <h5 class="mb-0 h6">{{ translate('All files') }}</h5>
                @if($filtersApplied)
                    <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
                @endif
            </div>
            <div class="d-flex flex-wrap align-items-center">
                <div class="dropdown mb-2 mr-2">
                    <button class="btn border dropdown-toggle" type="button" data-toggle="dropdown">
                        {{ translate('Bulk Action') }}
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        @if($foldersReady ?? false)
                            <a class="dropdown-item open-move-modal" href="javascript:void(0)">
                                {{ translate('Move selected') }}
                            </a>
                        @endif
                        <a class="dropdown-item confirm-alert" href="javascript:void(0)" data-target="#bulk-delete-modal">
                            {{ translate('Delete selection') }}
                        </a>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal" data-target="#uploadedFilesFilterModal">
                    {{ translate('Open Filters') }}
                </button>
                <a href="{{ route('uploaded-files.index', array_filter(['folder' => $currentFolder->id ?? null, 'view' => $viewMode ?? null])) }}" class="btn btn-danger mr-2 mb-2">
                    {{ translate('Reset') }}
                </a>
                <div class="btn-group btn-group-sm mb-2" role="group" aria-label="View Mode">
                    <button type="button" class="btn btn-outline-secondary view-toggle" data-view="grid">
                        <i class="las la-th-large"></i> {{ translate('Grid') }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary view-toggle" data-view="list">
                        <i class="las la-list"></i> {{ translate('List') }}
                    </button>
                </div>
            </div>
        </div>
        @include('backend.uploaded_files.partials.filter_modal', ['showUploader' => true])

        <div class="card-body">
            <div class="form-group mb-2">
                <div class="aiz-checkbox-inline">
                    <label class="aiz-checkbox">
                        {{ translate('Select All')}}
                        <input type="checkbox" class="check-all">
                        <span class="aiz-square-check"></span>
                    </label>
                </div>
            </div>
            @if($foldersReady ?? false)
                <div id="upload-selection-bar" class="upload-selection-bar d-none mb-3">
                    <span><strong id="upload-selection-count">0</strong> {{ translate('selected') }}</span>
                    <span>
                        <button type="button" class="btn btn-sm btn-primary open-move-modal">{{ translate('Move selected') }}</button>
                        <button type="button" class="btn btn-sm btn-link" id="clear-upload-selection">{{ translate('Clear') }}</button>
                    </span>
                </div>
            @endif

            <div class="row gutters-5 view-grid {{ ($viewMode ?? 'grid') === 'list' ? 'd-none' : '' }}">
                @if($foldersReady ?? false)
                    @foreach($childFolders as $folder)
                        <div class="col-auto w-20-percentage" data-folder-row="{{ $folder->id }}">
                            <div class="aiz-file-box">
                                <div class="dropdown-file">
                                    <a class="dropdown-link" data-toggle="dropdown">
                                        <i class="la la-ellipsis-v"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="{{ $folderLink($folder->id) }}" class="dropdown-item">
                                            <i class="las la-folder-open mr-2"></i>
                                            <span>{{ translate('Open') }}</span>
                                        </a>
                                        <a href="javascript:void(0)" class="dropdown-item rename-folder-action"
                                           data-route="{{ route('uploaded-files.folders.rename', $folder) }}"
                                           data-name="{{ $folder->name }}">
                                            <i class="las la-i-cursor mr-2"></i>
                                            <span>{{ translate('Rename') }}</span>
                                        </a>
                                        <a href="javascript:void(0)" class="dropdown-item move-one-action">
                                            <i class="las la-exchange-alt mr-2"></i>
                                            <span>{{ translate('Move this folder only') }}</span>
                                        </a>
                                        <a href="javascript:void(0)" class="dropdown-item confirm-delete" data-href="{{ route('uploaded-files.folders.destroy', $folder->id) }}" data-message="{{ translate('Remove this folder and move everything inside it up one level?') }}">
                                            <i class="las la-level-up-alt mr-2"></i>
                                            <span>{{ translate('Remove folder only') }}</span>
                                        </a>
                                        <a href="javascript:void(0)" class="dropdown-item confirm-delete" data-href="{{ route('uploaded-files.folders.destroy', $folder->id) }}?with_files=1" data-message="{{ translate('Delete this folder and every file and folder inside it? Files that are in use will be kept.') }}">
                                            <i class="las la-trash mr-2"></i>
                                            <span>{{ translate('Delete folder and files') }}</span>
                                        </a>
                                    </div>
                                </div>
                                <div class="select-box">
                                    <div class="aiz-checkbox-inline">
                                        <label class="aiz-checkbox">
                                            <input type="checkbox" class="check-folder" name="folder_ids[]" value="{{ $folder->id }}">
                                            <span class="aiz-square-check"></span>
                                        </label>
                                    </div>
                                </div>
                                <a href="{{ $folderLink($folder->id) }}" class="card card-file aiz-uploader-select c-default uploaded-file-card uploaded-folder-card" title="{{ $folder->name }}">
                                    <div class="card-file-thumb d-flex align-items-center justify-content-center">
                                        <i class="las la-folder uploaded-folder-icon"></i>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="d-flex uploaded-file-title">
                                            <span class="text-truncate title">{{ $folder->name }}</span>
                                        </h6>
                                        <p class="uploaded-file-size">{{ translate('Folder') }}</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    @endforeach
                @endif
                @foreach($all_uploads as $key => $file)
                    @php
                        $file_name = $file->file_original_name ?? translate('Unknown');
                        $file_path = $file->external_link ? $file->external_link : my_asset($file->file_name);
                        $icon_class = 'las la-file';
                        if ($file->type === 'video') {
                            $icon_class = 'las la-file-video';
                        } elseif ($file->type === 'audio') {
                            $icon_class = 'las la-file-audio';
                        } elseif ($file->type === 'archive') {
                            $icon_class = 'las la-file-archive';
                        } elseif (in_array(strtolower($file->extension), ['pdf'])) {
                            $icon_class = 'las la-file-pdf';
                        } elseif (in_array(strtolower($file->extension), ['doc', 'docx'])) {
                            $icon_class = 'las la-file-word';
                        } elseif (in_array(strtolower($file->extension), ['xls', 'xlsx', 'ods'])) {
                            $icon_class = 'las la-file-excel';
                        } elseif (in_array(strtolower($file->extension), ['csv'])) {
                            $icon_class = 'las la-file-csv';
                        }
                    @endphp
                    <div class="col-auto w-20-percentage" data-file-row="{{ $file->id }}">
                        <div class="aiz-file-box">
                            <div class="dropdown-file">
                                <a class="dropdown-link" data-toggle="dropdown">
                                    <i class="la la-ellipsis-v"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a href="javascript:void(0)" class="dropdown-item" onclick="detailsInfo(this)" data-id="{{ $file->id }}">
                                        <i class="las la-info-circle mr-2"></i>
                                        <span>{{ translate('Details Info') }}</span>
                                    </a>
                                    <a href="{{ $file_path }}" target="_blank" download="{{ $file_name }}.{{ $file->extension }}" class="dropdown-item file-download-link">
                                        <i class="la la-download mr-2"></i>
                                        <span>{{ translate('Download') }}</span>
                                    </a>
                                    <a href="javascript:void(0)" class="dropdown-item copy-link-btn" data-url="{{ $file_path }}">
                                        <i class="las la-clipboard mr-2"></i>
                                        <span>{{ translate('Copy Link') }}</span>
                                    </a>
                                    <a href="javascript:void(0)" class="dropdown-item rename-file-action"
                                       data-id="{{ $file->id }}"
                                       data-route="{{ route('uploaded-files.rename', $file) }}"
                                       data-name="{{ $file_name }}"
                                       data-ext="{{ $file->extension }}">
                                        <i class="las la-i-cursor mr-2"></i>
                                        <span>{{ translate('Rename') }}</span>
                                    </a>
                                    @if($foldersReady ?? false)
                                        <a href="javascript:void(0)" class="dropdown-item move-one-action">
                                            <i class="las la-exchange-alt mr-2"></i>
                                            <span>{{ translate('Move this file only') }}</span>
                                        </a>
                                    @endif
                                    <a href="javascript:void(0)" class="dropdown-item confirm-delete" data-href="{{ route('uploaded-files.destroy', $file->id ) }}" data-target="#delete-modal">
                                        <i class="las la-trash mr-2"></i>
                                        <span>{{ translate('Delete') }}</span>
                                    </a>
                                </div>
                            </div>
                            <div class="select-box">
                                <div class="aiz-checkbox-inline">
                                    <label class="aiz-checkbox">
                                        <input type="checkbox" class="check-one" name="id[]" value="{{$file->id}}">
                                        <span class="aiz-square-check"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="card card-file aiz-uploader-select c-default uploaded-file-card" title="{{ $file_name }}.{{ $file->extension }}">
                                <div class="card-file-thumb">
                                    @if($file->type == 'image')
                                        <img src="{{ $file_path }}" class="img-fit uploaded-file-image">
                                    @elseif($file->type == 'video')
                                        <video src="{{ $file_path }}" class="img-fit uploaded-file-video" preload="metadata" muted playsinline></video>
                                    @else
                                        <i class="{{ $icon_class }} uploaded-file-icon"></i>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <h6 class="d-flex uploaded-file-title">
                                        <span class="text-truncate title file-title-text">{{ $file_name }}</span>
                                        <span class="ext">.{{ $file->extension }}</span>
                                    </h6>
                                    <p class="uploaded-file-size">{{ formatBytes($file->file_size) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @php
                $sortIcon = function ($column) use ($sortBy, $sortOrder) {
                    if ($sortBy === $column) {
                        return $sortOrder === 'asc' ? 'las la-sort-amount-up' : 'las la-sort-amount-down';
                    }
                    return 'las la-sort';
                };
            @endphp

            <div class="table-responsive view-list {{ ($viewMode ?? 'grid') === 'list' ? '' : 'd-none' }}">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th width="55">
                                <div class="aiz-checkbox-inline mb-0">
                                    <label class="aiz-checkbox mb-0">
                                        <input type="checkbox" class="check-all">
                                        <span class="aiz-square-check"></span>
                                    </label>
                                </div>
                            </th>
                            <th class="table-sort-trigger c-pointer" data-sort="name">
                                {{ translate('Name') }} <i class="{{ $sortIcon('name') }}"></i>
                            </th>
                            <th class="table-sort-trigger c-pointer" data-sort="type">
                                {{ translate('Type') }} <i class="{{ $sortIcon('type') }}"></i>
                            </th>
                            <th class="table-sort-trigger c-pointer text-right" data-sort="size">
                                {{ translate('Size') }} <i class="{{ $sortIcon('size') }}"></i>
                            </th>
                            <th class="table-sort-trigger c-pointer" data-sort="created_at">
                                {{ translate('Created At') }} <i class="{{ $sortIcon('created_at') }}"></i>
                            </th>
                            <th class="text-right">{{ translate('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($foldersReady ?? false)
                            @foreach($childFolders as $folder)
                                <tr data-folder-row="{{ $folder->id }}">
                                    <td>
                                        <div class="aiz-checkbox-inline mb-0">
                                            <label class="aiz-checkbox mb-0">
                                                <input type="checkbox" class="check-folder" name="folder_ids[]" value="{{ $folder->id }}">
                                                <span class="aiz-square-check"></span>
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="uploaded-list-icon-wrapper avatar avatar-sm flex-shrink-0 mr-3 d-flex align-items-center justify-content-center">
                                                <i class="las la-folder uploaded-folder-icon"></i>
                                            </span>
                                            <div>
                                                <a href="{{ $folderLink($folder->id) }}" class="font-weight-medium uploaded-list-title">{{ $folder->name }}</a>
                                                <div class="text-muted small">{{ translate('Folder') }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ translate('Folder') }}</td>
                                    <td class="text-right">—</td>
                                    <td>{{ $folder->created_at ? $folder->created_at->format('d M Y, h:i A') : '' }}</td>
                                    <td class="text-right">
                                        <div class="dropdown">
                                            <a class="btn btn-sm btn-outline-primary dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                {{ translate('Actions') }}
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a href="{{ $folderLink($folder->id) }}" class="dropdown-item">
                                                    <i class="las la-folder-open mr-2"></i>{{ translate('Open') }}
                                                </a>
                                                <a href="javascript:void(0)" class="dropdown-item rename-folder-action"
                                                   data-route="{{ route('uploaded-files.folders.rename', $folder) }}"
                                                   data-name="{{ $folder->name }}">
                                                    <i class="las la-i-cursor mr-2"></i>{{ translate('Rename') }}
                                                </a>
                                                <a href="javascript:void(0)" class="dropdown-item move-one-action">
                                                    <i class="las la-exchange-alt mr-2"></i>{{ translate('Move this folder only') }}
                                                </a>
                                                <a href="javascript:void(0)" class="dropdown-item confirm-delete" data-href="{{ route('uploaded-files.folders.destroy', $folder->id) }}" data-message="{{ translate('Remove this folder and move everything inside it up one level?') }}">
                                                    <i class="las la-level-up-alt mr-2"></i>{{ translate('Remove folder only') }}
                                                </a>
                                                <a href="javascript:void(0)" class="dropdown-item confirm-delete" data-href="{{ route('uploaded-files.folders.destroy', $folder->id) }}?with_files=1" data-message="{{ translate('Delete this folder and every file and folder inside it? Files that are in use will be kept.') }}">
                                                    <i class="las la-trash mr-2"></i>{{ translate('Delete folder and files') }}
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        @foreach($all_uploads as $file)
                            @php
                                $file_name = $file->file_original_name ?? translate('Unknown');
                                $file_path = $file->external_link ? $file->external_link : my_asset($file->file_name);
                                $icon_class = 'las la-file';
                                if ($file->type === 'video') {
                                    $icon_class = 'las la-file-video';
                                } elseif ($file->type === 'audio') {
                                    $icon_class = 'las la-file-audio';
                                } elseif ($file->type === 'archive') {
                                    $icon_class = 'las la-file-archive';
                                } elseif (in_array(strtolower($file->extension), ['pdf'])) {
                                    $icon_class = 'las la-file-pdf';
                                } elseif (in_array(strtolower($file->extension), ['doc', 'docx'])) {
                                    $icon_class = 'las la-file-word';
                                } elseif (in_array(strtolower($file->extension), ['xls', 'xlsx', 'ods'])) {
                                    $icon_class = 'las la-file-excel';
                                } elseif (in_array(strtolower($file->extension), ['csv'])) {
                                    $icon_class = 'las la-file-csv';
                                }
                            @endphp
                            <tr data-file-row="{{ $file->id }}">
                                <td>
                                    <div class="aiz-checkbox-inline mb-0">
                                        <label class="aiz-checkbox mb-0">
                                            <input type="checkbox" class="check-one" name="id[]" value="{{$file->id}}">
                                            <span class="aiz-square-check"></span>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($file->type == 'image')
                                            <img src="{{ $file_path }}" class="uploaded-list-image img-fit rounded mr-3">
                                        @elseif($file->type == 'video')
                                            <video src="{{ $file_path }}" class="uploaded-list-video rounded mr-3" preload="metadata" muted playsinline></video>
                                        @else
                                            <span class="uploaded-list-icon-wrapper avatar avatar-sm flex-shrink-0 mr-3 bg-soft-primary d-flex align-items-center justify-content-center">
                                                <i class="{{ $icon_class }} uploaded-list-icon"></i>
                                            </span>
                                        @endif
                                        <div>
                                            <div class="font-weight-medium file-title-text uploaded-list-title">{{ $file_name }}</div>
                                            <div class="text-muted small uploaded-list-extension">.{{ $file->extension }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ strtoupper($file->extension) ?? strtoupper($file->type) }}</td>
                                <td class="text-right">{{ formatBytes($file->file_size) }}</td>
                                <td>{{ $file->created_at->format('d M Y, h:i A') }}</td>
                                <td class="text-right">
                                    <div class="dropdown">
                                        <a class="btn btn-sm btn-outline-primary dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            {{ translate('Actions') }}
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a href="javascript:void(0)" class="dropdown-item" onclick="detailsInfo(this)" data-id="{{ $file->id }}">
                                                <i class="las la-info-circle mr-2"></i>{{ translate('Details Info') }}
                                            </a>
                                            <a href="{{ $file_path }}" target="_blank" download="{{ $file_name }}.{{ $file->extension }}" class="dropdown-item file-download-link">
                                                <i class="la la-download mr-2"></i>{{ translate('Download') }}
                                            </a>
                                            <a href="javascript:void(0)" class="dropdown-item copy-link-btn" data-url="{{ $file_path }}">
                                                <i class="las la-clipboard mr-2"></i>{{ translate('Copy Link') }}
                                            </a>
                                            <a href="javascript:void(0)" class="dropdown-item rename-file-action"
                                               data-id="{{ $file->id }}"
                                               data-route="{{ route('uploaded-files.rename', $file) }}"
                                               data-name="{{ $file_name }}"
                                               data-ext="{{ $file->extension }}">
                                                <i class="las la-i-cursor mr-2"></i>{{ translate('Rename') }}
                                            </a>
                                            @if($foldersReady ?? false)
                                                <a href="javascript:void(0)" class="dropdown-item move-one-action">
                                                    <i class="las la-exchange-alt mr-2"></i>{{ translate('Move this file only') }}
                                                </a>
                                            @endif
                                            <a href="javascript:void(0)" class="dropdown-item confirm-delete" data-href="{{ route('uploaded-files.destroy', $file->id ) }}" data-target="#delete-modal">
                                                <i class="las la-trash mr-2"></i>{{ translate('Delete') }}
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(($foldersReady ?? false) && $childFolders->isEmpty() && $all_uploads->isEmpty())
                <div class="text-center text-muted py-5">{{ translate('This folder is empty') }}</div>
            @endif

            <div class="aiz-pagination mt-3">
                {{ $all_uploads->appends(request()->input())->links() }}
            </div>
        </div>
    </form>
</div>
@endsection
@section('modal')
<div id="info-modal" class="modal fade">
    <div class="modal-dialog modal-dialog-right">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title h6">{{ translate('File Info') }}</h5>
                <button type="button" class="close" data-dismiss="modal"></button>
            </div>
            <div class="modal-body c-scrollbar-light position-relative" id="info-modal-content">
                <div class="c-preloader text-center absolute-center">
                    <i class="las la-spinner la-spin la-3x opacity-70"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rename modal -->
<div class="modal fade" id="rename-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title h6">{{ translate('Rename File') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">{{ translate('New name (without extension)') }}</label>
                    <input type="text" class="form-control" id="rename-new-name" autocomplete="off">
                    <small class="text-muted d-block mt-1">{{ translate('Extension will stay the same') }}</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link" data-dismiss="modal">{{ translate('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="rename-save-btn">{{ translate('Save') }}</button>
            </div>
        </div>
    </div>
</div>

@if($foldersReady ?? false)
<div class="modal fade" id="create-folder-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('uploaded-files.folders.store') }}" method="POST">
                @csrf
                <input type="hidden" name="parent_id" value="{{ $currentFolder->id ?? '' }}">
                <div class="modal-header">
                    <h5 class="modal-title h6">{{ translate('New Folder') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label class="form-label">{{ translate('Folder name') }}</label>
                        <input type="text" class="form-control" name="name" maxlength="190" required autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ translate('Create') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="rename-folder-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="rename-folder-form" action="" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title h6">{{ translate('Rename Folder') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label class="form-label">{{ translate('Folder name') }}</label>
                        <input type="text" class="form-control" name="name" id="rename-folder-name" maxlength="190" required autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="move-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="move-items-form" action="{{ route('uploaded-files.move') }}" method="POST">
                @csrf
                <div id="move-selection-inputs"></div>
                <div class="modal-header">
                    <h5 class="modal-title h6">{{ translate('Move') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label class="form-label">{{ translate('Destination folder') }}</label>
                        <select class="form-control" name="destination_id" id="move-destination">
                            <option value="">{{ translate('All files') }}</option>
                            @foreach($folderOptions as $option)
                                <option value="{{ $option['id'] }}">{{ str_repeat('— ', $option['depth']) }}{{ $option['name'] }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-2">{{ translate('The file link stays the same. Only the folder changes.') }}</small>
                        <small id="move-scope-note" class="text-muted d-none mt-1"></small>
                        <small id="move-all-note" class="text-muted d-none mt-1">{{ translate('This moves every file in this folder, on every page. A type or search filter limits which files move. Subfolders move too, unless a type filter is on.') }}</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary" id="move-save-btn">{{ translate('Move') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Delete modal -->
@include('modals.delete_modal')
<!-- Bulk Delete modal -->
@include('modals.bulk_delete_modal')
@endsection
@section('style')
<style>
    /* Uploaded Files Page Improvements */
    .uploaded-file-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        background: #ffffff;
    }
    
    .uploaded-file-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        border-color: #2b56a1;
    }
    
    .uploaded-file-card .card-file-thumb {
        height: 160px;
        background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
        border-radius: 8px 8px 0 0;
        overflow: hidden;
    }
    
    .uploaded-file-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .uploaded-file-card:hover .uploaded-file-image {
        transform: scale(1.05);
    }
    
    .uploaded-file-video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .upload-selection-bar:not(.d-none) {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 14px;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        background: #eff6ff;
        color: #1e3a8a;
    }

    .uploaded-folder-icon {
        font-size: 56px;
        color: #d97706;
    }

    .uploaded-folder-card {
        text-decoration: none;
        color: inherit;
    }

    .uploaded-file-icon {
        font-size: 56px !important;
        color: #2b56a1;
        transition: transform 0.3s ease;
    }
    
    .uploaded-file-card:hover .uploaded-file-icon {
        transform: scale(1.1);
    }
    
    .uploaded-file-card .card-body {
        padding: 12px;
        background: #ffffff;
    }
    
    .uploaded-file-title {
        font-size: 14px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
        line-height: 1.4;
    }
    
    .uploaded-file-title .text-truncate {
        max-width: 140px;
        font-weight: 600;
    }
    
    .uploaded-file-title .ext {
        color: #6b7280;
        font-weight: 500;
        margin-left: 4px;
    }
    
    .uploaded-file-size {
        font-size: 12px;
        color: #6b7280;
        font-weight: 500;
        margin: 0;
    }
    
    /* List View Improvements */
    .uploaded-list-image,
    .uploaded-list-video {
        width: 64px;
        height: 64px;
        object-fit: cover;
        border: 2px solid #e5e7eb;
    }
    
    .uploaded-list-icon-wrapper {
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
        border: 2px solid #e5e7eb;
    }
    
    .uploaded-list-icon,
    .uploaded-list-icon-wrapper .uploaded-folder-icon {
        font-size: 28px;
        color: #2b56a1;
    }

    .uploaded-list-icon-wrapper .uploaded-folder-icon {
        color: #d97706;
    }
    
    .uploaded-list-title {
        font-size: 15px;
        font-weight: 600;
        color: #1f2937;
    }
    
    .uploaded-list-extension {
        font-size: 13px;
        color: #6b7280;
        font-weight: 500;
    }
    
    /* Grid/List Button Styling */
    .view-toggle.btn-primary {
        background-color: #2b56a1;
        border-color: #2b56a1;
        color: #ffffff;
    }
    
    .view-toggle.btn-outline-secondary {
        border-color: #d1d5db;
        color: #6b7280;
    }
    
    .view-toggle.btn-outline-secondary:hover {
        background-color: #f3f4f6;
        border-color: #2b56a1;
        color: #2b56a1;
    }
    
    /* Table Improvements */
    .view-list table {
        font-size: 14px;
    }
    
    .view-list thead th {
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
        border-bottom: 2px solid #e5e7eb;
        padding: 12px;
    }
    
    .view-list tbody td {
        padding: 14px 12px;
        vertical-align: middle;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .view-list tbody tr:hover {
        background-color: #f9fafb;
    }
    
    /* Grid View Improvements */
    .view-grid .w-140px {
        width: 180px;
        margin-bottom: 20px;
    }
    
    .view-grid .w-lg-220px {
        width: 220px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .view-grid .w-140px,
        .view-grid .w-lg-220px {
            width: 100%;
            max-width: 200px;
        }
        
        .uploaded-file-card .card-file-thumb {
            height: 140px;
        }
    }
    
    /* Card Header Improvements */
    .card-header {
        background: #f9fafb;
        border-bottom: 2px solid #e5e7eb;
        padding: 16px 20px;
    }
    
    /* Form Controls */
    .form-control-xs {
        font-size: 14px;
        padding: 8px 12px;
        border-radius: 6px;
        border: 1px solid #d1d5db;
    }
    
    .form-control-xs:focus {
        border-color: #2b56a1;
        box-shadow: 0 0 0 3px rgba(43, 86, 161, 0.1);
    }
</style>
@endsection

@section('script')
    <script type="text/javascript">
        (function () {
            var state = {
                renameTarget: null,
            };

            function applyView(mode) {
                var normalized = mode === 'list' ? 'list' : 'grid';
                $('#view_mode_input').val(normalized);
                localStorage.setItem('aiz_upload_view', normalized);
                $('.view-grid').toggleClass('d-none', normalized === 'list');
                $('.view-list').toggleClass('d-none', normalized !== 'list');
                $('.view-toggle').removeClass('btn-primary').addClass('btn-outline-secondary');
                $('.view-toggle[data-view="' + normalized + '"]').addClass('btn-primary').removeClass('btn-outline-secondary');
            }

            var storedView = localStorage.getItem('aiz_upload_view');
            applyView(storedView || $('#view_mode_input').val() || 'grid');

            $('.view-toggle').on('click', function () {
                applyView($(this).data('view'));
            });

            var defaultDeleteMessage = "{{ translate('Are you sure to delete this?') }}";
            $(document).on('click', '.confirm-delete', function () {
                var message = $(this).data('message') || defaultDeleteMessage;
                $('#delete-modal .modal-body p').text(message);
            });

            $('.table-sort-trigger').on('click', function (e) {
                e.preventDefault();
                var column = $(this).data('sort');
                var current = $('#sort_by').val();
                var order = $('#sort_order').val() === 'asc' ? 'desc' : 'asc';
                if (current !== column) {
                    order = 'asc';
                }
                $('#sort_by').val(column);
                $('#sort_order').val(order);
                $('#sort_uploads').submit();
            });

            $(document).on("change", ".check-all", function() {
                var checked = this.checked;
                $('.check-all').prop('checked', checked);
                $('.check-one:checkbox, .check-folder:checkbox').prop('checked', checked);
                refreshSelectionBar();
            });

            $(document).on('change', '.check-one, .check-folder', function () {
                refreshSelectionBar();
            });

            $('#clear-upload-selection').on('click', function () {
                $('.check-all, .check-one, .check-folder').prop('checked', false);
                refreshSelectionBar();
            });

            function refreshSelectionBar() {
                var selected = selectedMoveIds();
                var count = selected.fileIds.length + selected.folderIds.length;
                $('#upload-selection-count').text(count);
                $('#upload-selection-bar').toggleClass('d-none', count === 0);
            }

            function selectedMoveIds() {
                var fileIds = [];
                var folderIds = [];
                $('.check-one:checked').each(function () {
                    var value = String($(this).val());
                    if (fileIds.indexOf(value) === -1) {
                        fileIds.push(value);
                    }
                });
                $('.check-folder:checked').each(function () {
                    var value = String($(this).val());
                    if (folderIds.indexOf(value) === -1) {
                        folderIds.push(value);
                    }
                });
                return { fileIds: fileIds, folderIds: folderIds };
            }

            function openMoveModal() {
                var selected = selectedMoveIds();
                if (!selected.fileIds.length && !selected.folderIds.length) {
                    AIZ.plugins.notify('warning', "{{ translate('Select files or folders to move.') }}");
                    return;
                }
                var box = $('<div>');
                selected.fileIds.forEach(function (id) {
                    box.append($('<input>', { type: 'hidden', name: 'id[]', value: id }));
                });
                selected.folderIds.forEach(function (id) {
                    box.append($('<input>', { type: 'hidden', name: 'folder_ids[]', value: id }));
                });
                $('#move-selection-inputs').empty().append(box.children());
                $('#move-modal .modal-title').text("{{ translate('Move selected') }}");
                $('#move-save-btn').text("{{ translate('Move selected') }}");
                $('#move-scope-note').text("{{ translate('Only the checked files and folders will move.') }}").removeClass('d-none');
                $('#move-all-note').addClass('d-none');
                $('#move-modal').modal('show');
            }

            $(document).on('click', '.open-move-all', function (e) {
                e.preventDefault();
                var box = $('<div>');
                box.append($('<input>', { type: 'hidden', name: 'move_all', value: '1' }));
                box.append($('<input>', { type: 'hidden', name: 'source_id', value: $('input[name="folder"]').val() || '' }));
                box.append($('<input>', { type: 'hidden', name: 'search', value: $('#upload_search').val() || '' }));
                box.append($('<input>', { type: 'hidden', name: 'type', value: $('#type_filter').val() || '' }));
                box.append($('<input>', { type: 'hidden', name: 'extension', value: $('#upload_extension').val() || '' }));
                box.append($('<input>', { type: 'hidden', name: 'size_min', value: $('#size_min').val() || '' }));
                box.append($('<input>', { type: 'hidden', name: 'size_max', value: $('#size_max').val() || '' }));
                box.append($('<input>', { type: 'hidden', name: 'date_from', value: $('#date_from').val() || '' }));
                box.append($('<input>', { type: 'hidden', name: 'date_to', value: $('#date_to').val() || '' }));
                box.append($('<input>', { type: 'hidden', name: 'uploader', value: $('#upload_uploader').val() || '' }));
                $('#move-selection-inputs').empty().append(box.children());
                $('#move-modal .modal-title').text("{{ translate('Move everything here') }}");
                $('#move-save-btn').text("{{ translate('Move everything here') }}");
                $('#move-scope-note').addClass('d-none');
                $('#move-all-note').removeClass('d-none');
                $('#move-modal').modal('show');
            });

            $(document).on('click', '.open-move-modal', function (e) {
                e.preventDefault();
                openMoveModal();
            });

            $(document).on('click', '.move-one-action', function (e) {
                e.preventDefault();
                var row = $(this).closest('[data-file-row], [data-folder-row]');
                var fileId = row.data('file-row');
                var folderId = row.data('folder-row');
                var box = $('<div>');
                if (fileId) {
                    box.append($('<input>', { type: 'hidden', name: 'id[]', value: fileId }));
                }
                if (folderId) {
                    box.append($('<input>', { type: 'hidden', name: 'folder_ids[]', value: folderId }));
                }
                $('#move-selection-inputs').empty().append(box.children());
                $('#move-modal .modal-title').text(fileId ? "{{ translate('Move this file only') }}" : "{{ translate('Move this folder only') }}");
                $('#move-save-btn').text(fileId ? "{{ translate('Move this file only') }}" : "{{ translate('Move this folder only') }}");
                $('#move-scope-note').text("{{ translate('The other selected items will stay where they are.') }}").removeClass('d-none');
                $('#move-all-note').addClass('d-none');
                $('#move-modal').modal('show');
            });

            $(document).on('click', '.rename-folder-action', function (e) {
                e.preventDefault();
                $('#rename-folder-form').attr('action', $(this).data('route'));
                $('#rename-folder-name').val($(this).data('name'));
                $('#rename-folder-modal').modal('show');
            });

            function copyUrl(e) {
                var url = $(e).data('url');
                var $temp = $("<input>");
                $("body").append($temp);
                $temp.val(url).select();
                try {
                    document.execCommand("copy");
                    AIZ.plugins.notify('success', "{{ translate('Link copied to clipboard') }}");
                } catch (err) {
                    AIZ.plugins.notify('danger', "{{ translate('Oops, unable to copy') }}");
                }
                $temp.remove();
            }

            $(document).on('click', '.copy-link-btn', function () {
                copyUrl(this);
            });

            window.detailsInfo = function (e) {
                $('#info-modal-content').html('<div class="c-preloader text-center absolute-center"><i class="las la-spinner la-spin la-3x opacity-70"></i></div>');
                var id = $(e).data('id');
                $('#info-modal').modal('show');
                $.post('{{ route('uploaded-files.info') }}', {_token: AIZ.data.csrf, id:id}, function(data){
                    $('#info-modal-content').html(data);
                });
            }

            window.bulk_delete = function () {
                var data = new FormData($('#sort_uploads')[0]);
                $.ajax({
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    url: "{{route('bulk-uploaded-files-delete')}}",
                    type: 'POST',
                    data: data,
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function (response) {
                        if(response == 1) {
                            location.reload();
                        } else {
                            AIZ.plugins.notify('danger', "{{ translate('Something Went Wrong.') }}");
                        }
                    }
                });
            }

            // Rename
            $(document).on('click', '.rename-file-action', function () {
                state.renameTarget = {
                    id: $(this).data('id'),
                    route: $(this).data('route'),
                    ext: $(this).data('ext'),
                    name: $(this).data('name')
                };
                $('#rename-new-name').val(state.renameTarget.name);
                $('#rename-modal').modal('show');
            });

            $('#rename-save-btn').on('click', function () {
                if (!state.renameTarget) return;
                var newName = $('#rename-new-name').val();
                $.post(state.renameTarget.route, {
                    _token: AIZ.data.csrf,
                    new_name: newName
                }).done(function (resp) {
                    $('#rename-modal').modal('hide');
                    updateFileRow(resp.file);
                    AIZ.plugins.notify('success', resp.message || "{{ translate('File renamed successfully') }}");
                }).fail(function (xhr) {
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : "{{ translate('Unable to rename file') }}";
                    AIZ.plugins.notify('danger', msg);
                });
            });

            function updateFileRow(file) {
                var selector = '[data-file-row="' + file.id + '"]';
                $(selector).find('.file-title-text').text(file.file_original_name);
                $(selector).find('.ext').text('.' + file.extension);
                $(selector).find('.file-download-link')
                    .attr('href', file.full_path)
                    .attr('download', file.file_original_name + '.' + file.extension);
                $(selector).find('.copy-link-btn').data('url', file.full_path);
                $(selector).find('.rename-file-action')
                    .data('name', file.file_original_name)
                    .data('ext', file.extension);
            }

        })();
    </script>
@endsection