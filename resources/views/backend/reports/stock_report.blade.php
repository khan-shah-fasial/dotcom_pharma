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
                    <form action="{{ route('stock_report.index') }}" method="GET" class="mb-3">
                        <div class="border rounded p-3">
                            <div class="row">
                                <div class="col-12 mb-2">
                                    <h6 class="mb-0">{{ translate('Filter Stock Report') }}</h6>
                                </div>

                                <div class="col-12 col-sm-6 col-lg-3 mb-3">
                                    <label for="category_select" class="mb-1">{{ translate('Category') }}</label>
                                    <select id="category_select" class="form-control aiz-selectpicker" name="category_id" data-live-search="true">
                                        <option value="">{{ translate('All Categories') }}</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" @selected($categoryId == $category->id)>
                                                {{ $category->getTranslation('name') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-sm-6 col-lg-3 mb-3">
                                    <label for="product_select" class="mb-1">{{ translate('Product') }}</label>
                                    <select id="product_select" class="form-control aiz-selectpicker" name="product_id" data-live-search="true">
                                        <option value="">{{ translate('All Products') }}</option>
                                        @foreach ($productsForFilter as $product)
                                            <option value="{{ $product->id }}" @selected($productId == $product->id)>
                                                {{ $product->getTranslation('name') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-sm-6 col-lg-3 mb-3">
                                    <label for="variant_select" class="mb-1">{{ translate('Variant') }}</label>
                                    <select id="variant_select" class="form-control aiz-selectpicker" name="variant_id" data-live-search="true">
                                        <option value="">{{ translate('All Variants') }}</option>
                                        @foreach ($variants as $variant)
                                            <option value="{{ $variant->id }}" @selected($variantId == $variant->id)>
                                                {{ $variant->variant }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-sm-6 col-lg-3 mb-3">
                                    <label for="batch_select" class="mb-1">{{ translate('Batch') }}</label>
                                    <select id="batch_select" class="form-control aiz-selectpicker" name="batch_id" data-live-search="true">
                                        <option value="">{{ translate('All Batches') }}</option>
                                        @foreach ($batches as $batch)
                                            @continue(function_exists('is_batch_expired') && is_batch_expired($batch))
                                            <option value="{{ $batch->id }}" @selected($batchId == $batch->id)>
                                                {{ $batch->batch }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 d-flex flex-column flex-sm-row justify-content-end">
                                    <a href="{{ route('stock_report.index') }}" class="btn btn-light border mb-2 mb-sm-0 mr-sm-2">
                                        {{ translate('Reset') }}
                                    </a>
                                    <button class="btn btn-primary" type="submit">
                                        {{ translate('Apply Filter') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <table class="table table-bordered aiz-table mb-0">
                        <thead>
                            <tr>
                                <th>{{ translate('Product Name') }}</th>
                                <th>{{ translate('Variant') }}</th>
                                <th>{{ translate('Batch') }}</th>
                                <th>{{ translate('Stock') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $key => $product)
                                @foreach ($product->stocks as $key => $stock)
                                    @foreach ($stock->batches as $batch)
                                        <tr>
                                            <td>{{ $product->getTranslation('name') }}</td>
                                            <td>{{ $stock->variant }}</td>
                                            <td>{{ $batch->batch }}</td>
                                            <td>{{ $batch->qty }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            @endforeach
                            @if ($products->isEmpty())
                                <tr>
                                    <td colspan="4" class="text-center">{{ translate('No stock data found') }}</td>
                                </tr>
                            @endif
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
        const productSelect = $('#product_select');
        const variantSelect = $('#variant_select');
        const batchSelect = $('#batch_select');

        function resetVariantAndBatch() {
            variantSelect.empty().append('<option value="">{{ translate("All Variants") }}</option>');
            batchSelect.empty().append('<option value="">{{ translate("All Batches") }}</option>');
            variantSelect.selectpicker('refresh');
            batchSelect.selectpicker('refresh');
        }

        function loadVariantAndBatchOptions(productId, selectedVariantId = '', selectedBatchId = '') {
            if (!productId) {
                resetVariantAndBatch();
                return;
            }

            $.ajax({
                url: '{{ route("stock_report.filter_options") }}',
                type: 'GET',
                data: {
                    product_id: productId,
                    variant_id: selectedVariantId
                },
                success: function (response) {
                    variantSelect.empty().append('<option value="">{{ translate("All Variants") }}</option>');
                    batchSelect.empty().append('<option value="">{{ translate("All Batches") }}</option>');

                    $.each(response.variants, function (key, variant) {
                        const isSelected = String(variant.id) === String(selectedVariantId) ? 'selected' : '';
                        variantSelect.append('<option value="' + variant.id + '" ' + isSelected + '>' + variant.name + '</option>');
                    });

                    $.each(response.batches, function (key, batch) {
                        const isSelected = String(batch.id) === String(selectedBatchId) ? 'selected' : '';
                        batchSelect.append('<option value="' + batch.id + '" ' + isSelected + '>' + batch.name + '</option>');
                    });

                    variantSelect.selectpicker('refresh');
                    batchSelect.selectpicker('refresh');
                },
                error: function () {
                    resetVariantAndBatch();
                }
            });
        }

        $('#category_select').on('change', function () {
            var categoryId = $(this).val();

            $.ajax({
                url: '{{ route("get.products.by.category") }}',
                type: 'GET',
                data: { category_id: categoryId },
                success: function (response) {
                    productSelect.empty(); // Clear old options

                    // Add default option
                    productSelect.append('<option value="">{{ translate("All Products") }}</option>');

                    // Append new options
                    $.each(response.products, function (key, product) {
                        productSelect.append('<option value="' + product.id + '">' + product.name + '</option>');
                    });

                    productSelect.selectpicker('refresh'); // Refresh Bootstrap Select (if using)
                    resetVariantAndBatch();
                },
                error: function () {
                    alert('Failed to load products.');
                }
            });
        });

        $('#product_select').on('change', function () {
            const productId = $(this).val();
            loadVariantAndBatchOptions(productId);
        });

        $('#variant_select').on('change', function () {
            const productId = productSelect.val();
            const selectedVariantId = $(this).val();
            const selectedBatchId = batchSelect.val();
            loadVariantAndBatchOptions(productId, selectedVariantId, selectedBatchId);
        });

        @if($productId)
            loadVariantAndBatchOptions('{{ $productId }}', '{{ $variantId }}', '{{ $batchId }}');
        @endif
    });
</script>
@endsection
