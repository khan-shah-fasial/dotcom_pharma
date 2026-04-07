@extends('backend.layouts.app')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h6 class="mb-0">{{ translate('Gifts') }}</h6>
            <small class="text-muted">{{ translate('Manage all gift SKUs in one place.') }}</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('gifts.index') }}" method="GET" class="form-inline">
                <div class="form-group mr-2 mb-0">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ translate('Search by name') }}" value="{{ $search ?? '' }}">
                </div>
                <div class="form-group mr-2 mb-0">
                    <select name="status" class="form-control form-control-sm aiz-selectpicker" data-style="btn-light">
                        <option value="">{{ translate('All Status') }}</option>
                        <option value="1" @selected(($status ?? '') === '1')>{{ translate('Active') }}</option>
                        <option value="0" @selected(($status ?? '') === '0')>{{ translate('Inactive') }}</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">{{ translate('Filter') }}</button>
            </form>
            <a href="{{ route('gifts.create') }}" class="btn btn-primary btn-sm">
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
                        <th data-breakpoints="lg">{{ translate('Updated') }}</th>
                        <th>{{ translate('Name') }}</th>
                        <th>{{ translate('Cost') }}</th>
                        <th>{{ translate('Stock') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th data-breakpoints="md">{{ translate('Images') }}</th>
                        <th class="text-right">{{ translate('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gifts as $gift)
                        <tr>
                            <td>{{ $gift->id }}</td>
                            <td>{{ optional($gift->updated_at)->format('d M Y H:i') }}</td>
                            <td class="fw-600">{{ $gift->name }}</td>
                            <td>{{ single_price($gift->cost) }}</td>
                            <td>{{ $gift->stock }}</td>
                            <td>
                                <span class="badge badge-inline badge-{{ $gift->is_active ? 'success' : 'secondary' }}">
                                    {{ $gift->is_active ? translate('Active') : translate('Inactive') }}
                                </span>
                            </td>
                            <td>
                                @if(!empty($gift->photos))
                                    <small>{{ count($gift->photos) }} {{ translate('image(s)') }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
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
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="aiz-pagination mt-3">
            {{ $gifts->links() }}
        </div>
    </div>
</div>
@include('uploader.aiz-uploader')
@endsection
