@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class=" align-items-center">
            <h1 class="h3">{{ translate('Product wise stock report') }}</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <!--card body-->
                <div class="card-body">
                    <form action="{{ route('stock_report.index') }}" method="GET">
                        <div class="form-group row d-flex justify-content-center align-items-center">
                            <div class="col-lg-9 mt-1 d-flex justify-content-center align-items-center">
                                <label class="col-form-label mr-1">{{ translate('Sort by Product') }} :</label>

                                <!-- Category Dropdown -->
                                <div class="mx-2">
                                    <select id="category_select" class="form-control aiz-selectpicker" name="category_id"
                                    data-live-search="true">
                                        <option value="">{{ translate('Choose Category') }}</option>
                                        @foreach (\App\Models\Category::all() as $key => $category)
                                            <option value="{{ $category->id }}">{{ $category->getTranslation('name') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Product Dropdown -->
                                <div class="mx-2">
                                    <select id="product_select" class="form-control aiz-selectpicker" name="product_id"
                                    data-live-search="true">
                                        <option value="">{{ translate('Choose Product') }}</option>
                                        @foreach (App\Models\Product::orderBy('created_at', 'desc')->get() as $key => $product)
                                            <option value="{{ $product->id }}">{{ $product->getTranslation('name') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-3 mt-1 d-flex justify-content-center align-items-center">
                                <div class="">
                                    <button class="btn btn-primary ml-1" type="submit">{{ translate('Filter') }}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <table class="table table-bordered aiz-table mb-0">
                        <thead>
                            <tr>
                                <th>{{ translate('Product Name') }}</th>
                                <th>{{ translate('Variant') }}</th>
                                <th>{{ translate('Stock') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $key => $product)
                                {{-- @php
                                $qty = 0;
                                foreach ($product->stocks as $key => $stock) {
                                    $qty += $stock->qty;
                                }
                            @endphp --}}
                                @foreach ($product->stocks as $key => $stock)
                                    <tr>
                                        <td>{{ $product->getTranslation('name') }}</td>
                                        <td>{{ $stock->variant }}</td>
                                        <td>{{ $stock->qty }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                    <div class="aiz-pagination mt-4">
                        {{ $products->appends(request()->input())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
<script>
    $(document).ready(function () {
        $('#category_select').on('change', function () {
            var categoryId = $(this).val();

            $.ajax({
                url: '{{ route("get.products.by.category") }}',
                type: 'GET',
                data: { category_id: categoryId },
                success: function (response) {
                    let productSelect = $('#product_select');
                    productSelect.empty(); // Clear old options

                    // Add default option
                    productSelect.append('<option value="">{{ translate("Choose Product") }}</option>');

                    // Append new options
                    $.each(response.products, function (key, product) {
                        productSelect.append('<option value="' + product.id + '">' + product.name + '</option>');
                    });

                    productSelect.selectpicker('refresh'); // Refresh Bootstrap Select (if using)
                },
                error: function () {
                    alert('Failed to load products.');
                }
            });
        });
    });
</script>
@endsection
