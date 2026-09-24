@extends('backend.layouts.app')

@section('content')

@php
    CoreComponentRepository::instantiateShopRepository();
    CoreComponentRepository::initializeCache();
    $sortBy = $sortBy ?? 'order_level';
    $sortDir = $sortDir ?? 'desc';
    $filters = $filters ?? [];
    $filtersApplied = collect($filters)->contains(function ($value) {
        return $value !== null && $value !== '';
    });
    $showCommission = get_setting('seller_commission_type') == 'category_based';
@endphp

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('All Medical Groups')}}</h1>
        </div>
        @can('add_product_category')
            <div class="col-md-6 text-md-right">
                <a href="{{ route('groups.create') }}" class="btn btn-circle btn-info">
                    <span>{{translate('Add New Group')}}</span>
                </a>
            </div>
        @endcan
    </div>
</div>
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <div class="mb-2">
            <h5 class="mb-0 h6">{{ translate('Medical Groups') }}</h5>
            @if ($filtersApplied)
                <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
            @endif
        </div>
        <div class="d-flex flex-wrap align-items-center">
            <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal" data-target="#medicalGroupFilterModal">
                {{ translate('Open Filters') }}
            </button>
            <a href="{{ route('groups.index') }}" class="btn btn-danger mb-2">{{ translate('Reset') }}</a>
        </div>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th data-breakpoints="lg">#</th>
                    @include('backend.inc.sortable_th', ['column' => 'name', 'label' => translate('Name'), 'routeName' => 'groups.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                    @include('backend.inc.sortable_th', ['column' => 'parent', 'label' => translate('Parent Group'), 'routeName' => 'groups.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'order_level', 'label' => translate('Order Level'), 'routeName' => 'groups.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'level', 'label' => translate('Level'), 'routeName' => 'groups.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'banner', 'label' => translate('Banner'), 'routeName' => 'groups.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'icon', 'label' => translate('Icon'), 'routeName' => 'groups.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'cover_image', 'label' => translate('Cover Image'), 'routeName' => 'groups.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @include('backend.inc.sortable_th', ['column' => 'featured', 'label' => translate('Featured'), 'routeName' => 'groups.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @if($showCommission)
                        @include('backend.inc.sortable_th', ['column' => 'commision_rate', 'label' => translate('Commission'), 'routeName' => 'groups.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir, 'breakpoints' => 'lg'])
                    @endif
                    <th width="10%" class="text-right">{{translate('Options')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groups as $key => $group)
                    <tr>
                        <td>{{ ($key+1) + ($groups->currentPage() - 1)*$groups->perPage() }}</td>
                        <td class="d-flex align-items-center">
                            {{ $group->getTranslation('name') }}
                            @if($group->digital == 1)
                                <img src="{{ static_asset('assets/img/digital_tag.png') }}" alt="{{translate('Digital')}}" class="ml-2 h-25px" style="cursor: pointer;" title="Digital">
                            @endif
                         </td>
                        <td>
                            @php
                                $parent = $group->parentGroup;
                            @endphp
                            @if ($parent != null)
                                {{ $parent->getTranslation('name') }}
                            @else
                                --
                            @endif
                        </td>
                        <td>{{ $group->order_level }}</td>
                        <td>{{ $group->level }}</td>
                        <td>
                            @if($group->banner != null)
                                <img src="{{ uploaded_asset($group->banner) }}" alt="{{translate('Banner')}}" class="h-50px">
                            @else
                                --
                            @endif
                        </td>
                        <td>
                            @if($group->icon != null)
                                <span class="avatar avatar-square avatar-xs">
                                    <img src="{{ uploaded_asset($group->icon) }}" alt="{{translate('icon')}}">
                                </span>
                            @else
                                --
                            @endif
                        </td>
                        <td>
                            @if($group->cover_image != null)
                                <img src="{{ uploaded_asset($group->cover_image) }}" alt="{{translate('Cover Image')}}" class="h-50px">
                            @else
                                --
                            @endif
                        </td>
                        <td>
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input type="checkbox" onchange="update_featured(this)" value="{{ $group->id }}" <?php if($group->featured == 1) echo "checked";?>>
                                <span></span>
                            </label>
                        </td>
                        @if($showCommission)
                            <td>{{ $group->commision_rate }} %</td>
                        @endif
                        <td class="text-right">
                            @can('edit_product_category')
                                <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{route('groups.edit', ['id'=>$group->id, 'lang'=>env('DEFAULT_LANGUAGE')] )}}" title="{{ translate('Edit') }}">
                                    <i class="las la-edit"></i>
                                </a>
                            @endcan
                            @can('delete_product_category')
                                <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{route('groups.destroy', $group->id)}}" title="{{ translate('Delete') }}">
                                    <i class="las la-trash"></i>
                                </a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $groups->appends(request()->input())->links() }}
        </div>
    </div>
</div>
@endsection


@section('modal')
    @include('modals.delete_modal')
    <form action="{{ route('groups.index') }}" method="GET">
        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
        <div class="modal fade" id="medicalGroupFilterModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Filter Medical Groups') }}</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row gutters-5">
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Name') }}</label>
                                <input type="text" class="form-control" name="name" value="{{ $filters['name'] ?? '' }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ translate('Parent Group') }}</label>
                                <input type="text" class="form-control" name="parent" value="{{ $filters['parent'] ?? '' }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Order Level From') }}</label>
                                <input type="number" class="form-control" name="order_level_from" value="{{ $filters['order_level_from'] ?? '' }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Order Level To') }}</label>
                                <input type="number" class="form-control" name="order_level_to" value="{{ $filters['order_level_to'] ?? '' }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Level From') }}</label>
                                <input type="number" class="form-control" name="level_from" value="{{ $filters['level_from'] ?? '' }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ translate('Level To') }}</label>
                                <input type="number" class="form-control" name="level_to" value="{{ $filters['level_to'] ?? '' }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>{{ translate('Banner') }}</label>
                                <select name="banner" class="form-control">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="1" @selected(($filters['banner'] ?? '') === '1')>{{ translate('Has image') }}</option>
                                    <option value="0" @selected(($filters['banner'] ?? '') === '0')>{{ translate('No image') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>{{ translate('Icon') }}</label>
                                <select name="icon" class="form-control">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="1" @selected(($filters['icon'] ?? '') === '1')>{{ translate('Has image') }}</option>
                                    <option value="0" @selected(($filters['icon'] ?? '') === '0')>{{ translate('No image') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>{{ translate('Cover Image') }}</label>
                                <select name="cover_image" class="form-control">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="1" @selected(($filters['cover_image'] ?? '') === '1')>{{ translate('Has image') }}</option>
                                    <option value="0" @selected(($filters['cover_image'] ?? '') === '0')>{{ translate('No image') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>{{ translate('Featured') }}</label>
                                <select name="featured" class="form-control">
                                    <option value="">{{ translate('All') }}</option>
                                    <option value="1" @selected(($filters['featured'] ?? '') === '1')>{{ translate('Yes') }}</option>
                                    <option value="0" @selected(($filters['featured'] ?? '') === '0')>{{ translate('No') }}</option>
                                </select>
                            </div>
                            @if($showCommission)
                                <div class="col-md-4 mb-3">
                                    <label>{{ translate('Commission From') }}</label>
                                    <input type="number" step="0.01" class="form-control" name="commission_from" value="{{ $filters['commission_from'] ?? '' }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>{{ translate('Commission To') }}</label>
                                    <input type="number" step="0.01" class="form-control" name="commission_to" value="{{ $filters['commission_to'] ?? '' }}">
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('groups.index') }}" class="btn btn-danger">{{ translate('Reset') }}</a>
                        <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection


@section('script')
    <script type="text/javascript">
        function update_featured(el){
            if('{{env('DEMO_MODE')}}' == 'On'){
                AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
                return;
            }

            var status = el.checked ? 1 : 0;
            $.post('{{ route('groups.featured') }}', {_token:'{{ csrf_token() }}', id:el.value, status:status}, function(data){
                if(data == 1){
                    AIZ.plugins.notify('success', '{{ translate('Featured groups updated successfully') }}');
                }
                else{
                    AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                }
            });
        }
    </script>
@endsection
