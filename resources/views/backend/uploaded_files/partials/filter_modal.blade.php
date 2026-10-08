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
        'zip' => translate('Zip / Rar / 7z'),
        'document' => translate('Documents'),
    ];
    $dateRangeLabel = (request('date_from') && request('date_to'))
        ? request('date_from') . ' to ' . request('date_to')
        : '';
@endphp
<div class="modal fade" id="uploadedFilesFilterModal" tabindex="-1" role="dialog" aria-labelledby="uploadedFilesFilterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadedFilesFilterModalLabel">{{ translate('Filter uploaded files') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row gutters-5">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="upload_search">{{ translate('File name') }}</label>
                        <input type="text" class="form-control" id="upload_search" name="search" value="{{ $search }}" placeholder="{{ translate('Search by name or extension') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="type_filter">{{ translate('Type') }}</label>
                        <select id="type_filter" class="form-control aiz-selectpicker" name="type" data-live-search="true">
                            <option value="">{{ translate('All types') }}</option>
                            @foreach($typeOptions as $value => $label)
                                <option value="{{ $value }}" @selected(($typeFilter ?? null) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="upload_extension">{{ translate('Extension') }}</label>
                        <input type="text" class="form-control" id="upload_extension" name="extension" value="{{ $extension ?? '' }}" placeholder="{{ translate('jpg, pdf, mp4') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="size_min">{{ translate('Minimum size (KB)') }}</label>
                        <input type="number" min="0" step="1" class="form-control" id="size_min" name="size_min" value="{{ $sizeMin ?? '' }}" placeholder="{{ translate('Minimum size (KB)') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="size_max">{{ translate('Maximum size (KB)') }}</label>
                        <input type="number" min="0" step="1" class="form-control" id="size_max" name="size_max" value="{{ $sizeMax ?? '' }}" placeholder="{{ translate('Maximum size (KB)') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="per_page">{{ translate('Files per page') }}</label>
                        <select class="form-control aiz-selectpicker" id="per_page" name="per_page">
                            @foreach([30, 60, 120, 240] as $pageSize)
                                <option value="{{ $pageSize }}" @selected((int) ($perPage ?? 60) === $pageSize)>{{ $pageSize }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($showUploader ?? false)
                        <div class="col-md-4 mb-3">
                            <label class="form-label" for="upload_uploader">{{ translate('Uploaded by') }}</label>
                            <input type="text" class="form-control" id="upload_uploader" name="uploader" value="{{ $uploader ?? '' }}" placeholder="{{ translate('User name') }}">
                        </div>
                    @endif
                    <div class="col-md-8 mb-3">
                        <label class="form-label" for="uploaded_date_range">{{ translate('Uploaded date') }}</label>
                        <input type="text" class="form-control aiz-date-range" id="uploaded_date_range" value="{{ $dateRangeLabel }}" data-time-picker="false" data-format="DD-MM-YYYY" data-separator=" to " placeholder="{{ translate('DD-MM-YYYY to DD-MM-YYYY') }}">
                        <input type="hidden" name="date_from" id="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" id="date_to" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="sort_by">{{ translate('Sort by') }}</label>
                        <select class="form-control aiz-selectpicker" id="sort_by" name="sort_by">
                            <option value="created_at" @selected(($sortBy ?? 'created_at') === 'created_at')>{{ translate('Uploaded date') }}</option>
                            <option value="updated_at" @selected(($sortBy ?? '') === 'updated_at')>{{ translate('Updated date') }}</option>
                            <option value="name" @selected(($sortBy ?? '') === 'name')>{{ translate('Name') }}</option>
                            <option value="extension" @selected(($sortBy ?? '') === 'extension')>{{ translate('Extension') }}</option>
                            <option value="type" @selected(($sortBy ?? '') === 'type')>{{ translate('Type') }}</option>
                            <option value="size" @selected(($sortBy ?? '') === 'size')>{{ translate('Size') }}</option>
                            @if($showUploader ?? false)
                                <option value="user" @selected(($sortBy ?? '') === 'user')>{{ translate('Uploaded by') }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="sort_order">{{ translate('Direction') }}</label>
                        <select class="form-control aiz-selectpicker" id="sort_order" name="sort_order">
                            <option value="asc" @selected(($sortOrder ?? 'desc') === 'asc')>{{ translate('Ascending') }}</option>
                            <option value="desc" @selected(($sortOrder ?? 'desc') === 'desc')>{{ translate('Descending') }}</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Close') }}</button>
                <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).on('apply.daterangepicker', '#uploaded_date_range', function (ev, picker) {
        $('#date_from').val(picker.startDate.format('DD-MM-YYYY'));
        $('#date_to').val(picker.endDate.format('DD-MM-YYYY'));
    });
    $(document).on('cancel.daterangepicker', '#uploaded_date_range', function () {
        $('#date_from').val('');
        $('#date_to').val('');
    });
    $('#uploadedFilesFilterModal').on('shown.bs.modal', function () {
        if ($.fn.selectpicker) {
            $(this).find('.aiz-selectpicker').selectpicker('refresh');
        }
    });
</script>
