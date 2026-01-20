@extends('backend.layouts.app')

@section('content')

@php
    CoreComponentRepository::instantiateShopRepository();
    CoreComponentRepository::initializeCache();
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
    <div class="card-header d-block d-md-flex">
        <h5 class="mb-0 h6">{{ translate('Medical Groups') }}</h5>
        <form class="" id="sort_groups" action="" method="GET">
            <div class="box-inline pad-rgt pull-left">
                <div class="" style="min-width: 200px;">
                    <input type="text" class="form-control" id="search" name="search"@isset($sort_search) value="{{ $sort_search }}" @endisset placeholder="{{ translate('Type name & Enter') }}">
                </div>
            </div>
        </form>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th data-breakpoints="lg">#</th>
                    <th>{{translate('Name')}}</th>
                    <th data-breakpoints="lg">{{ translate('Parent Group') }}</th>
                    <th data-breakpoints="lg">{{ translate('Order Level') }}</th>
                    <th data-breakpoints="lg">{{ translate('Level') }}</th>
                    <th data-breakpoints="lg">{{translate('Banner')}}</th>
                    <th data-breakpoints="lg">{{translate('Icon')}}</th>
                    <th data-breakpoints="lg">{{translate('Cover Image')}}</th>
                    <th data-breakpoints="lg">{{translate('Featured')}}</th>
                    @if(get_setting('seller_commission_type') == 'category_based')
                        <th data-breakpoints="lg">{{translate('Commission')}}</th>
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
                                $parent = \App\Models\Group::where('id', $group->parent_id)->first();
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
                        @if(get_setting('seller_commission_type') == 'category_based')
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
