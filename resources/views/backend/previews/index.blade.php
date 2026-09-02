@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="h3">{{ translate('UI previews') }}</h1>
                <p class="text-muted mb-0">{{ translate('Frontend-only sandboxes. Live pages are not changed.') }}</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Preview') }}</th>
                            <th>{{ translate('Description') }}</th>
                            <th class="text-right">{{ translate('Open') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($previews as $slug => $preview)
                            <tr>
                                <td>{{ translate($preview['title']) }}</td>
                                <td>{{ translate($preview['description']) }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.previews.show', $slug) }}" class="btn btn-sm btn-primary">
                                        {{ translate('Open') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
