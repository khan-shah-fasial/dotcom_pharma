@extends('backend.layouts.app')

@section('content')
@php
    $parentKindMap = \App\Models\ContactClassification::KINDS;
@endphp
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">{{ translate('Contact Master') }}</h1></div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('contact-directory.index') }}" class="btn btn-primary">{{ translate('Contact List') }}</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="{{ auth()->user()->can('add_contact_directory') ? 'col-lg-7' : 'col-lg-12' }}">
        <div class="card">
            <div class="card-header"><h5 class="mb-0 h6">{{ translate('Classification Tree') }}</h5></div>
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ translate('Name') }}</th>
                            <th>{{ translate('Kind') }}</th>
                            <th>{{ translate('Parent') }}</th>
                            <th>{{ translate('Contacts') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th class="text-right">{{ translate('Options') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($classifications as $key => $classification)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td style="padding-left: {{ 12 + ((int) ($classification->depth ?? 0) * 18) }}px;">
                                    {{ $classification->name }}
                                </td>
                                <td>{{ translate($classification->kindLabel()) }}</td>
                                <td>{{ optional($classification->parent)->name ?? '-' }}</td>
                                <td>{{ $classification->usage_count ?? 0 }}</td>
                                <td>
                                    @can('edit_contact_directory')
                                        <label class="aiz-switch aiz-switch-success mb-0">
                                            <input onchange="update_classification_status(this)" value="{{ $classification->id }}" type="checkbox" @checked($classification->status == 1)>
                                            <span class="slider round"></span>
                                        </label>
                                    @else
                                        <span class="badge badge-inline badge-{{ $classification->status ? 'success' : 'secondary' }}">{{ $classification->status ? translate('Active') : translate('Inactive') }}</span>
                                    @endcan
                                </td>
                                <td class="text-right">
                                    @can('edit_contact_directory')
                                        <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('contact-classifications.edit', $classification->id) }}" title="{{ translate('Edit') }}">
                                            <i class="las la-edit"></i>
                                        </a>
                                    @endcan
                                    @can('delete_contact_directory')
                                        <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('contact-classifications.destroy', $classification->id) }}" title="{{ translate('Delete') }}">
                                            <i class="las la-trash"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">{{ translate('No master items found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @can('add_contact_directory')
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h5 class="mb-0 h6">{{ translate('Add Master Item') }}</h5></div>
                <div class="card-body">
                    <form action="{{ route('contact-classifications.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>{{ translate('Kind') }} <span class="text-danger">*</span></label>
                            <select name="kind" id="classification_kind" class="form-control aiz-selectpicker" required>
                                @foreach ($kinds as $kind => $label)
                                    <option value="{{ $kind }}" @selected(old('kind') === $kind)>{{ translate($label) }}</option>
                                @endforeach
                            </select>
                            @error('kind') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group" id="classification_parent_wrap">
                            <label>{{ translate('Parent') }} <span class="text-danger" id="classification_parent_required">*</span></label>
                            <select name="parent_id" id="classification_parent_id" class="form-control aiz-selectpicker" data-live-search="true">
                                <option value="">{{ translate('Select Parent') }}</option>
                            </select>
                            @error('parent_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Status') }}</label>
                            <select name="status" class="form-control aiz-selectpicker">
                                <option value="1">{{ translate('Active') }}</option>
                                <option value="0" @selected(old('status') === '0')>{{ translate('Inactive') }}</option>
                            </select>
                        </div>
                        <div class="text-right">
                            <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
</div>
@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection

@section('script')
<script>
    function update_classification_status(el) {
        if ('{{ env('DEMO_MODE') }}' == 'On') {
            AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
            return;
        }
        $.post('{{ route('contact-classifications.update_status') }}', {
            _token: '{{ csrf_token() }}',
            id: el.value,
            status: el.checked ? 1 : 0
        }, function(data) {
            AIZ.plugins.notify(data == 1 ? 'success' : 'danger', data == 1 ? '{{ translate('Status updated successfully') }}' : '{{ translate('Something went wrong') }}');
        });
    }

    (function () {
        var parentKindMap = @json($parentKindMap);
        var parentOptions = @json(collect($parentOptions)->map(function ($items) {
            return collect($items)->map(function ($item) {
                return ['id' => $item->id, 'name' => $item->name];
            })->values();
        }));
        var oldParentId = @json(old('parent_id'));
        var $kind = $('#classification_kind');
        var $parent = $('#classification_parent_id');
        var $wrap = $('#classification_parent_wrap');
        var $required = $('#classification_parent_required');

        function refreshParentOptions() {
            var kind = $kind.val();
            var parentKind = parentKindMap[kind] || null;
            $parent.empty().append($('<option>', { value: '', text: @json(translate('Select Parent')) }));

            if (!parentKind) {
                $wrap.hide();
                $parent.prop('required', false);
                $required.hide();
                $parent.selectpicker('refresh');
                return;
            }

            $wrap.show();
            $parent.prop('required', true);
            $required.show();
            $.each(parentOptions[kind] || [], function (index, item) {
                $parent.append($('<option>', { value: item.id, text: item.name }));
            });
            if (oldParentId) {
                $parent.val(String(oldParentId));
            }
            $parent.selectpicker('refresh');
        }

        $kind.on('change', function () {
            oldParentId = '';
            refreshParentOptions();
        });

        refreshParentOptions();
    })();
</script>
@endsection
