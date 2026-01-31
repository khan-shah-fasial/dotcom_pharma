<div class="searc_box_product">
    <form action="{{ route('search') }}" method="GET" class="stop-propagation">
        <div class="row">
            <div class="col-md-12 mb-lg-0 mb-md-2"><h3>Search Product</h3></div>
            
            <div class="col-md-3">
                <div class="form-group">
                    @php
                        $searchCategoryList = $searchCategories ?? $categories ?? collect();
                    @endphp
                    <select name="category" class="form-control form-select">
                        <option value="" selected>All Category</option>
                        @foreach ($searchCategoryList as $category)
                            <option value="{{ $category->id }}">{{ $category->getTranslation('name') }}</option>
                            @foreach ($category->childrenCategories as $childCategory)
                                @include('frontend.metro.partials.child_category', ['child_category' => $childCategory])
                            @endforeach
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <select name="brand" class="form-control form-select">
                        <option value="" selected>All Brand</option>
                        @foreach ($Brands as $Brand)
                            <option value="{{ $Brand->id }}">{{ $Brand->getTranslation('name') }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <input type="text" name="product" class="form-control" placeholder="Enter Product..." />
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <button type="submit" class="animate_button black1_buttons">
                        <i class="las la-search la-flip-horizontal la-1x" style="color:#fff;"></i> Search
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
