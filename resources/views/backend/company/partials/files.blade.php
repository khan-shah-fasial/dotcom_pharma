@php
    $blankFileRow = ['name' => '', 'valid_until' => '', 'upload_id' => ''];
    $fileSections = [
        'certificates' => 'Certificates',
        'documents' => 'Important Documents',
    ];

    $savedFileRows = function (string $relation) use ($company, $filesReady, $blankFileRow) {
        $oldRows = old($relation);
        if (is_array($oldRows)) {
            return $oldRows === [] ? [$blankFileRow] : array_values($oldRows);
        }

        if (!empty($filesReady) && isset($company) && $company->exists && $company->relationLoaded($relation)) {
            $rows = $company->{$relation}->map(function ($file) {
                return [
                    'name' => $file->name,
                    'valid_until' => optional($file->valid_until)->format('Y-m-d'),
                    'upload_id' => $file->upload_id,
                ];
            })->all();

            return $rows === [] ? [$blankFileRow] : $rows;
        }

        return [$blankFileRow];
    };
@endphp

@if (!empty($filesReady))
    @foreach ($fileSections as $section => $label)
        <div class="form-group row company-file-section" data-section="{{ $section }}">
            <label class="col-md-3 col-form-label">{{ translate($label) }}</label>
            <div class="col-md-9">
                <div class="company-file-rows">
                    @foreach ($savedFileRows($section) as $index => $row)
                        <div class="border rounded p-3 mb-2 company-file-row">
                            <div class="row gutters-5">
                                <div class="col-md-4 mb-2">
                                    <label>{{ translate('Name') }}</label>
                                    <input type="text" class="form-control" maxlength="255"
                                        name="{{ $section }}[{{ $index }}][name]"
                                        value="{{ $row['name'] ?? '' }}">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label>{{ translate('Validity') }}</label>
                                    <input type="date" class="form-control"
                                        name="{{ $section }}[{{ $index }}][valid_until]"
                                        value="{{ $row['valid_until'] ?? '' }}">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label>{{ translate('File') }}</label>
                                    <div class="input-group" data-toggle="aizuploader" data-type="all">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                                        </div>
                                        <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                                        <input type="hidden" name="{{ $section }}[{{ $index }}][upload_id]"
                                            value="{{ $row['upload_id'] ?? '' }}" class="selected-files">
                                    </div>
                                    <div class="file-preview box sm"></div>
                                </div>
                                <div class="col-md-1 mb-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-soft-danger btn-icon btn-sm company-file-remove" title="{{ translate('Remove') }}">
                                        <i class="las la-times"></i>
                                    </button>
                                </div>
                            </div>
                            @error($section . '.' . $index . '.name') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                            @error($section . '.' . $index . '.valid_until') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                            @error($section . '.' . $index . '.upload_id') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-soft-primary btn-sm company-file-add">
                    {{ translate('Add More') }}
                </button>
            </div>
        </div>
    @endforeach
@else
    <div class="alert alert-soft-warning">
        {{ translate('Certificates and Important Documents are waiting for the database update. Run the company files SQL, then reload this page.') }}
    </div>
@endif

@once
    @push('company_scripts')
        <script>
            $(document).ready(function () {
                function reindexCompanyFileRows($section) {
                    var section = $section.data('section');
                    $section.find('.company-file-row').each(function (index) {
                        $(this).find('[name]').each(function () {
                            var name = $(this).attr('name');
                            if (!name) {
                                return;
                            }
                            $(this).attr('name', name.replace(/\[[0-9]+\]/, '[' + index + ']'));
                        });
                    });
                }

                $(document).on('click', '.company-file-add', function () {
                    var $section = $(this).closest('.company-file-section');
                    var $rows = $section.find('.company-file-rows');
                    var $clone = $rows.find('.company-file-row').first().clone();
                    $clone.find('input').val('');
                    $clone.find('.file-amount').text(@json(translate('Choose File')));
                    $clone.find('.file-preview').empty();
                    $clone.find('.text-danger').remove();
                    $rows.append($clone);
                    reindexCompanyFileRows($section);
                });

                $(document).on('click', '.company-file-remove', function () {
                    var $section = $(this).closest('.company-file-section');
                    var $rows = $section.find('.company-file-row');
                    if ($rows.length === 1) {
                        $rows.find('input').val('');
                        $rows.find('.file-amount').text(@json(translate('Choose File')));
                        $rows.find('.file-preview').empty();
                        return;
                    }
                    $(this).closest('.company-file-row').remove();
                    reindexCompanyFileRows($section);
                });
            });
        </script>
    @endpush
@endonce
