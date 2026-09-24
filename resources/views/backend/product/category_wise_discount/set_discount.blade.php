@extends('backend.layouts.app')

@section('content')

@php
    CoreComponentRepository::instantiateShopRepository();
    CoreComponentRepository::initializeCache();
    $filtersApplied = collect($filters ?? [])->contains(fn ($value) => $value !== null && $value !== '');
    $formatDiscountDate = function ($timestamp) {
        return $timestamp ? date('d-m-Y H:i', (int) $timestamp) : null;
    };
@endphp

<style>
    .category-discount-thumb {
        width: 72px;
        height: 72px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #e4e5eb;
        background: #f8f9fb;
        cursor: zoom-in;
    }
    .category-discount-enlarge-img {
        display: block;
        max-width: 100%;
        max-height: 75vh;
        margin: 0 auto;
        object-fit: contain;
    }
</style>

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{translate('Set Category Wise Product Discount')}}</h1>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <div class="mb-2">
            <h5 class="mb-0 h6">{{ translate('Categories') }}</h5>
            @if ($filtersApplied)
                <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
            @endif
        </div>
        <div class="d-flex flex-wrap align-items-center">
            <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal" data-target="#categoryDiscountFilterModal">
                {{ translate('Open Filters') }}
            </button>
            <a href="{{ route('categories_wise_product_discount') }}" class="btn btn-danger mb-2">{{ translate('Reset') }}</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{translate('Icon')}}</th>
                    @include('backend.inc.sortable_th', ['column' => 'name', 'label' => translate('Name'), 'routeName' => 'categories_wise_product_discount', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                    @include('backend.inc.sortable_th', ['column' => 'parent', 'label' => translate('Parent Category'), 'routeName' => 'categories_wise_product_discount', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                    @include('backend.inc.sortable_th', ['column' => 'discount', 'label' => translate('Discount'), 'routeName' => 'categories_wise_product_discount', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                    @include('backend.inc.sortable_th', ['column' => 'discount_start', 'label' => translate('Discount Date Range'), 'routeName' => 'categories_wise_product_discount', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
                    <th class="text-center">{{ translate('Seller Products?') }}</th>
                    <th class="text-right">{{ translate('Action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $key => $category)
                    @php
                        $parent = $category->parentCategory;
                        $currentStart = $formatDiscountDate($category->current_discount_start);
                        $currentEnd = $formatDiscountDate($category->current_discount_end);
                    @endphp
                    <tr>
                        <td>{{ ($key+1) + ($categories->currentPage() - 1)*$categories->perPage() }}</td>
                        <td>
                            @if($category->icon != null)
                                <button type="button"
                                    class="btn btn-link p-0 border-0 js-category-discount-enlarge"
                                    data-image="{{ uploaded_asset($category->icon) }}"
                                    data-title="{{ $category->getTranslation('name') }}"
                                    title="{{ translate('Click to enlarge') }}">
                                    <img src="{{ uploaded_asset($category->icon) }}" alt="{{ $category->getTranslation('name') }}" class="category-discount-thumb">
                                </button>
                            @else
                                —
                            @endif
                        </td>
                        <td class="fw-800">
                            {{ $category->getTranslation('name') }}
                            @if($category->digital == 1)
                                <img src="{{ static_asset('assets/img/digital_tag.png') }}" alt="{{translate('Digital')}}" class="ml-2 h-25px" title="{{ translate('Digital') }}">
                            @endif
                         </td>
                        <td class="fw-600">
                            @if ($parent != null)
                                {{ $parent->getTranslation('name') }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <div class="small text-muted mb-1">
                                {{ translate('Current') }}:
                                {{ $category->current_discount !== null ? $category->current_discount . '%' : '—' }}
                            </div>
                            <div class="input-group">
                                <input type="number" class="form-control" id="discount_{{ $category->id }}" step="0.01" value="0" min="0" placeholder="{{translate('Discount')}}"
                                    style="border-radius: 8px 0 0 8px;">
                                <div class="input-group-prepend">
                                    <span class="input-group-text border-left-0" id="inputGroupPrepend" style="border-radius: 0 8px 8px 0;">%</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small text-muted mb-1">
                                @if ($currentStart || $currentEnd)
                                    {{ $currentStart ?: '—' }} {{ translate('to') }} {{ $currentEnd ?: '—' }}
                                @else
                                    —
                                @endif
                            </div>
                            <input type="text" class="form-control aiz-date-range rounded-2" id="date_range_{{ $category->id }}" placeholder="{{translate('Select Date')}}" data-time-picker="true" data-format="DD-MM-Y HH:mm:ss" data-separator=" to " autocomplete="off">
                        </td>
                        <td class="text-center">
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input id="seller_product_discount_{{ $category->id }}" type="checkbox" >
                                <span class="slider round"></span>
                            </label>
                        </td>
                        <td class="text-right">
                            <div class="form-group mb-0 text-right">
                                <button type="button" onclick="trigger_alert({{ $category->id }})" class="btn btn-primary btn-sm rounded-2 w-120px">{{translate('Set')}}</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">{{ translate('No categories found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="aiz-pagination">
            {{ $categories->links() }}
        </div>
    </div>
</div>
@endsection

@section('modal')
    <!-- confirm Modal -->
    <div id="confirm-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered" style="max-width: 540px;">
            <div class="modal-content p-2rem">
                <div class="modal-body text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="72" height="64" viewBox="0 0 72 64">
                        <g id="Octicons" transform="translate(-0.14 -1.02)">
                          <g id="alert" transform="translate(0.14 1.02)">
                            <path id="Shape" d="M40.159,3.309a4.623,4.623,0,0,0-7.981,0L.759,58.153a4.54,4.54,0,0,0,0,4.578A4.718,4.718,0,0,0,4.75,65.02H67.587a4.476,4.476,0,0,0,3.945-2.289,4.773,4.773,0,0,0,.046-4.578Zm.6,52.555H31.582V46.708h9.173Zm0-13.734H31.582V23.818h9.173Z" transform="translate(-0.14 -1.02)" fill="#ffc700" fill-rule="evenodd"/>
                          </g>
                        </g>
                    </svg>
                    <p class="mt-3 mb-3 fs-16 fw-700">{{translate('Are you sure you want to set this discount?')}}</p>
                    <div>
                        <button type="button" class="btn btn-light rounded-2 mt-2 fs-13 fw-700 w-150px" data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <a href="javascript:void(0)" id="trigger_btn" data-value="" class="btn btn-warning rounded-2 mt-2 fs-13 fw-700 w-250px" onclick="setDiscount()">{{translate('Confirm')}}</a>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.modal -->

    <div class="modal fade" id="categoryDiscountFilterModal" tabindex="-1" role="dialog" aria-labelledby="categoryDiscountFilterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form method="GET" action="{{ route('categories_wise_product_discount') }}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="categoryDiscountFilterModalLabel">{{ translate('Filter Categories') }}</h5>
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
                                <label class="form-label">{{ translate('Parent Category') }}</label>
                                <select name="parent_id" class="form-control aiz-selectpicker" data-live-search="true">
                                    <option value="">{{ translate('All Parents') }}</option>
                                    <option value="0" @selected(($filters['parent_id'] ?? '') === '0')>{{ translate('No Parent') }}</option>
                                    @foreach ($parentCategories as $parentCategory)
                                        <option value="{{ $parentCategory->id }}" @selected((string) ($filters['parent_id'] ?? '') === (string) $parentCategory->id)>
                                            {{ $parentCategory->getTranslation('name') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ translate('Discount From') }}</label>
                                <input type="number" step="0.01" min="0" name="discount_from" class="form-control" value="{{ $filters['discount_from'] }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ translate('Discount To') }}</label>
                                <input type="number" step="0.01" min="0" name="discount_to" class="form-control" value="{{ $filters['discount_to'] }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ translate('Date From') }}</label>
                                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ translate('Date To') }}</label>
                                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                            </div>
                        </div>
                        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('categories_wise_product_discount') }}" class="btn btn-light">{{ translate('Reset') }}</a>
                        <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="categoryDiscountImageModal" tabindex="-1" role="dialog" aria-labelledby="categoryDiscountImageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryDiscountImageModalLabel">{{ translate('Icon') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img src="" alt="" id="categoryDiscountImageEnlarge" class="category-discount-enlarge-img">
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">

        $(document).ready(function() {
            setTimeout(() => {
                AIZ.plugins.dateRange();
            }, "2000");
        });

        $(document).on('click', '.js-category-discount-enlarge', function () {
            var src = $(this).attr('data-image');
            var title = $(this).attr('data-title') || '{{ translate('Icon') }}';
            $('#categoryDiscountImageModalLabel').text(title);
            $('#categoryDiscountImageEnlarge').attr('src', src).attr('alt', title);
            $('#categoryDiscountImageModal').modal('show');
        });

        $('#categoryDiscountImageModal').on('hidden.bs.modal', function () {
            $('#categoryDiscountImageEnlarge').attr('src', '').attr('alt', '');
        });

        function trigger_alert(CategoryId){
            $('#trigger_btn').attr('data-value', CategoryId);
            $('#confirm-modal').modal('show');
        }

        function setDiscount(){

            if('{{env('DEMO_MODE')}}' == 'On'){
                AIZ.plugins.notify('info', '{{ translate('Data can not change in demo mode.') }}');
                $('#confirm-modal').modal('hide');
                return;
            }

            $('#confirm-modal').modal('hide');
            var CategoryId = $('#trigger_btn').attr('data-value');
            var discount =  $("#discount_" + CategoryId).val();
            var dateRange =  $("#date_range_" + CategoryId).val();
            var sellerProductDiscount =  $("#seller_product_discount_" + CategoryId).prop('checked') ? 1 : 0;

            if(discount < 0) {
                AIZ.plugins.notify('danger', '{{ translate('Discount can not be less than 0') }}');
            }
            else{
                $.post('{{ route('set_product_discount') }}', {
                    _token:'{{ csrf_token() }}',
                    category_id:CategoryId,
                    discount:discount,
                    date_range:dateRange,
                    seller_product_discount:sellerProductDiscount
                }, function(data) {
                    if(data == 1){
                        AIZ.plugins.notify('success', '{{ translate('Category Wise Product Discount Set Successfully') }}');
                    }
                    location.reload();
                }).fail(function() {
                    AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}');
                });
            }
        }
    </script>
@endsection


