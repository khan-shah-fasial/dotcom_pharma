@extends('backend.layouts.app')

@section('content')
@php
    $filtersApplied = collect($filters ?? [])->contains(fn ($value) => $value !== null && $value !== '');
@endphp
<style>
    .gift-list-thumbs {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        max-width: 240px;
    }
    .gift-list-thumb {
        width: 72px;
        height: 72px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #e4e5eb;
        background: #f8f9fb;
        cursor: zoom-in;
    }
    .gift-enlarge-img {
        display: block;
        max-width: 100%;
        max-height: 75vh;
        margin: 0 auto;
        object-fit: contain;
    }
    .gift-description-clamp {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        max-width: 280px;
        overflow: hidden;
        white-space: normal;
    }
</style>
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <div class="mb-2">
            <h6 class="mb-0">{{ translate('Gifts') }}</h6>
            <small class="text-muted">{{ translate('Manage all gift SKUs in one place.') }}</small>
            @if ($filtersApplied)
                <div><span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span></div>
            @endif
        </div>
        <div class="d-flex flex-wrap align-items-center">
            <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal" data-target="#giftFilterModal">
                {{ translate('Open Filters') }}
            </button>
            <a href="{{ route('gifts.index') }}" class="btn btn-danger mr-2 mb-2">{{ translate('Reset') }}</a>
            <a href="{{ route('gifts.create') }}" class="btn btn-primary mb-2">
                <i class="las la-plus"></i> {{ translate('Add Gift') }}
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ translate('Image') }}</th>
                        @include('backend.inc.sortable_th', ['column' => 'updated_at', 'label' => translate('Updated'), 'routeName' => 'gifts.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'name', 'label' => translate('Name'), 'routeName' => 'gifts.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'cost', 'label' => translate('Cost'), 'routeName' => 'gifts.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'description', 'label' => translate('Description'), 'routeName' => 'gifts.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        @include('backend.inc.sortable_th', ['column' => 'stock', 'label' => translate('Stock'), 'routeName' => 'gifts.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                        <th>{{ translate('Status') }}</th>
                        <th class="text-right">{{ translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gifts as $gift)
                        @php
                            $imageIds = collect($gift->photos ?? [])
                                ->prepend($gift->thumbnail_id)
                                ->filter(fn ($id) => $id !== null && $id !== '')
                                ->map(fn ($id) => (string) $id)
                                ->unique()
                                ->values();
                            $plainDescription = trim(preg_replace('/\s+/', ' ', strip_tags((string) $gift->description)));
                        @endphp
                        <tr>
                            <td>{{ $gifts->firstItem() + $loop->index }}</td>
                            <td>
                                @if ($imageIds->isNotEmpty())
                                    <div class="gift-list-thumbs">
                                        @foreach ($imageIds as $imageId)
                                            <button type="button"
                                                class="btn btn-link p-0 border-0 js-gift-enlarge"
                                                data-image="{{ uploaded_asset($imageId) }}"
                                                data-title="{{ $gift->name }}"
                                                title="{{ translate('Click to enlarge') }}">
                                                <img src="{{ uploaded_asset($imageId) }}" alt="{{ $gift->name }}" class="gift-list-thumb">
                                            </button>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ optional($gift->updated_at)->format('d M Y H:i') }}</td>
                            <td class="fw-600">{{ $gift->name }}</td>
                            <td>{{ single_price($gift->cost) }}</td>
                            <td>
                                @if ($plainDescription !== '')
                                    <div class="gift-description-clamp" title="{{ $plainDescription }}">{{ $plainDescription }}</div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $gift->stock }}</td>
                            <td>
                                <span class="badge badge-inline badge-{{ $gift->is_active ? 'success' : 'secondary' }}">
                                    {{ $gift->is_active ? translate('Active') : translate('Inactive') }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="d-flex justify-content-end">
                                    <a href="{{ route('gifts.edit', $gift->id) }}" class="btn btn-icon btn-circle btn-sm btn-soft-primary mr-1" title="{{ translate('Edit') }}">
                                        <i class="las la-pen"></i>
                                    </a>
                                    <form action="{{ route('gifts.toggle', $gift->id) }}" method="POST" class="d-inline-block mr-1">
                                        @csrf
                                        <button class="btn btn-icon btn-circle btn-sm btn-soft-warning" type="submit" title="{{ translate('Toggle status') }}">
                                            <i class="las la-adjust"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('gifts.destroy', $gift->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('{{ translate('Delete gift?') }}')">
                                        @csrf
                                        <button class="btn btn-icon btn-circle btn-sm btn-soft-danger" type="submit" title="{{ translate('Delete') }}">
                                            <i class="las la-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">{{ translate('No gifts found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="aiz-pagination mt-3">
            {{ $gifts->links() }}
        </div>
    </div>
</div>
@endsection

@section('modal')
    <div class="modal fade" id="giftFilterModal" tabindex="-1" role="dialog" aria-labelledby="giftFilterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form method="GET" action="{{ route('gifts.index') }}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="giftFilterModalLabel">{{ translate('Filter Gifts') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row gutters-5">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ translate('Name') }}</label>
                                <input type="text" name="name" class="form-control" value="{{ $filters['name'] ?? '' }}" placeholder="{{ translate('Search any word in name') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ translate('Description') }}</label>
                                <input type="text" name="description" class="form-control" value="{{ $filters['description'] ?? '' }}" placeholder="{{ translate('Search any word in description') }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ translate('Cost From') }}</label>
                                <input type="number" step="0.01" min="0" name="cost_from" class="form-control" value="{{ $filters['cost_from'] }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ translate('Cost To') }}</label>
                                <input type="number" step="0.01" min="0" name="cost_to" class="form-control" value="{{ $filters['cost_to'] }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ translate('Stock From') }}</label>
                                <input type="number" step="1" min="0" name="stock_from" class="form-control" value="{{ $filters['stock_from'] }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ translate('Stock To') }}</label>
                                <input type="number" step="1" min="0" name="stock_to" class="form-control" value="{{ $filters['stock_to'] }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ translate('Updated From') }}</label>
                                <input type="date" name="updated_from" class="form-control" value="{{ $filters['updated_from'] }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ translate('Updated To') }}</label>
                                <input type="date" name="updated_to" class="form-control" value="{{ $filters['updated_to'] }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ translate('Status') }}</label>
                                <select name="status" class="form-control aiz-selectpicker">
                                    <option value="">{{ translate('All Status') }}</option>
                                    <option value="1" @selected(($filters['status'] ?? '') === '1')>{{ translate('Active') }}</option>
                                    <option value="0" @selected(($filters['status'] ?? '') === '0')>{{ translate('Inactive') }}</option>
                                </select>
                            </div>
                        </div>
                        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('gifts.index') }}" class="btn btn-light">{{ translate('Reset') }}</a>
                        <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="giftImageModal" tabindex="-1" role="dialog" aria-labelledby="giftImageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="giftImageModalLabel">{{ translate('Image') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img src="" alt="" id="giftImageEnlarge" class="gift-enlarge-img">
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    $(document).on('click', '.js-gift-enlarge', function () {
        var src = $(this).data('image');
        var title = $(this).data('title') || '{{ translate('Image') }}';
        $('#giftImageModalLabel').text(title);
        $('#giftImageEnlarge').attr('src', src).attr('alt', title);
        $('#giftImageModal').modal('show');
    });

    $('#giftImageModal').on('hidden.bs.modal', function () {
        $('#giftImageEnlarge').attr('src', '').attr('alt', '');
    });
</script>
@endsection
