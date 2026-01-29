@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('All uploaded files') }}</h1>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('uploaded-files.create') }}" class="btn btn-circle btn-info">
                <span>{{ translate('Upload New File') }}</span>
            </a>
        </div>
    </div>
</div>

<div class="card">
    <form id="sort_uploads" action="" method="GET">
        <input type="hidden" name="sort_by" id="sort_by" value="{{ $sortBy ?? 'created_at' }}">
        <input type="hidden" name="sort_order" id="sort_order" value="{{ $sortOrder ?? 'desc' }}">
        <input type="hidden" name="view" id="view_mode_input" value="{{ $viewMode ?? 'grid' }}">

        <div class="card-header row gutters-5 align-items-center">
            <div class="col">
                <h5 class="mb-0 h6">{{ translate('All files') }}</h5>
            </div>
            <div class="dropdown mb-2 mb-md-0">
                <button class="btn border dropdown-toggle" type="button" data-toggle="dropdown">
                    {{ translate('Bulk Action') }}
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item confirm-alert" href="javascript:void(0)" data-target="#bulk-delete-modal">
                        {{ translate('Delete selection') }}
                    </a>
                </div>
            </div>
            <div class="col-auto">
                <div class="btn-group btn-group-sm" role="group" aria-label="View Mode">
                    <button type="button" class="btn btn-outline-secondary view-toggle" data-view="grid">
                        <i class="las la-th-large"></i> {{ translate('Grid') }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary view-toggle" data-view="list">
                        <i class="las la-list"></i> {{ translate('List') }}
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <select id="type_filter" class="form-control form-control-xs aiz-selectpicker" name="type" data-live-search="true">
                    <option value="">{{ translate('All types') }}</option>
                    @php
                        $typeOptions = [
                            'image' => translate('Images'),
                            'video' => translate('Videos'),
                            'audio' => translate('Audio'),
                            'pdf' => translate('PDF'),
                            'doc' => translate('Word / Doc'),
                            'docx' => translate('Word / Docx'),
                            'excel' => translate('Excel'),
                            'xls' => translate('Excel (XLS)'),
                            'xlsx' => translate('Excel (XLSX)'),
                            'csv' => translate('CSV'),
                            'archive' => translate('Archive'),
                            'document' => translate('Documents'),
                        ];
                    @endphp
                    @foreach($typeOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($typeFilter ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select id="sort_select" class="form-control form-control-xs aiz-selectpicker">
                    <option value="created_at|desc" @selected(($sortBy ?? 'created_at') === 'created_at' && ($sortOrder ?? 'desc') === 'desc')>{{ translate('Newest first') }}</option>
                    <option value="created_at|asc" @selected(($sortBy ?? 'created_at') === 'created_at' && ($sortOrder ?? 'desc') === 'asc')>{{ translate('Oldest first') }}</option>
                    <option value="name|asc" @selected(($sortBy ?? '') === 'name' && ($sortOrder ?? '') === 'asc')>{{ translate('Name A-Z') }}</option>
                    <option value="name|desc" @selected(($sortBy ?? '') === 'name' && ($sortOrder ?? '') === 'desc')>{{ translate('Name Z-A') }}</option>
                    <option value="size|desc" @selected(($sortBy ?? '') === 'size' && ($sortOrder ?? '') === 'desc')>{{ translate('Size large-small') }}</option>
                    <option value="size|asc" @selected(($sortBy ?? '') === 'size' && ($sortOrder ?? '') === 'asc')>{{ translate('Size small-large') }}</option>
                    <option value="type|asc" @selected(($sortBy ?? '') === 'type' && ($sortOrder ?? '') === 'asc')>{{ translate('Type A-Z') }}</option>
                    <option value="type|desc" @selected(($sortBy ?? '') === 'type' && ($sortOrder ?? '') === 'desc')>{{ translate('Type Z-A') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control form-control-xs" name="search" placeholder="{{ translate('Search by name or extension') }}" value="{{ $search }}">
            </div>
            <div class="col-auto d-flex align-items-center">
                <button type="submit" class="btn btn-primary mr-2">{{ translate('Apply') }}</button>
                <button type="button" class="btn btn-secondary" id="reset-filters">{{ translate('Reset') }}</button>
            </div>
        </div>

        <div class="card-body">
            <div class="form-group">
                <div class="aiz-checkbox-inline">
                    <label class="aiz-checkbox">
                        {{ translate('Select All')}}
                        <input type="checkbox" class="check-all">
                        <span class="aiz-square-check"></span>
                    </label>
                </div>
            </div>

            <div class="row gutters-5 view-grid {{ ($viewMode ?? 'grid') === 'list' ? 'd-none' : '' }}">
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
                    <div class="col-auto w-140px w-lg-220px" data-file-row="{{ $file->id }}">
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
                            <div class="card card-file aiz-uploader-select c-default" title="{{ $file_name }}.{{ $file->extension }}">
                                <div class="card-file-thumb">
                                    @if($file->type == 'image')
                                        <img src="{{ $file_path }}" class="img-fit">
                                    @elseif($file->type == 'video')
                                        <video src="{{ $file_path }}" class="img-fit" preload="metadata" muted playsinline></video>
                                    @else
                                        <i class="{{ $icon_class }} fs-32"></i>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <h6 class="d-flex">
                                        <span class="text-truncate title file-title-text">{{ $file_name }}</span>
                                        <span class="ext">.{{ $file->extension }}</span>
                                    </h6>
                                    <p>{{ formatBytes($file->file_size) }}</p>
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
                                            <img src="{{ $file_path }}" class="size-48px img-fit rounded mr-3">
                                        @elseif($file->type == 'video')
                                            <video src="{{ $file_path }}" class="size-48px rounded mr-3" preload="metadata" muted playsinline></video>
                                        @else
                                            <span class="avatar avatar-sm flex-shrink-0 mr-3 bg-soft-primary d-flex align-items-center justify-content-center">
                                                <i class="{{ $icon_class }}"></i>
                                            </span>
                                        @endif
                                        <div>
                                            <div class="font-weight-medium file-title-text">{{ $file_name }}</div>
                                            <div class="text-muted small">.{{ $file->extension }}</div>
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

<!-- Delete modal -->
@include('modals.delete_modal')
<!-- Bulk Delete modal -->
@include('modals.bulk_delete_modal')
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

            $('#sort_select').on('change', function () {
                var parts = $(this).val().split('|');
                $('#sort_by').val(parts[0]);
                $('#sort_order').val(parts[1]);
                $('#sort_uploads').submit();
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

            $('#reset-filters').on('click', function () {
                $('input[name="search"]').val('');
                $('#type_filter').val('').change();
                $('#sort_by').val('created_at');
                $('#sort_order').val('desc');
                $('#sort_select').val('created_at|desc').change();
                applyView('grid');
                localStorage.removeItem('aiz_upload_view');
                $('#sort_uploads').submit();
            });

            $(document).on("change", ".check-all", function() {
                $('.check-one:checkbox').prop('checked', this.checked);
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