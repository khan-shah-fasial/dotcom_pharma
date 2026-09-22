@extends('backend.layouts.app')

@section('content')
@php
    $filtersApplied = collect($filters ?? [])->contains(fn ($v) => $v !== null && $v !== '');
    $typeName = function ($slug) use ($types) {
        $match = collect($types)->firstWhere('slug', $slug);
        return $match ? $match->name : $slug;
    };
@endphp
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('All Notes') }}</h1>
            </div>
            @can('add_note')
                <div class="col-md-6 text-md-right">
                    <a href="{{ route('note.create') }}" class="btn btn-circle btn-info">
                        <span>{{ translate('Add New Note') }}</span>
                    </a>
                </div>
            @endcan
        </div>
    </div>
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <div class="mb-2">
                <h5 class="mb-0 h6">{{ translate('notes') }}</h5>
                @if ($filtersApplied)
                    <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
                @endif
            </div>
            <div class="d-flex flex-wrap align-items-center">
                <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal" data-target="#noteFilterModal">
                    {{ translate('Open Filters') }}
                </button>
                <a href="{{ route('note.index') }}" class="btn btn-danger mb-2">{{ translate('Reset') }}</a>
            </div>
        </div>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        @include('backend.inc.sortable_th', ['column' => 'id', 'label' => '#', 'routeName' => 'note.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'note_type', 'label' => translate('Type'), 'routeName' => 'note.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'description', 'label' => translate('Description'), 'routeName' => 'note.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'updated_at', 'label' => translate('Updated'), 'routeName' => 'note.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        <th width="10%" class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($notes as $key => $note)
                        <tr>
                            <td>{{ $key + 1 + ($notes->currentPage() - 1) * $notes->perPage() }}</td>
                            <td>{{ translate($typeName($note->note_type)) }}</td>
                            <td>
                                <p class="text-truncate-2 mb-0">{{ $note->getTranslation('description') }}</p>
                            </td>
                            <td>{{ optional($note->updated_at)->format('d-m-Y H:i') }}</td>
                            <td class="text-right">
                                @can('edit_note')
                                    <a class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                        href="{{ route('note.edit', ['id' => $note->id, 'lang' => env('DEFAULT_LANGUAGE')]) }}"
                                        title="{{ translate('Edit') }}">
                                        <i class="las la-edit"></i>
                                    </a>
                                @endcan
                                @can('delete_note')
                                    <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete"
                                        data-href="{{ route('note.delete', $note->id) }}" title="{{ translate('Delete') }}">
                                        <i class="las la-trash"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">{{ translate('No notes found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="aiz-pagination">
                {{ $notes->appends(request()->input())->links() }}
            </div>
        </div>
    </div>
@endsection

@section('modal')
    @include('modals.delete_modal')

    <div class="modal fade" id="noteFilterModal" tabindex="-1" role="dialog" aria-labelledby="noteFilterModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="GET" action="{{ route('note.index') }}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="noteFilterModalLabel">{{ translate('Filter Notes') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>{{ translate('Type') }}</label>
                            <select name="note_type" class="form-control aiz-selectpicker" data-live-search="true">
                                <option value="">{{ translate('All Types') }}</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type->slug }}" @selected(($filters['note_type'] ?? '') === $type->slug)>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label>{{ translate('Description') }}</label>
                            <input type="text" name="description" class="form-control"
                                value="{{ $filters['description'] ?? '' }}"
                                placeholder="{{ translate('Search any word in description') }}">
                        </div>
                        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('note.index') }}" class="btn btn-light">{{ translate('Reset') }}</a>
                        <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
