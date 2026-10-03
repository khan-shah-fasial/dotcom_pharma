@extends('backend.layouts.app')

@section('content')
    @php
        $sortIcon = function ($column) use ($sortBy, $sortOrder) {
            if ($sortBy === $column) {
                return $sortOrder === 'asc' ? 'las la-sort-amount-up' : 'las la-sort-amount-down';
            }
            return 'las la-sort';
        };
        $sortValue = ($sortBy ?? 'created_at') . '|' . ($sortOrder ?? 'desc');
    @endphp

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
                    @if(optional($user->details)->account_no_business)
                        <span class="ml-2 text-secondary">| {{ optional($user->details)->account_no_business }}</span>
                    @endif
                </p>
            </div>
            <div class="col-md-6 text-md-right">
                <button type="button" class="btn btn-circle btn-info" data-toggle="modal" data-target="#add-archive-modal">
                    <span>{{ translate('Add New Archive') }}</span>
                </button>
                <a href="{{ route('customers.business') }}" class="btn btn-soft-secondary">
                    {{ translate('Back to Business Customers') }}
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <form id="archive-filters" method="GET">
            <input type="hidden" name="sort_by" id="sort_by" value="{{ $sortBy }}">
            <input type="hidden" name="sort_order" id="sort_order" value="{{ $sortOrder }}">
            <div class="card-header row gutters-5 align-items-center">
                <div class="col-md-2">
                    <select name="type" class="form-control form-control-xs aiz-selectpicker" data-live-search="true">
                        <option value="">{{ translate('All Types') }}</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected($filterType == $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="extension" class="form-control form-control-xs aiz-selectpicker" data-live-search="true">
                        <option value="">{{ translate('All extensions') }}</option>
                        @foreach ($extensions as $extension)
                            <option value="{{ $extension }}" @selected(strtolower((string) $filterExtension) === $extension)>.{{ $extension }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="sort_select" class="form-control form-control-xs aiz-selectpicker">
                        <option value="created_at|desc" @selected($sortValue === 'created_at|desc')>{{ translate('Newest first') }}</option>
                        <option value="created_at|asc" @selected($sortValue === 'created_at|asc')>{{ translate('Oldest first') }}</option>
                        <option value="type|asc" @selected($sortValue === 'type|asc')>{{ translate('Type A-Z') }}</option>
                        <option value="type|desc" @selected($sortValue === 'type|desc')>{{ translate('Type Z-A') }}</option>
                        <option value="name|asc" @selected($sortValue === 'name|asc')>{{ translate('Name A-Z') }}</option>
                        <option value="name|desc" @selected($sortValue === 'name|desc')>{{ translate('Name Z-A') }}</option>
                        <option value="extension|asc" @selected($sortValue === 'extension|asc')>{{ translate('Extension A-Z') }}</option>
                        <option value="extension|desc" @selected($sortValue === 'extension|desc')>{{ translate('Extension Z-A') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control form-control-xs" value="{{ $filterSearch }}" placeholder="{{ translate('Search by file name') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">{{ translate('Filter') }}</button>
                    <button type="button" class="btn btn-secondary" id="reset-archive-filters">{{ translate('Reset') }}</button>
                </div>
            </div>
        </form>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th class="table-sort-trigger c-pointer" data-sort="type">
                            {{ translate('Type') }} <i class="{{ $sortIcon('type') }}"></i>
                        </th>
                        <th>{{ translate('Preview') }}</th>
                        <th class="table-sort-trigger c-pointer" data-sort="name">
                            {{ translate('File name') }} <i class="{{ $sortIcon('name') }}"></i>
                        </th>
                        <th class="table-sort-trigger c-pointer" data-sort="extension">
                            {{ translate('Extension') }} <i class="{{ $sortIcon('extension') }}"></i>
                        </th>
                        <th class="text-right">{{ translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $previewIndex = 0; @endphp
                    @forelse ($archives as $key => $archive)
                        @php
                            $upload = $archive->upload;
                            $fileName = $upload->file_original_name ?? '';
                            $extension = strtolower((string) ($upload->extension ?? ''));
                            $fileUrl = $upload ? uploaded_asset($archive->upload_id) : '';
                            $iconClass = 'las la-file';
                            $kind = 'file';
                            if ($upload) {
                                if (($upload->type ?? '') === 'image' || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'])) {
                                    $kind = 'image';
                                } elseif ($extension === 'pdf') {
                                    $kind = 'pdf';
                                    $iconClass = 'las la-file-pdf';
                                } elseif (($upload->type ?? '') === 'video') {
                                    $iconClass = 'las la-file-video';
                                } elseif (($upload->type ?? '') === 'audio') {
                                    $iconClass = 'las la-file-audio';
                                } elseif (($upload->type ?? '') === 'archive' || in_array($extension, ['zip', 'rar', '7z'])) {
                                    $iconClass = 'las la-file-archive';
                                } elseif (in_array($extension, ['doc', 'docx'])) {
                                    $iconClass = 'las la-file-word';
                                } elseif (in_array($extension, ['xls', 'xlsx', 'ods'])) {
                                    $iconClass = 'las la-file-excel';
                                } elseif ($extension === 'csv') {
                                    $iconClass = 'las la-file-csv';
                                }
                            }
                        @endphp
                        <tr>
                            <td>{{ $key + 1 + ($archives->currentPage() - 1) * $archives->perPage() }}</td>
                            <td>{{ $types[$archive->type] ?? $archive->type }}</td>
                            <td>
                                @if ($upload)
                                    <button type="button"
                                        class="btn btn-link p-0 archive-preview-trigger"
                                        data-index="{{ $previewIndex }}"
                                        data-kind="{{ $kind }}"
                                        data-url="{{ $fileUrl }}"
                                        data-name="{{ $fileName }}"
                                        data-ext="{{ $extension }}"
                                        data-icon="{{ $iconClass }}"
                                        title="{{ translate('Preview') }}">
                                        @if ($kind === 'image')
                                            <img src="{{ $fileUrl }}" class="archive-thumb" alt="{{ $fileName }}">
                                        @else
                                            <span class="archive-thumb archive-thumb-icon">
                                                <i class="{{ $iconClass }}"></i>
                                            </span>
                                        @endif
                                    </button>
                                    @php $previewIndex++; @endphp
                                @else
                                    <span class="text-danger">{{ translate('File missing') }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($upload)
                                    {{ $fileName !== '' ? $fileName : translate('View File') }}
                                @else
                                    <span class="text-danger">{{ translate('File missing') }}</span>
                                @endif
                            </td>
                            <td>{{ $extension !== '' ? '.' . $extension : '—' }}</td>
                            <td class="text-right">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-soft-secondary btn-icon" type="button" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false" title="{{ translate('Actions') }}">
                                        <i class="la la-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        @if ($upload)
                                            <a href="javascript:void(0)" class="dropdown-item rename-archive-action"
                                                data-route="{{ route('financial-archives.rename', $archive->id) }}"
                                                data-id="{{ $archive->id }}"
                                                data-name="{{ $fileName }}">
                                                <i class="las la-i-cursor mr-2"></i>
                                                <span>{{ translate('Rename') }}</span>
                                            </a>
                                        @endif
                                        <a href="javascript:void(0)" class="dropdown-item move-archive-action"
                                            data-route="{{ route('financial-archives.move', $archive->id) }}"
                                            data-id="{{ $archive->id }}"
                                            data-name="{{ $fileName }}">
                                            <i class="las la-exchange-alt mr-2"></i>
                                            <span>{{ translate('Move to other account no') }}</span>
                                        </a>
                                        <a href="javascript:void(0)" class="dropdown-item confirm-delete" data-href="{{ route('financial-archives.destroy', $archive->id) }}" data-target="#delete-modal">
                                            <i class="las la-trash mr-2"></i>
                                            <span>{{ translate('Delete') }}</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ translate('No archives found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination">
                {{ $archives->links() }}
            </div>
        </div>
    </div>

    <style>
        .archive-thumb {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            background: #f8fafc;
        }
        .archive-thumb-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #2b56a1;
            font-size: 28px;
        }
        .archive-preview-stage {
            height: 70vh;
            background: #111827;
            overflow: auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .archive-preview-stage img {
            max-width: 100%;
            height: auto;
            transform-origin: center center;
        }
        .archive-preview-stage iframe {
            width: 100%;
            height: 70vh;
            border: 0;
            background: #fff;
        }
        .archive-preview-file {
            color: #fff;
            text-align: center;
            padding: 40px 16px;
        }
        .archive-preview-file i {
            font-size: 72px;
        }
        .table-sort-trigger {
            white-space: nowrap;
        }
    </style>
@endsection

@section('modal')
    <div class="modal fade" id="add-archive-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h6">{{ translate('Add New Archive') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('financial-archives.customer.store', $user->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                    <input type="hidden" name="form" value="create">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">{{ translate('Type') }}</label>
                            <select name="type" class="form-control aiz-selectpicker" required>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}" @selected(old('form') === 'create' && old('type') == $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Attachment') }}</label>
                            <input type="file" name="file" class="form-control" required
                                accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.svg,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.xml,.zip,.rar,.7z,image/*">
                            <small class="text-muted d-block mt-1">{{ translate('Images and documents are accepted.') }}</small>
                            @error('file') <div class="text-danger small">{{ $message }}</div> @enderror
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

    <div class="modal fade" id="rename-archive-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="rename-archive-form" method="POST" action="{{ old('form') === 'rename' && old('archive_id') ? route('financial-archives.rename', old('archive_id')) : '' }}">
                    @csrf
                    <input type="hidden" name="form" value="rename">
                    <input type="hidden" name="archive_id" id="rename-archive-id" value="{{ old('form') === 'rename' ? old('archive_id') : '' }}">
                    <div class="modal-header">
                        <h5 class="modal-title h6">{{ translate('Rename File') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('New name (without extension)') }}</label>
                            <input type="text" class="form-control" name="new_name" id="rename-new-name" value="{{ old('form') === 'rename' ? old('new_name') : '' }}" autocomplete="off" required>
                            <small class="text-muted d-block mt-1">{{ translate('Extension will stay the same') }}</small>
                            @error('new_name') <div class="text-danger small">{{ $message }}</div> @enderror
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

    <div class="modal fade" id="move-archive-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="move-archive-form" method="POST" action="{{ old('form') === 'move' && old('archive_id') ? route('financial-archives.move', old('archive_id')) : '' }}">
                    @csrf
                    <input type="hidden" name="form" value="move">
                    <input type="hidden" name="archive_id" id="move-archive-id" value="{{ old('form') === 'move' ? old('archive_id') : '' }}">
                    <div class="modal-header">
                        <h5 class="modal-title h6">{{ translate('Move to other account no') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2 text-muted" id="move-archive-label"></p>
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Account No') }}</label>
                            <input type="text" class="form-control" name="account_no" id="move-account-no" value="{{ old('form') === 'move' ? old('account_no') : '' }}" autocomplete="off" required>
                            @error('account_no') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ translate('Move') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="archive-preview-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h6" id="archive-preview-title"></h5>
                    <div class="d-flex align-items-center">
                        <span class="text-muted mr-3" id="archive-preview-count"></span>
                        <button type="button" class="btn btn-sm btn-soft-secondary mr-1" id="archive-zoom-out" title="{{ translate('Zoom out') }}">
                            <i class="las la-search-minus"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-soft-secondary mr-2" id="archive-zoom-in" title="{{ translate('Zoom in') }}">
                            <i class="las la-search-plus"></i>
                        </button>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-0">
                    <div class="d-flex align-items-stretch">
                        <button type="button" class="btn btn-link px-3" id="archive-preview-prev" title="{{ translate('Previous') }}">
                            <i class="las la-angle-left la-2x"></i>
                        </button>
                        <div class="archive-preview-stage flex-grow-1" id="archive-preview-stage">
                            <img src="" alt="" id="archive-preview-image" class="d-none">
                            <iframe id="archive-preview-frame" class="d-none" title="{{ translate('Preview') }}"></iframe>
                            <div id="archive-preview-file" class="archive-preview-file d-none">
                                <div><i id="archive-preview-file-icon" class="las la-file"></i></div>
                                <p class="mt-3 mb-3" id="archive-preview-file-name"></p>
                                <a href="#" class="btn btn-primary" id="archive-preview-download" target="_blank" rel="noopener">{{ translate('Download') }}</a>
                            </div>
                        </div>
                        <button type="button" class="btn btn-link px-3" id="archive-preview-next" title="{{ translate('Next') }}">
                            <i class="las la-angle-right la-2x"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('modals.delete_modal')
@endsection

@section('script')
    <script type="text/javascript">
        (function () {
            var previewItems = [];
            var previewIndex = 0;
            var zoom = 1;

            function collectPreviewItems() {
                previewItems = [];
                $('.archive-preview-trigger').each(function () {
                    previewItems.push({
                        kind: $(this).data('kind'),
                        url: $(this).data('url'),
                        name: $(this).data('name') || '',
                        ext: $(this).data('ext') || '',
                        icon: $(this).data('icon') || 'las la-file'
                    });
                });
            }

            function applyZoom() {
                var img = document.getElementById('archive-preview-image');
                img.style.transform = 'scale(' + zoom + ')';
            }

            function showPreview(index) {
                if (!previewItems.length) {
                    return;
                }
                previewIndex = (index + previewItems.length) % previewItems.length;
                var item = previewItems[previewIndex];
                var title = item.name || '{{ translate('Preview') }}';
                if (item.ext) {
                    title += '.' + item.ext;
                }
                $('#archive-preview-title').text(title);
                $('#archive-preview-count').text((previewIndex + 1) + ' / ' + previewItems.length);
                $('#archive-preview-image').addClass('d-none').attr('src', '');
                $('#archive-preview-frame').addClass('d-none').attr('src', '');
                $('#archive-preview-file').addClass('d-none');
                $('#archive-zoom-in, #archive-zoom-out').toggleClass('d-none', item.kind !== 'image');
                zoom = 1;
                applyZoom();

                if (item.kind === 'image') {
                    $('#archive-preview-image').removeClass('d-none').attr('src', item.url).attr('alt', title);
                } else if (item.kind === 'pdf') {
                    $('#archive-preview-frame').removeClass('d-none').attr('src', item.url);
                } else {
                    $('#archive-preview-file-icon').attr('class', item.icon);
                    $('#archive-preview-file-name').text(title);
                    $('#archive-preview-download').attr('href', item.url);
                    $('#archive-preview-file').removeClass('d-none');
                }
            }

            $('#sort_select').on('change', function () {
                var parts = String($(this).val() || 'created_at|desc').split('|');
                $('#sort_by').val(parts[0]);
                $('#sort_order').val(parts[1] || 'desc');
                $('#archive-filters').trigger('submit');
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
                $('#archive-filters').trigger('submit');
            });

            $('#reset-archive-filters').on('click', function () {
                $('#archive-filters').find('[name="type"]').val('');
                $('#archive-filters').find('[name="extension"]').val('');
                $('#archive-filters').find('[name="search"]').val('');
                $('#sort_by').val('created_at');
                $('#sort_order').val('desc');
                $('#archive-filters').trigger('submit');
            });

            $(document).on('click', '.rename-archive-action', function () {
                $('#rename-archive-form').attr('action', $(this).data('route'));
                $('#rename-archive-id').val($(this).data('id'));
                $('#rename-new-name').val($(this).data('name') || '');
                $('#rename-archive-modal').modal('show');
            });

            $(document).on('click', '.move-archive-action', function () {
                var name = $(this).data('name') || '';
                $('#move-archive-form').attr('action', $(this).data('route'));
                $('#move-archive-id').val($(this).data('id'));
                $('#move-account-no').val('');
                $('#move-archive-label').text(name);
                $('#move-archive-modal').modal('show');
            });

            $(document).on('click', '.archive-preview-trigger', function () {
                collectPreviewItems();
                showPreview(parseInt($(this).data('index'), 10) || 0);
                $('#archive-preview-modal').modal('show');
            });

            $('#archive-preview-prev').on('click', function () {
                showPreview(previewIndex - 1);
            });
            $('#archive-preview-next').on('click', function () {
                showPreview(previewIndex + 1);
            });
            $('#archive-zoom-in').on('click', function () {
                zoom = Math.min(4, Math.round((zoom + 0.25) * 100) / 100);
                applyZoom();
            });
            $('#archive-zoom-out').on('click', function () {
                zoom = Math.max(1, Math.round((zoom - 0.25) * 100) / 100);
                applyZoom();
            });

            $('#archive-preview-modal').on('hidden.bs.modal', function () {
                $('#archive-preview-frame').attr('src', '');
                $('#archive-preview-image').attr('src', '');
            });

            @if ($errors->any() && old('form') === 'create')
                $('#add-archive-modal').modal('show');
            @elseif ($errors->any() && old('form') === 'rename')
                $('#rename-archive-modal').modal('show');
            @elseif ($errors->any() && old('form') === 'move')
                $('#move-archive-modal').modal('show');
            @endif
        })();
    </script>
@endsection
