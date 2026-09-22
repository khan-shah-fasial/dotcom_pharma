@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Note Type Master') }}</h1>
            </div>
            @can('add_note')
                <div class="col-md-6 text-md-right">
                    <a href="{{ route('note_types.create') }}" class="btn btn-circle btn-info">
                        <span>{{ translate('Add New Type') }}</span>
                    </a>
                </div>
            @endcan
        </div>
    </div>

    @if (!$tableReady)
        <div class="alert alert-warning">
            {{ translate('Note Type Master table is not ready yet. Run sqlupdates/note_types_master.sql on the database.') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header d-block d-md-flex justify-content-between align-items-center">
            <h5 class="mb-0 h6">{{ translate('All Note Types') }}</h5>
            <form class="mt-2 mt-md-0" method="GET" action="{{ route('note_types.index') }}">
                <div class="input-group" style="min-width: 220px;">
                    <input type="text" class="form-control" name="search" value="{{ $search ?? '' }}"
                        placeholder="{{ translate('Search name / slug') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">{{ translate('Search') }}</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        @include('backend.inc.sortable_th', ['column' => 'id', 'label' => '#', 'routeName' => 'note_types.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'name', 'label' => translate('Name'), 'routeName' => 'note_types.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'slug', 'label' => translate('Slug'), 'routeName' => 'note_types.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'status', 'label' => translate('Status'), 'routeName' => 'note_types.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'updated_at', 'label' => translate('Updated'), 'routeName' => 'note_types.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($tableReady && $types && $types->count())
                        @foreach ($types as $key => $type)
                            <tr>
                                <td>{{ $key + 1 + ($types->currentPage() - 1) * $types->perPage() }}</td>
                                <td>{{ $type->name }}</td>
                                <td><code>{{ $type->slug }}</code></td>
                                <td>
                                    <label class="aiz-switch aiz-switch-success mb-0">
                                        <input type="checkbox" onchange="updateNoteTypeStatus(this)" value="{{ $type->id }}"
                                            {{ $type->status ? 'checked' : '' }} @cannot('edit_note') disabled @endcannot>
                                        <span class="slider round"></span>
                                    </label>
                                </td>
                                <td>{{ optional($type->updated_at)->format('d-m-Y H:i') }}</td>
                                <td class="text-right">
                                    @can('edit_note')
                                        <a class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                            href="{{ route('note_types.edit', $type->id) }}" title="{{ translate('Edit') }}">
                                            <i class="las la-edit"></i>
                                        </a>
                                    @endcan
                                    @can('delete_note')
                                        <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete"
                                            data-href="{{ route('note_types.destroy', $type->id) }}"
                                            title="{{ translate('Delete') }}">
                                            <i class="las la-trash"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="text-center text-muted">{{ translate('No note types found') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
            @if ($tableReady && $types)
                <div class="aiz-pagination">
                    {{ $types->appends(request()->input())->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection

@section('script')
    <script>
        function updateNoteTypeStatus(el) {
            $.post('{{ route('note_types.update_status') }}', {
                _token: '{{ csrf_token() }}',
                id: el.value,
                status: el.checked ? 1 : 0
            });
        }
    </script>
@endsection
