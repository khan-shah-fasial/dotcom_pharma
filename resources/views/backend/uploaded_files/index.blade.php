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
                <input type="text" class="form-control form-control-xs" name="search" id="search_input" placeholder="{{ translate('Search by name or extension') }}" value="{{ $search ?? '' }}">
            </div>
            <div class="col-auto d-flex align-items-center">
                <button type="button" class="btn btn-primary mr-2" id="apply-filters">{{ translate('Apply') }}</button>
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

            <div id="uploads-loading" class="text-center py-5 d-none">
                <i class="las la-spinner la-spin la-3x opacity-70"></i>
                <p class="mt-2">{{ translate('Loading...') }}</p>
            </div>

            <div id="uploads-content" class="d-none">
                <div class="row gutters-5 view-grid" id="uploads-grid"></div>

                <div class="table-responsive view-list d-none" id="uploads-list-wrap">
                    <table class="table table-bordered mb-0" id="uploads-list-table">
                        <thead>
                            <tr>
                                <th width="55">
                                    {{-- <div class="aiz-checkbox-inline mb-0">
                                        <label class="aiz-checkbox mb-0">
                                            <input type="checkbox" class="check-all">
                                            <span class="aiz-square-check"></span>
                                        </label>
                                    </div> --}}
                                </th>
                                <th class="table-sort-trigger c-pointer" data-sort="name">{{ translate('Name') }} <i class="las la-sort"></i></th>
                                <th class="table-sort-trigger c-pointer" data-sort="type">{{ translate('Type') }} <i class="las la-sort"></i></th>
                                <th class="table-sort-trigger c-pointer text-right" data-sort="size">{{ translate('Size') }} <i class="las la-sort"></i></th>
                                <th class="table-sort-trigger c-pointer" data-sort="created_at">{{ translate('Created At') }} <i class="las la-sort"></i></th>
                                <th class="text-right">{{ translate('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="uploads-list"></tbody>
                    </table>
                </div>

                <div class="aiz-pagination mt-3" id="uploads-pagination"></div>
            </div>

            <div id="uploads-empty" class="text-center py-5 d-none">
                <i class="las la-folder-open la-3x text-muted"></i>
                <p class="mt-2 text-muted">{{ translate('No files found') }}</p>
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
                currentPage: 1,
                routes: {
                    index: "{{ route('uploaded-files.index') }}",
                    info: "{{ route('uploaded-files.info') }}",
                    bulkDelete: "{{ route('bulk-uploaded-files-delete') }}"
                },
                strings: {
                    detailsInfo: "{{ translate('Details Info') }}",
                    download: "{{ translate('Download') }}",
                    copyLink: "{{ translate('Copy Link') }}",
                    rename: "{{ translate('Rename') }}",
                    delete: "{{ translate('Delete') }}",
                    linkCopied: "{{ translate('Link copied to clipboard') }}",
                    copyFailed: "{{ translate('Oops, unable to copy') }}",
                    renameSuccess: "{{ translate('File renamed successfully') }}",
                    renameFailed: "{{ translate('Unable to rename file') }}",
                    deleteSuccess: "{{ translate('File deleted successfully') }}",
                    bulkDeleteFailed: "{{ translate('Something Went Wrong.') }}",
                    noFiles: "{{ translate('No files found') }}"
                }
            };

            function getIconClass(file) {
                var ext = (file.extension || '').toLowerCase();
                if (file.type === 'video') return 'las la-file-video';
                if (file.type === 'audio') return 'las la-file-audio';
                if (file.type === 'archive') return 'las la-file-archive';
                if (ext === 'pdf') return 'las la-file-pdf';
                if (['doc', 'docx'].indexOf(ext) >= 0) return 'las la-file-word';
                if (['xls', 'xlsx', 'ods'].indexOf(ext) >= 0) return 'las la-file-excel';
                if (ext === 'csv') return 'las la-file-csv';
                return 'las la-file';
            }

            function renderGridItem(file) {
                var iconClass = getIconClass(file);
                var thumb = '';
                if (file.type === 'image') {
                    thumb = '<img data-src="' + escapeHtml(file.full_path) + '" class="img-fit upload-media">';
                } else if (file.type === 'video') {
                    thumb = '<video data-src="' + escapeHtml(file.full_path) + '" class="img-fit upload-media" preload="metadata" muted playsinline></video>';
                } else {
                    thumb = '<i class="' + iconClass + ' fs-32"></i>';
                }
                return '<div class="col-auto w-140px w-lg-220px" data-file-row="' + file.id + '">' +
                    '<div class="aiz-file-box">' +
                    '<div class="dropdown-file">' +
                    '<a class="dropdown-link" data-toggle="dropdown"><i class="la la-ellipsis-v"></i></a>' +
                    '<div class="dropdown-menu dropdown-menu-right">' +
                    '<a href="javascript:void(0)" class="dropdown-item details-info-btn" data-id="' + file.id + '"><i class="las la-info-circle mr-2"></i><span>' + state.strings.detailsInfo + '</span></a>' +
                    '<a href="' + escapeHtml(file.full_path) + '" target="_blank" download="' + escapeHtml(file.file_original_name) + '.' + escapeHtml(file.extension) + '" class="dropdown-item file-download-link"><i class="la la-download mr-2"></i><span>' + state.strings.download + '</span></a>' +
                    '<a href="javascript:void(0)" class="dropdown-item copy-link-btn" data-url="' + escapeHtml(file.full_path) + '"><i class="las la-clipboard mr-2"></i><span>' + state.strings.copyLink + '</span></a>' +
                    '<a href="javascript:void(0)" class="dropdown-item rename-file-action" data-id="' + file.id + '" data-route="' + escapeHtml(file.rename_url) + '" data-name="' + escapeHtml(file.file_original_name) + '" data-ext="' + escapeHtml(file.extension) + '"><i class="las la-i-cursor mr-2"></i><span>' + state.strings.rename + '</span></a>' +
                    '<a href="javascript:void(0)" class="dropdown-item confirm-delete" data-href="' + escapeHtml(file.destroy_url) + '" data-target="#delete-modal"><i class="las la-trash mr-2"></i><span>' + state.strings.delete + '</span></a>' +
                    '</div></div>' +
                    '<div class="select-box"><div class="aiz-checkbox-inline"><label class="aiz-checkbox"><input type="checkbox" class="check-one" name="id[]" value="' + file.id + '"><span class="aiz-square-check"></span></label></div></div>' +
                    '<div class="card card-file aiz-uploader-select c-default" title="' + escapeHtml(file.file_original_name) + '.' + escapeHtml(file.extension) + '">' +
                    '<div class="card-file-thumb">' + thumb + '</div>' +
                    '<div class="card-body"><h6 class="d-flex"><span class="text-truncate title file-title-text">' + escapeHtml(file.file_original_name) + '</span><span class="ext">.' + escapeHtml(file.extension) + '</span></h6>' +
                    '<p>' + escapeHtml(file.file_size_formatted) + '</p></div></div></div></div>';
            }

            function renderListRow(file) {
                var iconClass = getIconClass(file);
                var thumb = '';
                if (file.type === 'image') {
                    thumb = '<img data-src="' + escapeHtml(file.full_path) + '" class="size-48px img-fit rounded mr-3 upload-media">';
                } else if (file.type === 'video') {
                    thumb = '<video data-src="' + escapeHtml(file.full_path) + '" class="size-48px rounded mr-3 upload-media" preload="metadata" muted playsinline></video>';
                } else {
                    thumb = '<span class="avatar avatar-sm flex-shrink-0 mr-3 bg-soft-primary d-flex align-items-center justify-content-center"><i class="' + iconClass + '"></i></span>';
                }
                return '<tr data-file-row="' + file.id + '">' +
                    '<td><div class="aiz-checkbox-inline mb-0"><label class="aiz-checkbox mb-0"><input type="checkbox" class="check-one" name="id[]" value="' + file.id + '"><span class="aiz-square-check"></span></label></div></td>' +
                    '<td><div class="d-flex align-items-center">' + thumb + '<div><div class="font-weight-medium file-title-text">' + escapeHtml(file.file_original_name) + '</div><div class="text-muted small">.' + escapeHtml(file.extension) + '</div></div></div></td>' +
                    '<td>' + escapeHtml((file.extension || file.type || '').toUpperCase()) + '</td>' +
                    '<td class="text-right">' + escapeHtml(file.file_size_formatted) + '</td>' +
                    '<td>' + escapeHtml(file.created_at_formatted) + '</td>' +
                    '<td class="text-right"><div class="dropdown">' +
                    '<a class="btn btn-sm btn-outline-primary dropdown-toggle" href="#" role="button" data-toggle="dropdown">{{ translate("Actions") }}</a>' +
                    '<div class="dropdown-menu dropdown-menu-right">' +
                    '<a href="javascript:void(0)" class="dropdown-item details-info-btn" data-id="' + file.id + '"><i class="las la-info-circle mr-2"></i>' + state.strings.detailsInfo + '</a>' +
                    '<a href="' + escapeHtml(file.full_path) + '" target="_blank" download="' + escapeHtml(file.file_original_name) + '.' + escapeHtml(file.extension) + '" class="dropdown-item file-download-link"><i class="la la-download mr-2"></i>' + state.strings.download + '</a>' +
                    '<a href="javascript:void(0)" class="dropdown-item copy-link-btn" data-url="' + escapeHtml(file.full_path) + '"><i class="las la-clipboard mr-2"></i>' + state.strings.copyLink + '</a>' +
                    '<a href="javascript:void(0)" class="dropdown-item rename-file-action" data-id="' + file.id + '" data-route="' + escapeHtml(file.rename_url) + '" data-name="' + escapeHtml(file.file_original_name) + '" data-ext="' + escapeHtml(file.extension) + '"><i class="las la-i-cursor mr-2"></i>' + state.strings.rename + '</a>' +
                    '<a href="javascript:void(0)" class="dropdown-item confirm-delete" data-href="' + escapeHtml(file.destroy_url) + '" data-target="#delete-modal"><i class="las la-trash mr-2"></i>' + state.strings.delete + '</a>' +
                    '</div></div></td></tr>';
            }

            function escapeHtml(s) {
                if (s == null) return '';
                var div = document.createElement('div');
                div.textContent = s;
                return div.innerHTML;
            }

            function buildPaginationHtml(meta, links) {
                if (meta.last_page <= 1) return '';
                var current = meta.current_page;
                var last = meta.last_page;
                var delta = 2;
                var left = current - delta;
                var right = current + delta;
                var range = [];
                var rangeWithDots = [];
                var l = null;
                for (var i = 1; i <= last; i++) {
                    if (i === 1 || i === last || (i >= left && i <= right)) {
                        range.push(i);
                    }
                }
                for (var j = 0; j < range.length; j++) {
                    if (l !== null && range[j] - l !== 1) {
                        rangeWithDots.push('...');
                    }
                    rangeWithDots.push(range[j]);
                    l = range[j];
                }
                var html = '<ul class="pagination justify-content-end flex-wrap">';
                if (current > 1) {
                    html += '<li class="page-item"><a class="page-link page-link-ajax" href="javascript:void(0)" data-page="' + (current - 1) + '"><i class="las la-arrow-left"></i></a></li>';
                }
                for (var k = 0; k < rangeWithDots.length; k++) {
                    if (rangeWithDots[k] === '...') {
                        html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    } else if (rangeWithDots[k] === current) {
                        html += '<li class="page-item active"><span class="page-link">' + current + '</span></li>';
                    } else {
                        html += '<li class="page-item"><a class="page-link page-link-ajax" href="javascript:void(0)" data-page="' + rangeWithDots[k] + '">' + rangeWithDots[k] + '</a></li>';
                    }
                }
                if (current < last) {
                    html += '<li class="page-item"><a class="page-link page-link-ajax" href="javascript:void(0)" data-page="' + (current + 1) + '"><i class="las la-arrow-right"></i></a></li>';
                }
                html += '</ul>';
                return html;
            }

            function loadVisibleMedia() {
                var listVisible = !$('#uploads-list-wrap').hasClass('d-none');
                if (listVisible) {
                    $('#uploads-list-wrap').find('img.upload-media[data-src], video.upload-media[data-src]').each(function () {
                        var $el = $(this);
                        var url = $el.attr('data-src');
                        if (url) { $el.attr('src', url); $el.removeAttr('data-src'); }
                    });
                } else {
                    $('#uploads-grid').find('img.upload-media[data-src], video.upload-media[data-src]').each(function () {
                        var $el = $(this);
                        var url = $el.attr('data-src');
                        if (url) { $el.attr('src', url); $el.removeAttr('data-src'); }
                    });
                }
            }

            function applyView(mode) {
                var normalized = mode === 'list' ? 'list' : 'grid';
                $('#view_mode_input').val(normalized);
                localStorage.setItem('aiz_upload_view', normalized);
                $('#uploads-grid').closest('.view-grid').toggleClass('d-none', normalized === 'list');
                $('#uploads-list-wrap').toggleClass('d-none', normalized !== 'list');
                $('.view-toggle').removeClass('btn-primary').addClass('btn-outline-secondary');
                $('.view-toggle[data-view="' + normalized + '"]').addClass('btn-primary').removeClass('btn-outline-secondary');
                loadVisibleMedia();
            }

            function getParams(page) {
                var p = {
                    page: page || state.currentPage,
                    sort_by: $('#sort_by').val(),
                    sort_order: $('#sort_order').val(),
                    view: $('#view_mode_input').val(),
                    search: $('#search_input').val(),
                    type: $('#type_filter').val()
                };
                return p;
            }

            function loadFiles(page) {
                state.currentPage = page || 1;
                var params = getParams(state.currentPage);
                $('#uploads-loading').removeClass('d-none');
                $('#uploads-content').addClass('d-none');
                $('#uploads-empty').addClass('d-none');

                $.ajax({
                    url: state.routes.index,
                    data: params,
                    dataType: 'json',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                }).done(function (resp) {
                    $('#uploads-loading').addClass('d-none');
                    var data = resp.data || [];
                    var meta = resp.meta || {};
                    if (data.length === 0) {
                        $('#uploads-empty').removeClass('d-none');
                        $('#uploads-pagination').empty();
                        return;
                    }
                    $('#uploads-content').removeClass('d-none');
                    var gridHtml = data.map(renderGridItem).join('');
                    $('#uploads-grid').html(gridHtml);
                    var listHtml = data.map(renderListRow).join('');
                    $('#uploads-list').html(listHtml);
                    $('#uploads-pagination').html(buildPaginationHtml(meta, resp.links));
                    applyView($('#view_mode_input').val() || 'grid');
                }).fail(function () {
                    $('#uploads-loading').addClass('d-none');
                    $('#uploads-empty').removeClass('d-none').find('p').text('{{ translate("Failed to load files.") }}');
                });
            }

            var storedView = localStorage.getItem('aiz_upload_view');
            applyView(storedView || $('#view_mode_input').val() || 'grid');

            $('.view-toggle').on('click', function () {
                applyView($(this).data('view'));
            });

            $('#sort_uploads').on('submit', function (e) { e.preventDefault(); loadFiles(1); });
            $('#apply-filters').on('click', function () { loadFiles(1); });
            $('#sort_select').on('change', function () {
                var parts = $(this).val().split('|');
                $('#sort_by').val(parts[0]);
                $('#sort_order').val(parts[1]);
                loadFiles(1);
            });
            $('#type_filter').on('change', function () { loadFiles(1); });

            $(document).on('click', '.table-sort-trigger', function (e) {
                e.preventDefault();
                var column = $(this).data('sort');
                var current = $('#sort_by').val();
                var order = $('#sort_order').val() === 'asc' ? 'desc' : 'asc';
                if (current !== column) order = 'asc';
                $('#sort_by').val(column);
                $('#sort_order').val(order);
                loadFiles(1);
            });

            $('#reset-filters').on('click', function () {
                $('#search_input').val('');
                $('#type_filter').val('').trigger('change');
                $('#sort_by').val('created_at');
                $('#sort_order').val('desc');
                $('#sort_select').val('created_at|desc').trigger('change');
                if (typeof $('.aiz-selectpicker').selectpicker === 'function') {
                    $('#type_filter').selectpicker('refresh');
                    $('#sort_select').selectpicker('refresh');
                }
                applyView('grid');
                localStorage.removeItem('aiz_upload_view');
                loadFiles(1);
            });

            $(document).on('click', '.page-link-ajax', function (e) {
                e.preventDefault();
                var page = $(this).data('page');
                if (page) loadFiles(page);
            });

            $(document).on("change", ".check-all", function () {
                $('.check-one:checkbox').prop('checked', this.checked);
            });

            $(document).on("change", ".check-one", function () {
                var value = $(this).val();
                var checked = $(this).prop('checked');
                $('.check-one[value="' + value + '"]').prop('checked', checked);
            });

            $(document).on('click', '.copy-link-btn', function () {
                var url = $(this).data('url');
                var $temp = $("<input>");
                $("body").append($temp);
                $temp.val(url).select();
                try {
                    document.execCommand("copy");
                    AIZ.plugins.notify('success', state.strings.linkCopied);
                } catch (err) {
                    AIZ.plugins.notify('danger', state.strings.copyFailed);
                }
                $temp.remove();
            });

            $(document).on('click', '.details-info-btn', function () {
                var id = $(this).data('id');
                $('#info-modal-content').html('<div class="c-preloader text-center absolute-center"><i class="las la-spinner la-spin la-3x opacity-70"></i></div>');
                $('#info-modal').modal('show');
                $.post(state.routes.info, { _token: AIZ.data.csrf, id: id }, function (data) {
                    $('#info-modal-content').html(data);
                });
            });

            $(document).on('click', '.confirm-delete', function (e) {
                e.preventDefault();
                var url = $(this).data('href');
                $('#delete-modal').modal('show');
                $('#delete-link').off('click.uploadedFiles').on('click.uploadedFiles', function (e) {
                    e.preventDefault();
                    $.ajax({
                        url: url,
                        type: 'GET',
                        data: { _token: AIZ.data.csrf },
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        success: function (resp) {
                        $('#delete-modal').modal('hide');
                        if (resp && resp.success) {
                            AIZ.plugins.notify('success', resp.message || state.strings.deleteSuccess);
                            loadFiles(state.currentPage);
                        }
                    },
                        error: function () {
                            $('#delete-modal').modal('hide');
                            loadFiles(state.currentPage);
                        }
                    });
                });
                $('#delete-link').attr('href', url);
            });

            window.bulk_delete = function () {
                var ids = [];
                $('.check-one:checked').each(function () { ids.push($(this).val()); });
                var uniqueIds = ids.filter(function (v, i, a) { return a.indexOf(v) === i; });
                var data = new FormData();
                data.append('_token', $('meta[name="csrf-token"]').attr('content'));
                $.each(uniqueIds, function (i, id) { data.append('id[]', id); });
                $.ajax({
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    url: state.routes.bulkDelete,
                    type: 'POST',
                    data: data,
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function (response) {
                        $('#bulk-delete-modal').modal('hide');
                        if (response == 1) {
                            AIZ.plugins.notify('success', '{{ translate("Deleted successfully") }}');
                            loadFiles(state.currentPage);
                        } else {
                            AIZ.plugins.notify('danger', state.strings.bulkDeleteFailed);
                        }
                    },
                    error: function (xhr) {
                        $('#bulk-delete-modal').modal('hide');
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : state.strings.bulkDeleteFailed;
                        AIZ.plugins.notify('danger', msg);
                    }
                });
            };

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
                $.post(state.renameTarget.route, { _token: AIZ.data.csrf, new_name: newName }).done(function (resp) {
                    $('#rename-modal').modal('hide');
                    if (resp.file) updateFileRow(resp.file);
                    AIZ.plugins.notify('success', resp.message || state.strings.renameSuccess);
                }).fail(function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : state.strings.renameFailed;
                    AIZ.plugins.notify('danger', msg);
                });
            });

            function updateFileRow(file) {
                var selector = '[data-file-row="' + file.id + '"]';
                $(selector).find('.file-title-text').text(file.file_original_name);
                $(selector).find('.ext').text('.' + file.extension);
                $(selector).find('.file-download-link').attr('href', file.full_path).attr('download', file.file_original_name + '.' + file.extension);
                $(selector).find('.copy-link-btn').data('url', file.full_path);
                $(selector).find('.rename-file-action').data('name', file.file_original_name).data('ext', file.extension);
            }

            loadFiles(1);
        })();
    </script>
@endsection
