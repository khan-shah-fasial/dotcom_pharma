@extends('backend.layouts.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
	<div class=" align-items-center">
       <h1 class="h3">{{translate('Product wise stock report')}}</h1>
	</div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <!--card body-->
            <div class="card-body">
                <form action="{{ route('stock_report.index') }}" method="GET">
                    <div class="form-group row d-flex justify-content-center align-items-center">
                        <div class="col-lg-6 mt-1 d-flex justify-content-center align-items-center">
                            <label class="col-form-label mr-1">{{translate('Sort by Product')}} :</label>
                            <div class="">
                                <select id="demo-ease" class="from-control aiz-selectpicker" name="product_id">
                                    <option value="">{{ translate('Choose Product') }}</option>
                                    @foreach (App\Models\Product::orderBy('created_at', 'desc')->get() as $key => $product)
                                        <option value="{{ $product->id }}" @if($sort_by == $product->id) selected @endif>{{ $product->getTranslation('name') }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-6 mt-1 d-flex justify-content-center align-items-center">
                            <div class="">
                                <select id="demo-ease" class="from-control aiz-selectpicker" name="category_id">
                                    <option value="">{{ translate('Choose Category') }}</option>
                                    @foreach (\App\Models\Category::all() as $key => $category)
                                        <option value="{{ $category->id }}" @if($sort_by == $category->id) selected @endif>{{ $category->getTranslation('name') }}</option>
                                    @endforeach
                                </select>
                            </div>
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
