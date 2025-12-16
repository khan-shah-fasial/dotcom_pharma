@extends('backend.layouts.app')

@section('content')
<div class="page-content">
    <div class="aiz-titlebar text-left mt-2 pb-2 px-3 px-md-2rem border-bottom border-gray">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="h3">{{ translate('Edit Product') }}</h1>
            </div>
            <div class="col-auto ml-auto text-right">
                <a class="btn btn-primary" href="{{ route('product', $product->slug) }}" target="_blank">
                    {{ translate('View Product') }}
                </a>
            </div>
        </div>
    </div>

    <div class="d-sm-flex">
        <!-- page side nav -->
        <div class="page-side-nav c-scrollbar-light px-3 py-2">
            <ul class="nav nav-tabs flex-sm-column border-0" role="tablist" aria-orientation="vertical">
                <!-- General -->
                <li class="nav-item">
                    <a class="nav-link" id="general-tab" href="#general"
                        data-toggle="tab" data-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                        {{ translate('General') }}
                    </a>
                </li>
                <!-- Files & Media -->
                <li class="nav-item">
                    <a class="nav-link" id="files-and-media-tab" href="#files_and_media"
                        data-toggle="tab" data-target="#files_and_media" type="button" role="tab" aria-controls="files_and_media" aria-selected="false">
                        {{ translate("Files & Media") }}
                    </a>
                </li>
                <!-- Price & Stock -->
                <li class="nav-item">
                    <a class="nav-link" id="price-and-stocks-tab" href="#price_and_stocks"
                        data-toggle="tab" data-target="#price_and_stocks" type="button" role="tab" aria-controls="price_and_stocks" aria-selected="false">
                        {{ translate('Price & Stock') }}
                    </a>
                </li>
                <!-- SEO -->
                <li class="nav-item">
                    <a class="nav-link" id="seo-tab" href="#seo"
                        data-toggle="tab" data-target="#seo" type="button" role="tab" aria-controls="seo" aria-selected="false">
                        {{ translate('SEO') }}
                    </a>
                </li>
                <!-- Shipping -->
                <li class="nav-item">
                    <a class="nav-link" id="shipping-tab" href="#shipping"
                        data-toggle="tab" data-target="#shipping" type="button" role="tab" aria-controls="shipping" aria-selected="false">
                        {{ translate('Shipping') }}
                    </a>
                </li>

                <!-- Warranty -->
                <li class="nav-item">
                    <a class="nav-link" id="warranty-tab" href="#warranty"
                        data-toggle="tab" data-target="#warranty" type="button" role="tab" aria-controls="warranty" aria-selected="false">
                        {{ translate('Warranty') }}
                    </a>
                </li>

                <!-- Frequently Bought Product -->
                <li class="nav-item">
                    <a class="nav-link" id="frequenty-bought-product-tab" href="#frequenty-bought-product"
                        data-toggle="tab" data-target="#frequenty-bought-product" type="button" role="tab" aria-controls="frequenty-bought-product" aria-selected="false">
                        {{ translate('Frequently Bought') }}
                    </a>
                </li>
            </ul>
        </div>

        <!-- tab content -->
        <div class="flex-grow-1 p-sm-3 p-lg-2rem mb-2rem mb-md-0">
            <!-- Error Meassages -->
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{route('products.update', $product->id)}}" method="POST" enctype="multipart/form-data" enctype="multipart/form-data" id="choice_form">
                @csrf
                <input name="_method" type="hidden" value="POST">
                <input type="hidden" name="id" value="{{ $product->id }}">
                <input type="hidden" name="lang" value="{{ $lang }}">
                <input type="hidden" name="tab" id="tab">

                <ul class="nav nav-tabs nav-fill language-bar">
                    @foreach (get_all_active_language() as $key => $language)
                    <li class="nav-item">
                        <a class="nav-link text-reset @if ($language->code == $lang) active @endif py-3" href="{{ route('products.admin.edit', ['id'=>$product->id, 'lang'=> $language->code] ) }}">
                            <img src="{{ static_asset('assets/img/flags/'.$language->code.'.png') }}" height="11" class="mr-1">
                            <span>{{$language->name}}</span>
                        </a>
                    </li>
                    @endforeach
                </ul>

                <div class="tab-content">
                    <!-- General -->
                    <div class="tab-pane fade" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <div class="bg-white p-3 p-sm-2rem">
                            <!-- Product Information -->
                            <h5 class="mb-3 pb-3 fs-17 fw-700" style="border-bottom: 1px dashed #e4e5eb;">{{translate('Product Information')}}</h5>
                            <div class="w-100">
                                <div class="row">

                                <div class="col-xxl-12 col-xl-12">
                                        <div class="card @if($errors->has('category_ids') || $errors->has('category_id')) border border-danger @endif">
                                            <div class="card-header">
                                                <h5 class="mb-0 h6">{{ translate('Product Category') }}</h5>
                                                <h6 class="float-right fs-13 mb-0">
                                                    {{ translate('Select Main') }}
                                                    <span class="position-relative main-category-info-icon">
                                                        <i class="las la-question-circle fs-18 text-info"></i>
                                                        <span class="main-category-info bg-soft-info p-2 position-absolute d-none border">{{ translate('This will be used for commission based calculations and homepage category wise product Show.') }}</span>
                                                    </span>
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="h-300px overflow-auto c-scrollbar-light">
                                                    @php
                                                        $old_categories = $product->categories()->pluck('category_id')->toArray();
                                                    @endphp
                                                    <ul class="hummingbird-treeview-converter list-unstyled" data-checkbox-name="category_ids[]" data-radio-name="category_id">
                                                        @foreach ($categories as $category)
                                                        <li id="{{ $category->id }}">{{ $category->getTranslation('name') }}</li>
                                                            @foreach ($category->childrenCategories as $childCategory)
                                                                @include('backend.product.products.child_category', ['child_category' => $childCategory])
                                                            @endforeach
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="col-xxl-12 col-xl-12">
                                        <!-- Product Name -->
                                        <div class="form-group row">
                                            <label class="col-xxl-3 col-from-label fs-13">{{translate('Product Name')}} <span class="text-danger">*</span></label>
                                            <div class="col-xxl-9">
                                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" placeholder="{{translate('Product Name')}}" value="{{ $product->getTranslation('name', $lang) }}" required>
                                            </div>
                                        </div>

                                        <!-- GEM portal link -->
                                        <div class="form-group row">
                                            <label class="col-xxl-3 col-from-label fs-13">{{ translate('GEM portal link') }}</label>
                                            <div class="col-xxl-9">
                                                <input type="text" class="form-control @error('gem_portal_link') is-invalid @enderror" name="gem_portal_link" placeholder="{{ translate('https://...') }}" value="{{ old('gem_portal_link', $product->gem_portal_link) }}">
                                            </div>
                                        </div>

                                        <!-- Drug Name -->
                                        <div class="form-group row">
                                            <label class="col-xxl-3 col-from-label fs-13">{{translate('Drug Name')}} </label>
                                            <div class="col-xxl-9">
                                                <input type="text" class="form-control @error('drug_name') is-invalid @enderror" name="drug_name" placeholder="{{ translate('Drug Name') }}" value="{{ $product->drug_name }}">
                                            </div>
                                        </div>

                                        <!-- Product Form -->
                                        <div class="form-group row">
                                            <label class="col-xxl-3 col-from-label fs-13">{{translate('Product Form')}} </label>
                                            <div class="col-xxl-9">
                                                <input type="text" class="form-control @error('product_form') is-invalid @enderror" name="product_form" placeholder="{{ translate('Product Form') }}" value="{{ $product->product_form }}">
                                            </div>
                                        </div>

                                        <!-- Product Pharma Categories -->
                                        <div class="form-group row">
                                            <label class="col-xxl-3 col-from-label fs-13">{{translate('Pharma Categories')}} </label>
                                            <div class="col-xxl-9">
                                                <input type="text" class="form-control @error('pharma_categories') is-invalid @enderror" name="pharma_categories" placeholder="{{ translate('Pharma Categories') }}" value="{{ $product->pharma_categories }}">
                                            </div>
                                        </div>


                                        <!-- Product short description -->
                                        {{-- <div class="form-group row">
                                            <label class="col-xxl-3 col-from-label fs-13">{{translate('Product Description')}} <span class="text-danger">*</span></label>
                                            <div class="col-xxl-9">
                                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="short_description" placeholder="{{translate('Product Description')}}" value="{{ $product->short_description }}" required>
                                            </div>
                                        </div> --}}

                                        <!-- Brand -->
                                        <div class="form-group row" id="brand">
                                            <label class="col-xxl-3 col-from-label fs-13">{{translate('Brand')}}</label>
                                            <div class="col-xxl-9">
                                                <select class="form-control aiz-selectpicker" name="brand_id" id="brand_id" data-live-search="true">
                                                    <option value="">{{ translate('Select Brand') }}</option>
                                                    @foreach (\App\Models\Brand::all() as $brand)
                                                    <option value="{{ $brand->id }}" @if($product->brand_id == $brand->id) selected @endif>{{ $brand->getTranslation('name') }}</option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">{{translate("You can choose a brand if you'd like to display your product by brand.")}}</small>
                                            </div>
                                        </div>
                                        <!-- Unit -->
                                        {{-- <div class="form-group row">
                                            <label class="col-xxl-3 col-from-label fs-13">{{translate('Unit')}} <span class="text-danger">*</span></label>
                                            <div class="col-xxl-9">
                                                <input type="text" class="form-control @error('unit') is-invalid @enderror" name="unit" placeholder="{{ translate('Unit (e.g. KG, Pc etc)') }}" value="{{$product->getTranslation('unit', $lang)}}" required>
                                            </div>
                                        </div> --}}
                                        <div class="form-group row">
                                            <label class="col-xxl-3 col-form-label fs-13">{{ translate('Unit') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <div class="col-xxl-9">
                                                <select class="form-control aiz-selectpicker @error('unit') is-invalid @enderror"
                                                        name="unit"
                                                        data-live-search="true"
                                                        title="{{ translate('Select a unit') }}"
                                                        required>

                                                    <!-- Weight Units -->
                                                    <optgroup label="Weight Units">
                                                        <option value="mcg" {{ old('unit', $product->getTranslation('unit', $lang)) == 'mcg' ? 'selected' : '' }}>Microgram (mcg)</option>
                                                        <option value="mg" {{ old('unit', $product->getTranslation('unit', $lang)) == 'mg' ? 'selected' : '' }}>Milligram (mg)</option>
                                                        <option value="g" {{ old('unit', $product->getTranslation('unit', $lang)) == 'g' ? 'selected' : '' }}>Gram (g)</option>
                                                        <option value="kg" {{ old('unit', $product->getTranslation('unit', $lang)) == 'kg' ? 'selected' : '' }}>Kilogram (kg)</option>
                                                        <option value="ng" {{ old('unit', $product->getTranslation('unit', $lang)) == 'ng' ? 'selected' : '' }}>Nanogram (ng)</option>
                                                    </optgroup>

                                                    <!-- Volume Units -->
                                                    <optgroup label="Volume Units">
                                                        <option value="ml" {{ old('unit', $product->getTranslation('unit', $lang)) == 'ml' ? 'selected' : '' }}>Milliliter (mL)</option>
                                                        <option value="ltr" {{ old('unit', $product->getTranslation('unit', $lang)) == 'ltr' ? 'selected' : '' }}>Liter (L)</option>
                                                        <option value="cc" {{ old('unit', $product->getTranslation('unit', $lang)) == 'cc' ? 'selected' : '' }}>Cubic Centimeter (cc)</option>
                                                        <option value="tsp" {{ old('unit', $product->getTranslation('unit', $lang)) == 'tsp' ? 'selected' : '' }}>Teaspoon (tsp ≈ 5 mL)</option>
                                                        <option value="tbsp" {{ old('unit', $product->getTranslation('unit', $lang)) == 'tbsp' ? 'selected' : '' }}>Tablespoon (tbsp ≈ 15 mL)</option>
                                                        <option value="fl_oz" {{ old('unit', $product->getTranslation('unit', $lang)) == 'fl_oz' ? 'selected' : '' }}>Fluid Ounce (fl oz ≈ 30 mL)</option>
                                                    </optgroup>

                                                    <!-- Count Units -->
                                                    <optgroup label="Count Units">
                                                        <option value="pc" {{ old('unit', $product->getTranslation('unit', $lang)) == 'pc' ? 'selected' : '' }}>Piece (Pc)</option>
                                                        <option value="tab" {{ old('unit', $product->getTranslation('unit', $lang)) == 'tab' ? 'selected' : '' }}>Tablet (Tab)</option>
                                                        <option value="cap" {{ old('unit', $product->getTranslation('unit', $lang)) == 'cap' ? 'selected' : '' }}>Capsule (Cap)</option>
                                                        <option value="amp" {{ old('unit', $product->getTranslation('unit', $lang)) == 'amp' ? 'selected' : '' }}>Ampoule (Amp)</option>
                                                        <option value="vial" {{ old('unit', $product->getTranslation('unit', $lang)) == 'vial' ? 'selected' : '' }}>Vial</option>
                                                        <option value="syringe" {{ old('unit', $product->getTranslation('unit', $lang)) == 'syringe' ? 'selected' : '' }}>Syringe</option>
                                                        <option value="bottle" {{ old('unit', $product->getTranslation('unit', $lang)) == 'bottle' ? 'selected' : '' }}>Bottle</option>
                                                        <option value="pack" {{ old('unit', $product->getTranslation('unit', $lang)) == 'pack' ? 'selected' : '' }}>Pack</option>
                                                    </optgroup>

                                                    <!-- Special Units -->
                                                    <optgroup label="Special Units">
                                                        <option value="iu" {{ old('unit', $product->getTranslation('unit', $lang)) == 'iu' ? 'selected' : '' }}>International Unit (IU)</option>
                                                        <option value="meq" {{ old('unit', $product->getTranslation('unit', $lang)) == 'meq' ? 'selected' : '' }}>Milliequivalent (mEq)</option>
                                                        <option value="units" {{ old('unit', $product->getTranslation('unit', $lang)) == 'units' ? 'selected' : '' }}>Units</option>
                                                    </optgroup>
                                                </select>

                                                @error('unit')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <!-- Tags -->
                                        <div class="form-group row">
                                            <label class="col-xxl-3 col-from-label fs-13">{{translate('Tags')}}</label>
                                            <div class="col-xxl-9">
                                                <input type="text" class="form-control aiz-tag-input" name="tags[]" id="tags" value="{{ $product->tags }}" placeholder="{{ translate('Type to add a tag') }}" data-role="tagsinput">
                                                <small class="text-muted">{{translate('This is used for search. Input those words by which cutomer can find this product.')}}</small>
                                            </div>
                                        </div>

                                        @if (addon_is_activated('pos_system'))
                                        <!-- Barcode -->
                                        <div class="form-group row">
                                            <label class="col-xxl-3 col-from-label fs-13">{{translate('Barcode')}}</label>
                                            <div class="col-xxl-9">
                                                <input type="text" class="form-control" name="barcode" placeholder="{{ translate('Barcode') }}" value="{{ $product->barcode }}">
                                            </div>
                                        </div>
                                        @endif

                                        @if (addon_is_activated('refund_request'))
                                        <!-- refund_request -->
                                        <div class="form-group row mt-4 mb-4">
                                            <label class="col-xxl-3 col-from-label fs-13">{{translate('Refundable')}}</label>
                                            <div class="col-xxl-9">
                                                <label class="aiz-switch aiz-switch-success mb-0">
                                                    <input type="checkbox" name="refundable" @if ($product->refundable == 1) checked @endif value="1">
                                                    <span></span>
                                                </label>
                                            </div>
                                        </div>
                                        @endif


                                        <!------- Prescription required --------->
                                        <div class="form-group row">
                                            <label class="col-md-3 col-from-label">{{translate('Prescription Required')}}</label>
                                            <div class="col-md-9">
                                                <label class="aiz-switch aiz-switch-success mb-0 d-block">
                                                    <input type="checkbox" name="prescription_req" @if ($product->prescription_req == 1) checked @endif value="1">
                                                    <span></span>
                                                </label>
                                                <small class="text-muted">{{ translate('If you enable this, a prescription will be required for this product.') }}</small>
                                            </div>
                                        </div>


                                        <!-- Product type -->
                                        <div class="form-group row">
                                            <label class="col-xxl-3 col-from-label fs-13">{{translate('Product Type')}} </label>
                                            <div class="col-xxl-9">
                                                <input type="text" class="form-control @error('product_type') is-invalid @enderror" name="product_type" placeholder="{{ translate('Product Type') }}" value="{{ $product->product_type }}">
                                            </div>
                                        </div>

                                        <!-- Product count -->
                                        {{-- <div class="form-group row">
                                            <label class="col-xxl-3 col-from-label fs-13">{{translate('Product Count')}} </label>
                                            <div class="col-xxl-9">
                                                <input type="text" class="form-control @error('product_count') is-invalid @enderror" name="product_count" placeholder="{{ translate('Product Count') }}" value="{{ $product->product_count }}">
                                            </div>
                                        </div> --}}


                                        <!-- Product Material -->
                                        <div class="form-group row">
                                            <label class="col-xxl-3 col-from-label fs-13">{{translate('Product Material')}} </label>
                                            <div class="col-xxl-9">
                                                <input type="text" class="form-control @error('product_material') is-invalid @enderror" name="product_material" placeholder="{{ translate('Product Material') }}" value="{{ $product->product_material }}">
                                            </div>
                                        </div>

                                        <!-- Product Country of Origin -->
                                        <div class="form-group row">
                                            <label class="col-xxl-3 col-from-label fs-13">{{translate('Product Country of Origin')}} </label>
                                            <div class="col-xxl-9">
                                                <input type="text" class="form-control @error('product_origin') is-invalid @enderror" name="product_origin" placeholder="{{ translate('Product Country of Origin') }}" value="{{ $product->product_origin }}">
                                            </div>
                                        </div>

                                        <!-- Product HSN / HS Code  -->
                                        <div class="form-group row">
                                            <label class="col-xxl-3 col-from-label fs-13">{{translate('Product HSN Code')}} </label>
                                            <div class="col-xxl-9">
                                                <input type="text" class="form-control @error('product_hsn') is-invalid @enderror" name="product_hsn" placeholder="{{ translate('Product HSN Code') }}" value="{{ $product->product_hsn }}">
                                            </div>
                                        </div>

                                        <!-- Product HSN / HS Code  -->
                                        <div class="form-group row">
                                            <label class="col-xxl-3 col-from-label fs-13">{{translate('Product HS Code')}} </label>
                                            <div class="col-xxl-9">
                                                <input type="text" class="form-control @error('product_hs') is-invalid @enderror" name="product_hs" placeholder="{{ translate('Product HS Code') }}" value="{{ $product->product_hs }}">
                                            </div>
                                        </div>

                                    </div>

                                    <!-- Product Category -->
                                    
                                </div>

                                <!-- Description -->
                                <div class="form-group">
                                    <label class="fs-13">{{translate('Description')}}</label>
                                    <div class="">
                                        <textarea class="aiz-text-editor" name="description">{{ $product->getTranslation('description', $lang) }}</textarea>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="fs-13">{{translate('Tabs Contents')}}</label>
                                    @php
                                        $storedTabs = json_decode($product->contents, true) ?? [];
                                    @endphp
                                    <div class="content-target">
                                        @if (!empty($storedTabs))
                                            @foreach ($storedTabs as $tab)
                                                <div class="p-3 p-md-4 mb-3 mb-md-2rem remove-parent" style="border: 1px dashed #e4e5eb;">
                                                    <div class="row gutters-5">
                                                        <!-- iteration (hidden) -->
                                                        <input type="hidden" class="form-control" name="itration[]" value="{{ $tab['iteration'] ?? 1 }}" required>

                                                        <!-- Title -->
                                                        <div class="col-md-12">
                                                            <div class="form-group mb-md-0">
                                                                <input type="text" class="form-control" placeholder="Enter Title" name="tab_title[]" value="{{ $tab['title'] ?? '' }}" required>
                                                            </div>
                                                        </div>

                                                        <!-- Content (textarea) -->
                                                        <div class="col-md">
                                                            <div class="form-group mt-2">
                                                                <textarea name="tab_content[]" rows="8" class="form-control aiz-text-editor" required>{!! $tab['content'] ?? '' !!}</textarea>
                                                            </div>
                                                        </div>

                                                        <!-- Remove Button -->
                                                        <div class="col-md-auto">
                                                            <div class="form-group mb-md-0">
                                                                <button type="button" class="mt-1 btn btn-icon btn-circle btn-sm btn-soft-danger"
                                                                    data-toggle="remove-parent" data-parent=".remove-parent">
                                                                    <i class="las la-times"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>  
                                    <!-- Add button -->
                                    <button
                                        type="button"
                                        class="btn btn-block border hov-bg-soft-secondary fs-14 rounded-0 d-flex align-items-center justify-content-center" style="background: #fcfcfc;"
                                        data-toggle="add-more"
                                        data-content='
                                        <div class="p-3 p-md-4 mb-3 mb-md-2rem remove-parent" style="border: 1px dashed #e4e5eb;">
                                            <div class="row gutters-5">
                                                <input type="hidden" class="form-control" name="itration[]" value="1" required>
                                                <!-- link -->
                                                <div class="col-md-12">
                                                    <div class="form-group mb-md-0">
                                                        <input type="text" class="form-control" placeholder="Enter Title" name="tab_title[]" value="" required>
                                                    </div>
                                                </div>					
                                                <!-- Image -->
                                                <div class="col-md">
                                                    <div class="form-group mt-2">
                                                        <textarea name="tab_content[]" rows="8" class="form-control aiz-text-editor" required></textarea>
                                                    </div>
                                                </div>
                                                <!-- remove parent button -->
                                                <div class="col-md-auto">
                                                    <div class="form-group mb-md-0">
                                                        <button type="button" class="mt-1 btn btn-icon btn-circle btn-sm btn-soft-danger" data-toggle="remove-parent" data-parent=".remove-parent">
                                                            <i class="las la-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>'
                                        data-target=".content-target">
                                        <i class="las la-2x text-success la-plus-circle"></i>
                                        <span class="ml-2">{{ translate('Add New') }}</span>
                                    </button>   
                                </div>

                            </div>

                            <!-- Status -->
                            <h5 class="mb-3 mt-5 pb-3 fs-17 fw-700" style="border-bottom: 1px dashed #e4e5eb;">{{translate('Status')}}</h5>
                            <div class="w-100">
                                <!-- Featured -->
                                <div class="form-group row">
                                    <label class="col-md-3 col-from-label">{{translate('Featured')}}</label>
                                    <div class="col-md-9">
                                        <label class="aiz-switch aiz-switch-success mb-0 d-block">
                                            <input type="checkbox" name="featured" value="1" @if($product->featured == 1) checked @endif>
                                            <span></span>
                                        </label>
                                        <small class="text-muted">{{ translate('If you enable this, this product will be granted as a featured product.') }}</small>
                                    </div>
                                </div>
                                <!-- Todays Deal -->
                                <div class="form-group row">
                                    <label class="col-md-3 col-from-label">{{translate('Todays Deal')}}</label>
                                    <div class="col-md-9">
                                        <label class="aiz-switch aiz-switch-success mb-0 d-block">
                                            <input type="checkbox" name="todays_deal" value="1" @if($product->todays_deal == 1) checked @endif>
                                            <span></span>
                                        </label>
                                        <small class="text-muted">{{ translate('If you enable this, this product will be granted as a todays deal product.') }}</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Flash Deal -->
                            <h5 class="mb-3 mt-4 pb-3 fs-17 fw-700" style="border-bottom: 1px dashed #e4e5eb;">
                                {{translate('Flash Deal')}}
                                <small class="text-muted">({{ translate('If you want to select this product as a flash deal, you can use it') }})</small>
                            </h5>
                            <div class="w-100">
                                <!-- Add To Flash -->
                                <div class="form-group row">
                                    <label class="col-md-3 col-from-label">{{translate('Add To Flash')}}</label>
                                    <div class="col-xxl-9">
                                        @php
                                            $productFlashDealId = $product->flash_deal_products->last()->flash_deal_id ?? null;
                                        @endphp
                                        <select class="form-control aiz-selectpicker" name="flash_deal_id" id="video_provider">
                                            <option value="">{{ translate('Choose Flash Title') }}</option>
                                            @foreach(\App\Models\FlashDeal::where("status", 1)->get() as $flash_deal)
                                                <option value="{{ $flash_deal->id }}" @if($productFlashDealId == $flash_deal->id) selected @endif>
                                                    {{ $flash_deal->title }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <!-- Discount -->
                                <div class="form-group row">
                                    <label class="col-md-3 col-from-label">{{translate('Discount')}}</label>
                                    <div class="col-xxl-9">
                                        <input type="number" name="flash_discount" value="{{ $product->discount }}" min="0" step="0.01" class="form-control">
                                    </div>
                                </div>
                                <!-- Discount Type -->
                                <div class="form-group row">
                                    <label class="col-md-3 col-from-label">{{translate('Discount Type')}}</label>
                                    <div class="col-xxl-9">
                                        <select class="form-control aiz-selectpicker" name="flash_discount_type" id="">
                                            <option value="">{{ translate('Choose Discount Type') }}</option>
                                            <!-- <option value="amount" @if($product->discount_type == 'amount') selected @endif>
                                                {{translate('Flat')}}
                                            </option> -->
                                            <option value="percent" @if($product->discount_type == 'percent') selected @endif>
                                                {{translate('Percent')}}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Vat & TAX -->
                            <h5 class="mb-3 mt-4 pb-3 fs-17 fw-700" style="border-bottom: 1px dashed #e4e5eb;">{{translate('Vat & TAX')}}</h5>
                            <div class="w-100">
                                @foreach(\App\Models\Tax::where('tax_status', 1)->get() as $tax)
                                    <label for="name">
                                        {{$tax->name}}
                                        <input type="hidden" value="{{$tax->id}}" name="tax_id[]">
                                    </label>

                                    @php
                                        $tax_amount = 0;
                                        $tax_type = '';
                                        foreach($tax->product_taxes as $row) {
                                            if($product->id == $row->product_id) {
                                                $tax_amount = $row->tax;
                                                $tax_type = $row->tax_type;
                                            }
                                        }
                                    @endphp

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <input type="number" lang="en" min="0" value="{{ $tax_amount }}" step="0.01" placeholder="{{ translate('Tax') }}" name="tax[]" class="form-control">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <select class="form-control aiz-selectpicker" name="tax_type[]">
                                                {{-- <option value="amount" @if($tax_type == 'amount') selected @endif>
                                                    {{translate('Flat')}}
                                                </option> --}}
                                                <option value="percent" @if($tax_type == 'percent') selected @endif>
                                                    {{translate('Percent')}}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Files & Media -->
                    <div class="tab-pane fade" id="files_and_media" role="tabpanel" aria-labelledby="files-and-media-tab">
                        <div class="bg-white p-3 p-sm-2rem">
                            <!-- Product Files & Media -->
                            <h5 class="mb-3 pb-3 fs-17 fw-700" style="border-bottom: 1px dashed #e4e5eb;">{{translate('Product Files & Media')}}</h5>
                            <div class="w-100">
                                <!-- Gallery Images -->
                                {{-- <div class="form-group row">
                                    <label class="col-md-3 col-form-label" for="signinSrEmail">{{translate('Gallery Images')}}</label>
                                    <div class="col-md-9">
                                        <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="true">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
                                            </div>
                                            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                                            <input type="hidden" name="photos" value="{{ $product->photos }}" class="selected-files">
                                        </div>
                                        <div class="file-preview box sm">
                                        </div>
                                        <small class="text-muted">{{translate('These images are visible in product details page gallery. Minimum dimensions required: 900px width X 900px height.')}}</small>
                                    </div>
                                </div> --}}
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label" for="signinSrEmail">{{ translate('Gallery Media') }}</label>
                                    <div class="col-md-9">
                                        <div class="input-group" data-toggle="aizuploader" data-type="image,video" data-multiple="true">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
                                            </div>
                                            <div class="form-control file-amount">{{ translate('Choose Files') }}</div>
                                            <input type="hidden" name="photos" value="{{ $product->photos }}" class="selected-files">
                                        </div>
                                        <div class="file-preview box sm">
                                        </div>
                                        <small class="text-muted">
                                            {{ translate('These images and videos are visible in the product details page gallery. Minimum image dimensions: 900px × 900px. Accepted video formats: MP4, WEBM.') }}
                                        </small>
                                    </div>
                                </div>
                                <!-- Thumbnail Image -->
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label" for="signinSrEmail">{{translate('Thumbnail Image')}}</label>
                                    <div class="col-md-9">
                                        <div class="input-group" data-toggle="aizuploader" data-type="image">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
                                            </div>
                                            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                                            <input type="hidden" name="thumbnail_img" value="{{ $product->thumbnail_img }}" class="selected-files">
                                        </div>
                                        <div class="file-preview box sm">
                                        </div>
                                        <small class="text-muted">{{translate("This image is visible in all product box. Minimum dimensions required: 195px width X 195px height. Keep some blank space around main object of your image as we had to crop some edge in different devices to make it responsive.")}}</small>
                                    </div>
                                </div>
                            </div>
                            <!-- Video Provider -->
                            <div class="form-group row">
                                <label class="col-md-3 col-from-label">{{translate('Video Provider')}}</label>
                                <div class="col-md-9">
                                    <select class="form-control aiz-selectpicker" name="video_provider" id="video_provider">
                                    <option value="youtube" <?php if ($product->video_provider == 'youtube') echo "selected"; ?> >{{translate('Youtube')}}</option>
                                    <option value="dailymotion" <?php if ($product->video_provider == 'dailymotion') echo "selected"; ?> >{{translate('Dailymotion')}}</option>
                                    <option value="vimeo" <?php if ($product->video_provider == 'vimeo') echo "selected"; ?> >{{translate('Vimeo')}}</option>
                                </select>
                                </div>
                            </div>
                            <!-- Video Link -->
                            <div class="form-group row">
                                <label class="col-md-3 col-from-label">{{translate('Video Link')}}</label>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" name="video_link" value="{{ $product->video_link }}" placeholder="{{ translate('Video Link') }}">
                                    <small class="text-muted">{{translate("Use proper link without extra parameter. Don't use short share link/embeded iframe code.")}}</small>
                                </div>
                            </div>
                            <!-- PDF Specification -->
                            <div class="form-group row">
                                <label class="col-md-3 col-form-label" for="signinSrEmail">{{translate('PDF Specification')}}</label>
                                <div class="col-md-9">
                                    <div class="input-group" data-toggle="aizuploader" data-type="document">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
                                        </div>
                                        <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                                        <input type="hidden" name="pdf" value="{{ $product->pdf }}" class="selected-files">
                                    </div>
                                    <div class="file-preview box sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Price & Stock -->
                    <div class="tab-pane fade" id="price_and_stocks" role="tabpanel" aria-labelledby="price-and-stocks-tab">
                        <div class="bg-white p-3 p-sm-2rem">
                            <!-- tab Title -->
                            <h5 class="mb-3 pb-3 fs-17 fw-700" style="border-bottom: 1px dashed #e4e5eb;">{{translate('Product price & stock')}}</h5>
                            <div class="w-100">
                                <!-- Colors -->
                                <div class="form-group row gutters-5">
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" value="{{translate('Colors')}}" disabled>
                                    </div>
                                    <div class="col-md-8">
                                        <select class="form-control aiz-selectpicker" data-live-search="true" data-selected-text-format="count" name="colors[]" id="colors" multiple>
                                            @foreach (\App\Models\Color::orderBy('name', 'asc')->get() as $key => $color)
                                            <option
                                                value="{{ $color->code }}"
                                                data-content="<span><span class='size-15px d-inline-block mr-2 rounded border' style='background:{{ $color->code }}'></span><span>{{ $color->name }}</span></span>"
                                                <?php if (in_array($color->code, json_decode($product->colors))) echo 'selected' ?>
                                                ></option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="aiz-switch aiz-switch-success mb-0">
                                            <input value="1" type="checkbox" name="colors_active" <?php if (count(json_decode($product->colors)) > 0) echo "checked"; ?> >
                                            <span></span>
                                        </label>
                                    </div>
                                </div>
                                <!-- Attributes -->
                                <div class="form-group row gutters-5">
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" value="{{translate('Attributes')}}" disabled>
                                    </div>
                                    <div class="col-md-8">
                                        <select name="choice_attributes[]" id="choice_attributes" data-selected-text-format="count" data-live-search="true" class="form-control aiz-selectpicker" multiple data-placeholder="{{ translate('Choose Attributes') }}">
                                            @foreach (\App\Models\Attribute::all() as $key => $attribute)
                                            <option value="{{ $attribute->id }}" @if($product->attributes != null && in_array($attribute->id, json_decode($product->attributes, true))) selected @endif>{{ $attribute->getTranslation('name') }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <p>{{ translate('Choose the attributes of this product and then input values of each attribute') }}</p>
                                    <br>
                                </div>

                                <!-- choice options -->
                                <div class="customer_choice_options" id="customer_choice_options">
                                    @foreach (json_decode($product->choice_options) as $key => $choice_option)
                                    <div class="form-group row">
                                        <div class="col-lg-3">
                                            <input type="hidden" name="choice_no[]" value="{{ $choice_option->attribute_id }}">
                                            <input type="text" class="form-control" name="choice[]" value="{{ optional(\App\Models\Attribute::find($choice_option->attribute_id))->getTranslation('name') }}" placeholder="{{ translate('Choice Title') }}" disabled>
                                        </div>
                                        <div class="col-lg-8">
                                            <select class="form-control aiz-selectpicker attribute_choice" data-live-search="true" name="choice_options_{{ $choice_option->attribute_id }}[]" data-selected-text-format="count" multiple>
                                                @foreach (\App\Models\AttributeValue::where('attribute_id', $choice_option->attribute_id)->get() as $row)
                                                <option value="{{ $row->value }}" @if( in_array($row->value, $choice_option->values)) selected @endif>
                                                    {{ $row->value }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                                <div class="form-group row">
                                    <label class="col-md-3 col-from-label">{{translate('MRP price')}} <span class="text-danger">*</span></label>
                                    <div class="col-md-6">
                                        <input type="text" placeholder="{{translate('Unit MRP price')}}" name="mrp_price" class="form-control @error('mrp_price') is-invalid @enderror" value="{{$product->mrp_price}}" required readonly>
                                    </div>
                                </div>

                                <!-- Unit price -->
                                <div class="form-group row">
                                    <label class="col-md-3 col-from-label">{{translate('Unit price')}} <span class="text-danger">*</span></label>
                                    <div class="col-md-6">
                                        <input type="text" placeholder="{{translate('Unit price')}}" name="unit_price" class="form-control @error('unit_price') is-invalid @enderror" value="{{$product->unit_price}}" required readonly>
                                    </div>
                                </div>

                                @php
                                    $start_date = date('d-m-Y H:i:s', $product->discount_start_date);
                                    $end_date = date('d-m-Y H:i:s', $product->discount_end_date);
                                @endphp
                                <!-- Discount Date Range -->
                                <div class="form-group row d-none">
                                    <label class="col-sm-3 control-label" for="start_date">{{translate('Discount Date Range')}}</label>
                                    <div class="col-sm-9">
                                      <input type="text" class="form-control aiz-date-range" @if($product->discount_start_date && $product->discount_end_date) value="{{ $start_date.' to '.$end_date }}" @endif name="date_range" placeholder="{{translate('Select Date')}}" data-time-picker="true" data-format="DD-MM-Y HH:mm:ss" data-separator=" to " autocomplete="off">
                                    </div>
                                </div>
                                <!-- Discount -->
                                <div class="form-group row d-none">
                                    <label class="col-md-3 col-from-label">{{translate('Discount')}} <span class="text-danger">*</span></label>
                                    <div class="col-md-6">
                                        <input type="number" lang="en" min="0" step="0.01" placeholder="{{translate('Discount')}}" name="discount" class="form-control @error('discount') is-invalid @enderror" value="{{ $product->discount }}">
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-control aiz-selectpicker" name="discount_type">
                                            <option value="amount" <?php if ($product->discount_type == 'amount') echo "selected"; ?> >{{translate('Flat')}}</option>
                                            <option value="percent" <?php if ($product->discount_type == 'percent') echo "selected"; ?> >{{translate('Percent')}}</option>
                                        </select>
                                    </div>
                                </div>

                                @if(addon_is_activated('club_point'))
                                    <!-- club point -->
                                    <div class="form-group row">
                                        <label class="col-md-3 col-from-label">
                                            {{translate('Set Point')}}
                                        </label>
                                        <div class="col-md-6">
                                            <input type="number" lang="en" min="0" value="{{ $product->earn_point }}" step="0.01" placeholder="{{ translate('1') }}" name="earn_point" class="form-control">
                                        </div>
                                    </div>
                                @endif

                                <div id="show-hide-div">
                                    <!-- Quantity -->
                                    <div class="form-group row" id="quantity">
                                        <label class="col-md-3 col-from-label">{{translate('Quantity')}} <span class="text-danger">*</span></label>
                                        <div class="col-md-6">
                                            <input type="number" lang="en" value="{{ optional($product->stocks->first())->qty ?? $product->current_stock ?? 0 }}" step="1" placeholder="{{translate('Quantity')}}" name="current_stock" class="form-control" required>
                                        </div>
                                    </div>
                                    <!-- SKU -->
                                    <div class="form-group row">
                                        <label class="col-md-3 col-from-label">
                                            {{translate('SKU')}}
                                        </label>
                                        <div class="col-md-6">
                                            <input type="text" placeholder="{{ optional($product->stocks->first())->sku ?? $product->sku ?? '' }}" name="sku" class="form-control">
                                        </div>
                                    </div>
                                    <!-- Minimum Purchase Qty -->
                                    <div class="form-group row">
                                        <label class="col-md-3 col-from-label">{{translate('Minimum Purchase Qty')}} <span class="text-danger h5">*</span></label>
                                        <div class="col-md-6">
                                            <input type="number" lang="en" min="1" step="1" name="min_qty" value="{{ optional($product->stocks->first())->min_qty ?? ($product->min_qty ?? 1) }}" class="form-control" required>
                                            <small class="text-muted">{{translate("The minimum quantity needs to be purchased by your customer.")}}</small>
                                        </div>
                                    </div>
                                    <!-- Product Minimum Pack Size -->
                                    <div class="form-group row">
                                        <label class="col-md-3 col-from-label">{{translate('Product Minimum Pack Size')}} <span class="text-danger h5">*</span></label>
                                        <div class="col-md-6">
                                            <input type="number" lang="en" min="1" step="1" name="product_min_pack_size" value="{{ optional($product->stocks->first())->product_min_pack_size ?? ($product->product_min_pack_size ?? 1) }}" class="form-control" required>
                                        </div>
                                    </div>
                                    <!-- Product Expiry Date -->
                                    <div class="form-group row">
                                        <label class="col-md-3 col-from-label">{{translate('Product Expiry Date')}}</label>
                                        <div class="col-md-6">
                                            <input type="date" name="product_exp_date" value="{{ optional($product->stocks->first())->product_exp_date ?? $product->product_exp_date }}" class="form-control" placeholder="{{ translate('Product Expiry Date') }}">
                                        </div>
                                    </div>
                                    <!-- Product Dimensions -->
                                    <div class="form-group row">
                                        <label class="col-md-3 col-from-label">{{ translate('Product Dimensions L x W x H (cm)') }} <span class="text-danger">*</span></label>
                                        <div class="col-md-6 d-flex gap-2 flex-wrap">
                                            <input type="number" min="0" step="0.01" class="form-control mx-1 mb-2" name="length" value="{{ optional($product->stocks->first())->length ?? $product->length }}" placeholder="{{ translate('L') }}" required>
                                            <input type="number" min="0" step="0.01" class="form-control mx-1 mb-2" name="width" value="{{ optional($product->stocks->first())->width ?? $product->width }}" placeholder="{{ translate('W') }}" required>
                                            <input type="number" min="0" step="0.01" class="form-control mx-1 mb-2" name="height" value="{{ optional($product->stocks->first())->height ?? $product->height }}" placeholder="{{ translate('H') }}" required>
                                        </div>
                                    </div>
                                    <!-- Product Weight / Volume -->
                                    <div class="form-group row">
                                        <label class="col-md-3 col-from-label">{{translate('Product Weight / Volume')}} <span class="text-danger h5">*</span></label>
                                        <div class="col-md-6">
                                            <input type="number" min="0" step="0.001" name="weight" value="{{ optional($product->stocks->first())->weight ?? ($product->weight ?? $product->product_weight_vol ?? 0) }}" class="form-control" placeholder="{{ translate('Weight / Volume') }}" required>
                                        </div>
                                    </div>
                                    <!-- Package Count -->
                                    <div class="form-group row">
                                        <label class="col-md-3 col-from-label">{{translate('Package Count')}}</label>
                                        <div class="col-md-6">
                                            <input type="number" lang="en" min="1" step="1" name="count" value="{{ optional($product->stocks->first())->count ?? 1 }}" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <!-- External link -->
                                <div class="form-group row">
                                    <label class="col-md-3 col-from-label">
                                        {{translate('External link')}}
                                    </label>
                                    <div class="col-md-9">
                                        <input type="text" placeholder="{{ translate('External link') }}" name="external_link" value="{{ $product->external_link }}" class="form-control">
                                        <small class="text-muted">{{translate('Leave it blank if you do not use external site link')}}</small>
                                    </div>
                                </div>
                                <!-- External link button text -->
                                <div class="form-group row">
                                    <label class="col-md-3 col-from-label">
                                        {{translate('External link button text')}}
                                    </label>
                                    <div class="col-md-9">
                                        <input type="text" placeholder="{{ translate('External link button text') }}" name="external_link_btn" value="{{ $product->external_link_btn }}" class="form-control">
                                        <small class="text-muted">{{translate('Leave it blank if you do not use external site link')}}</small>
                                    </div>
                                </div>

                                <div class="form-group row d-none">
                                    <label
                                        class="col-md-3 col-from-label">{{ translate('Change Product variant') }}</label>
                                    <div class="col-md-9">
                                        <label class="aiz-switch aiz-switch-success mb-0 d-block">
                                            <input type="checkbox" name="reset_variant_prices" value="1">
                                            <span></span>
                                        </label>
                                        <small
                                            class="text-muted">{{ translate('If you enable this, the prices of all variants of this product will be reset using an Excel or CSV file.') }}</small>
                                    </div>
                                </div>

                                <br>
                                <!-- sku combination -->
                                <div class="sku_combination" id="sku_combination">

                                </div>
                            </div>

                            <!-- Low Stock Quantity -->
                            <h5 class="mb-3 pb-3 fs-17 fw-700" style="border-bottom: 1px dashed #e4e5eb;">{{translate('Low Stock Quantity Warning')}}</h5>
                            <div class="w-100 mb-3">
                                <div class="form-group row">
                                    <label class="col-md-3 col-from-label">
                                        {{translate('Quantity')}}
                                    </label>
                                    <div class="col-md-9">
                                        <input type="number" name="low_stock_quantity" value="{{ $product->low_stock_quantity }}" min="0" step="1" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <!-- Stock Visibility State -->
                            <h5 class="mb-3 pb-3 fs-17 fw-700" style="border-bottom: 1px dashed #e4e5eb;">{{translate('Stock Visibility State')}}</h5>
                            <div class="w-100">
                                <!-- Show Stock Quantity -->
                                <div class="form-group row">
                                    <label class="col-md-3 col-from-label">{{translate('Show Stock Quantity')}}</label>
                                    <div class="col-md-9">
                                        <label class="aiz-switch aiz-switch-success mb-0">
                                            <input type="radio" name="stock_visibility_state" value="quantity" @if($product->stock_visibility_state == 'quantity') checked @endif>
                                            <span></span>
                                        </label>
                                    </div>
                                </div>
                                <!-- Show Stock With Text Only -->
                                <div class="form-group row">
                                    <label class="col-md-3 col-from-label">{{translate('Show Stock With Text Only')}}</label>
                                    <div class="col-md-9">
                                        <label class="aiz-switch aiz-switch-success mb-0">
                                            <input type="radio" name="stock_visibility_state" value="text" @if($product->stock_visibility_state == 'text') checked @endif>
                                            <span></span>
                                        </label>
                                    </div>
                                </div>
                                <!-- Hide Stock -->
                                <div class="form-group row">
                                    <label class="col-md-3 col-from-label">{{translate('Hide Stock')}}</label>
                                    <div class="col-md-9">
                                        <label class="aiz-switch aiz-switch-success mb-0">
                                            <input type="radio" name="stock_visibility_state" value="hide" @if($product->stock_visibility_state == 'hide') checked @endif>
                                            <span></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SEO -->
                    <div class="tab-pane fade" id="seo" role="tabpanel" aria-labelledby="seo-tab">
                        <div class="bg-white p-3 p-sm-2rem">
                            <!-- tab Title -->
                            <h5 class="mb-3 pb-3 fs-17 fw-700" style="border-bottom: 1px dashed #e4e5eb;">{{translate('SEO Meta Tags')}}</h5>
                            <div class="w-100">
                                <!-- Meta Title -->
                                <div class="form-group row">
                                    <label class="col-md-3 col-from-label">{{translate('Meta Title')}}</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="meta_title" value="{{ $product->meta_title }}" placeholder="{{translate('Meta Title')}}">
                                    </div>
                                </div>
                                <!-- Description -->
                                <div class="form-group row">
                                    <label class="col-md-3 col-from-label">{{translate('Description')}}</label>
                                    <div class="col-md-9">
                                        <textarea name="meta_description" rows="8" class="form-control">{{ $product->meta_description }}</textarea>
                                    </div>
                                </div>
                                <!-- Meta Image -->
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label" for="signinSrEmail">{{ translate('Meta Image') }}</label>
                                    <div class="col-md-9">
                                        <div class="input-group" data-toggle="aizuploader" data-type="image">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
                                            </div>
                                            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
                                            <input type="hidden" name="meta_img" value="{{ $product->meta_img }}" class="selected-files">
                                        </div>
                                        <div class="file-preview box sm">
                                        </div>
                                    </div>
                                </div>
                                <!-- Slug -->
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">{{translate('Slug')}}</label>
                                    <div class="col-md-8">
                                        <input type="text" placeholder="{{translate('Slug')}}" id="slug" name="slug" value="{{ $product->slug }}" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping -->
                    <div class="tab-pane fade" id="shipping" role="tabpanel" aria-labelledby="shipping-tab">
                        <div class="bg-white p-3 p-sm-2rem">
                            <!-- Shipping Configuration -->
                            <h5 class="mb-3 pb-3 fs-17 fw-700" style="border-bottom: 1px dashed #e4e5eb;">{{translate('Shipping Configuration')}}</h5>
                            <div class="w-100">
                                <!-- Cash On Delivery -->
                                @if (get_setting('cash_payment') == '1')
                                    <div class="form-group row">
                                        <label class="col-md-3 col-from-label">{{translate('Cash On Delivery')}}</label>
                                        <div class="col-md-9">
                                            <label class="aiz-switch aiz-switch-success mb-0">
                                                <input type="checkbox" name="cash_on_delivery" value="1" @if($product->cash_on_delivery == 1) checked @endif>
                                                <span></span>
                                            </label>
                                        </div>
                                    </div>
                                @else
                                    <p>
                                        {{ translate('Cash On Delivery option is disabled. Activate this feature from here') }}
                                        <a href="{{route('activation.index')}}" class="aiz-side-nav-link {{ areActiveRoutes(['shipping_configuration.index','shipping_configuration.edit','shipping_configuration.update'])}}">
                                            <span class="aiz-side-nav-text">{{translate('Cash Payment Activation')}}</span>
                                        </a>
                                    </p>
                                @endif

                                @if (get_setting('shipping_type') == 'product_wise_shipping')
                                <!-- Free Shipping -->
                                <div class="form-group row">
                                    <label class="col-md-3 col-from-label">{{translate('Free Shipping')}}</label>
                                    <div class="col-md-9">
                                        <label class="aiz-switch aiz-switch-success mb-0">
                                            <input type="radio" name="shipping_type" value="free" @if($product->shipping_type == 'free') checked @endif>
                                            <span></span>
                                        </label>
                                    </div>
                                </div>
                                <!-- Flat Rate -->
                                <div class="form-group row">
                                    <label class="col-md-3 col-from-label">{{translate('Flat Rate')}}</label>
                                    <div class="col-md-9">
                                        <label class="aiz-switch aiz-switch-success mb-0">
                                            <input type="radio" name="shipping_type" value="flat_rate" @if($product->shipping_type == 'flat_rate') checked @endif>
                                            <span></span>
                                        </label>
                                    </div>
                                </div>
                                <!-- Shipping cost -->
                                <div class="flat_rate_shipping_div" style="display: none">
                                    <div class="form-group row">
                                        <label class="col-md-3 col-from-label">{{translate('Shipping cost')}}</label>
                                        <div class="col-md-9">
                                            <input type="number" lang="en" min="0" value="{{ $product->shipping_cost }}" step="0.01" placeholder="{{ translate('Shipping cost') }}" name="flat_shipping_cost" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <!-- Is Product Quantity Mulitiply -->
                                <div class="form-group row">
                                    <label class="col-md-3 col-from-label">{{translate('Is Product Quantity Mulitiply')}}</label>
                                    <div class="col-md-9">
                                        <label class="aiz-switch aiz-switch-success mb-0">
                                            <input type="checkbox" name="is_quantity_multiplied" value="1" @if($product->is_quantity_multiplied == 1) checked @endif>
                                            <span></span>
                                        </label>
                                    </div>
                                </div>
                                @else
                                <p>
                                    {{ translate('Product wise shipping cost is disable. Shipping cost is configured from here') }}
                                    <a href="{{route('shipping_configuration.index')}}" class="aiz-side-nav-link {{ areActiveRoutes(['shipping_configuration.index','shipping_configuration.edit','shipping_configuration.update'])}}">
                                        <span class="aiz-side-nav-text">{{translate('Shipping Configuration')}}</span>
                                    </a>
                                </p>
                                @endif
                            </div>

                            <!-- Estimate Shipping Time -->
                            <h5 class="mb-3 mt-4 pb-3 fs-17 fw-700" style="border-bottom: 1px dashed #e4e5eb;">{{translate('Estimate Shipping Time')}}</h5>
                            <div class="w-100">
                                <div class="form-group row">
                                    <label class="col-md-3 col-from-label">{{translate('Shipping Days')}}</label>
                                    <div class="col-md-9">
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="est_shipping_days" value="{{ $product->est_shipping_days }}" min="1" step="1" placeholder="{{translate('Shipping Days')}}">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text" id="inputGroupPrepend">{{translate('Days')}}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Warranty -->
                    <div class="tab-pane fade" id="warranty" role="tabpanel" aria-labelledby="warranty-tab">
                        <div class="bg-white p-3 p-sm-2rem">
                            <h5 class="mb-3 pb-3 fs-17 fw-700" style="border-bottom: 1px dashed #e4e5eb;">{{translate('Warranty')}}</h5>
                            <div class="form-group row">
                                <label class="col-md-2 col-from-label">{{translate('Warranty')}}</label>
                                <div class="col-md-10">
                                    <label class="aiz-switch aiz-switch-success mb-0">
                                        <input type="checkbox" name="has_warranty" onchange="warrantySelection()" @if($product->has_warranty == 1) checked @endif> 
                                        <span></span>
                                    </label>
                                </div>
                            </div>
                            <div class="w-100 warranty_selection_div @if($product->has_warranty != 1) d-none @endif" >
                                <div class="form-group row">
                                    <div class="col-md-2"></div>
                                    <div class="col-md-10">
                                        <select class="form-control aiz-selectpicker" 
                                            name="warranty_id" 
                                            id="warranty_id" 
                                            data-selected="{{ $product->warranty_id }}" 
                                            data-live-search="true"
                                            @if($product->has_warranty == 1) required @endif
                                        >
                                            <option value="">{{ translate('Select Warranty') }}</option>
                                            @foreach (\App\Models\Warranty::all() as $warranty)
                                                <option value="{{ $warranty->id }}" @selected(old('warranty_id') == $warranty->id)>{{ $warranty->getTranslation('text') }}</option>
                                            @endforeach
                                        </select>

                                        <input type="hidden" name="warranty_note_id" id="warranty_note_id">
                                        
                                        <h5 class="fs-14 fw-600 mb-3 mt-4 pb-3" style="border-bottom: 1px dashed #e4e5eb;">{{translate('Warranty Note')}}</h5>
                                        <div id="warranty_note">
                                            @if($product->warrantyNote != null)
                                                <div class="border border-gray my-2 p-2">
                                                    {{ $product->warrantyNote->getTranslation('description') ?? '' }}
                                                </div>
                                            @endif
                                        </div>
                                        <button
                                            type="button"
                                            class="btn btn-block border border-dashed hov-bg-soft-secondary mt-2 fs-14 rounded-0 d-flex align-items-center justify-content-center"
                                            onclick="noteModal('warranty')">
                                            <i class="las la-plus"></i>
                                            <span class="ml-2">{{ translate('Select Warranty Note') }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Frequently Bought Product -->
                    <div class="tab-pane fade" id="frequenty-bought-product" role="tabpanel" aria-labelledby="frequenty-bought-product-tab">
                        <div class="bg-white p-3 p-sm-2rem">
                            <!-- tab Title -->
                            <h5 class="mb-3 pb-3 fs-17 fw-700">{{translate('Frequently Bought')}}</h5>
                            <div class="w-100">
                                <div class="d-flex mb-4">
                                    {{-- <div class="radio mar-btm mr-5 d-flex align-items-center">
                                        <input
                                            id="fq_bought_select_products"
                                            type="radio"
                                            name="frequently_bought_selection_type"
                                            value="product"
                                            onchange="fq_bought_product_selection_type()"
                                            @if($product->frequently_bought_selection_type == 'product') checked @endif
                                        >
                                        <label for="fq_bought_select_products" class="fs-14 fw-700 mb-0 ml-2">{{translate('Select Product')}}</label>
                                    </div> --}}
                                    <div class="radio mar-btm mr-3 d-flex align-items-center">
                                        <input
                                            id="fq_bought_select_category"
                                            type="radio"
                                            name="frequently_bought_selection_type"
                                            value="category"
                                            onchange="fq_bought_product_selection_type()"
                                            {{-- @if($product->frequently_bought_selection_type == 'category') checked @endif --}}
                                            checked
                                        >
                                        <label for="fq_bought_select_category" class="fs-14 fw-700 mb-0 ml-2">{{translate('Select Category')}}</label>
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-body">
                                        <div class="fq_bought_select_product_div d-none">
                                            @php
                                                $fq_bought_products = $product->frequently_bought_products()->where('category_id', null)->get();
                                            @endphp

                                            <div id="selected-fq-bought-products">
                                                @if(count($fq_bought_products) > 0)
                                                    <div class="table-responsive mb-4">
                                                        <table class="table mb-0">
                                                            <thead>
                                                                <tr>
                                                                    <th class="opacity-50 pl-0">{{ translate('Product Thumb') }}</th>
                                                                    <th class="opacity-50">{{ translate('Product Name') }}</th>
                                                                    <th class="opacity-50">{{ translate('Category') }}</th>
                                                                    <th class="opacity-50 text-right pr-0">{{ translate('Options') }}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($fq_bought_products as $fQBproduct)
                                                                    @isset($fQBproduct->frequently_bought_product->id)
                                                                        <tr class="remove-parent">
                                                                            <input type="hidden" name="fq_bought_product_ids[]" value="{{ $fQBproduct->frequently_bought_product->id }}">
                                                                            <td class="w-150px pl-0" style="vertical-align: middle;">
                                                                                <p class="d-block size-48px">
                                                                                    <img src="{{ uploaded_asset($fQBproduct->frequently_bought_product->thumbnail_img) }}" alt="{{ translate('Image')}}"
                                                                                        class="h-100 img-fit lazyload" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                                                                </p>
                                                                            </td>
                                                                            <td style="vertical-align: middle;">
                                                                                <p class="d-block fs-13 fw-700 hov-text-primary mb-1 text-dark" title="{{ translate('Product Name') }}">
                                                                                    {{ $fQBproduct->frequently_bought_product->getTranslation('name') }}
                                                                                </p>
                                                                            </td>
                                                                            <td style="vertical-align: middle;">{{ $fQBproduct->frequently_bought_product->main_category->name ?? translate('Category Not Found') }}</td>
                                                                            <td class="text-right pr-0" style="vertical-align: middle;">
                                                                                <button type="button" class="mt-1 btn btn-icon btn-circle btn-sm btn-soft-danger" data-toggle="remove-parent" data-parent=".remove-parent">
                                                                                    <i class="las la-trash"></i>
                                                                                </button>
                                                                            </td>
                                                                        </tr>
                                                                    @endisset
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @endif
                                            </div>

                                            <button
                                                type="button"
                                                class="btn btn-block border border-dashed hov-bg-soft-secondary fs-14 rounded-0 d-flex align-items-center justify-content-center"
                                                onclick="showFqBoughtProductModal()">
                                                <i class="las la-plus"></i>
                                                <span class="ml-2">{{ translate('Add More') }}</span>
                                            </button>
                                        </div>

                                        {{-- Select Category for Frequently Bought Product --}}
                                        <div class="fq_bought_select_category_div d-none">
                                            @php
                                                $fq_bought_product_category_id = $product->frequently_bought_products()->where('category_id','!=', null)->first();
                                                $fqCategory = $fq_bought_product_category_id != null ? $fq_bought_product_category_id->category_id : null;

                                            @endphp
                                            <div class="form-group row">
                                                <label class="col-md-2 col-from-label">{{translate('Category')}} <span class="text-danger">*</span></label>
                                                <div class="col-md-10">
                                                    <select
                                                        class="form-control aiz-selectpicker"
                                                        data-placeholder="{{ translate('Select a Category')}}"
                                                        name="fq_bought_product_category_id"
                                                        data-live-search="true"
                                                        data-selected="{{ $fqCategory }}"
                                                        required
                                                    >
                                                        @foreach ($categories as $category)
                                                            <option value="{{ $category->id }}">{{ $category->getTranslation('name') }}</option>
                                                            @foreach ($category->childrenCategories as $childCategory)
                                                                @include('categories.child_category', ['child_category' => $childCategory])
                                                            @endforeach
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Update Button -->
                    <div class="mt-4 text-right">
                        <button type="button" onclick="submitFormWithTab()" name="button" class="mx-2 btn btn-success w-230px btn-md rounded-2 fs-14 fw-700 shadow-success action-btn">{{ translate('Update') }}</button>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('modal')
	<!-- Frequently Bought Product Select Modal -->
    @include('modals.product_select_modal')

    {{-- Note Modal --}}
    @include('modals.note_modal')

@endsection

@section('script')
<!-- Treeview js -->
<script src="{{ static_asset('assets/js/hummingbird-treeview.js') }}"></script>

<script type="text/javascript">
    $(document).ready(function (){
        show_hide_shipping_div();

        $("#treeview").hummingbird();
        var main_id = '{{ $product->category_id != null ? $product->category_id : 0 }}';
        var selected_ids = '{{ implode(",",$old_categories) }}';
        if (selected_ids != '') {
            const myArray = selected_ids.split(",");
            for (let i = 0; i < myArray.length; i++) {
                const element = myArray[i];
                $('#treeview input:checkbox#'+element).prop('checked',true);
                $('#treeview input:checkbox#'+element).parents( "ul" ).css( "display", "block" );
                $('#treeview input:checkbox#'+element).parents( "li" ).children('.las').removeClass( "la-plus" ).addClass('la-minus');
            }
        }
        $('#treeview input:radio[value='+main_id+']').prop('checked',true);

        fq_bought_product_selection_type();

    });

    $("[name=shipping_type]").on("change", function (){
        show_hide_shipping_div();
    });

    function show_hide_shipping_div() {
        var shipping_val = $("[name=shipping_type]:checked").val();

        $(".flat_rate_shipping_div").hide();

        if(shipping_val == 'flat_rate'){
            $(".flat_rate_shipping_div").show();
        }
    }

    function add_more_customer_choice_option(i, name){
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type:"POST",
            url:'{{ route('products.add-more-choice-option') }}',
            data:{
               attribute_id: i
            },
            success: function(data) {
                var obj = JSON.parse(data);
                $('#customer_choice_options').append('\
                <div class="form-group row">\
                    <div class="col-md-3">\
                        <input type="hidden" name="choice_no[]" value="'+i+'">\
                        <input type="text" class="form-control" name="choice[]" value="'+name+'" placeholder="{{ translate('Choice Title') }}" readonly>\
                    </div>\
                    <div class="col-md-8">\
                        <select class="form-control aiz-selectpicker attribute_choice" data-live-search="true" name="choice_options_'+ i +'[]" data-selected-text-format="count" multiple>\
                            '+obj+'\
                        </select>\
                    </div>\
                </div>');
                AIZ.plugins.bootstrapSelect('refresh');
           }
       });


    }

    $('input[name="colors_active"]').on('change', function() {
        if(!$('input[name="colors_active"]').is(':checked')){
            $('#colors').prop('disabled', true);
            AIZ.plugins.bootstrapSelect('refresh');
        }
        else{
            $('#colors').prop('disabled', false);
            AIZ.plugins.bootstrapSelect('refresh');
        }
        update_sku();
    });

    $(document).on("change", ".attribute_choice",function() {
        update_sku();
    });

    $('#colors').on('change', function() {
        update_sku();
    });

    function delete_row(em){
        $(em).closest('.form-group').remove();
        update_sku();
    }

    function delete_variant(em){
        $(em).closest('.variant').remove();
    }

    function update_sku(){
        $.ajax({
           type:"POST",
           url:'{{ route('products.sku_combination_edit') }}',
           data:$('#choice_form').serialize(),
           success: function(data){
                $('#sku_combination').html(data);
                setTimeout(() => {
                        AIZ.uploader.previewGenerate();
                }, "2000");
                if (data.trim().length > 1) {
                    $('#show-hide-div').hide();
                    AIZ.plugins.sectionFooTable('#sku_combination');
                }
                else {
                    $('#show-hide-div').show();
                }
           }
        });
    }

    AIZ.plugins.tagify();

    $(document).ready(function(){
        update_sku();

        $('.remove-files').on('click', function(){
            $(this).parents(".col-md-4").remove();
        });
    });

    $('#choice_attributes').on('change', function() {
        $.each($("#choice_attributes option:selected"), function(j, attribute){
            flag = false;
            $('input[name="choice_no[]"]').each(function(i, choice_no) {
                if($(attribute).val() == $(choice_no).val()){
                    flag = true;
                }
            });
            if(!flag){
                add_more_customer_choice_option($(attribute).val(), $(attribute).text());
            }
        });

        var str = @php echo $product->attributes @endphp;

        $.each(str, function(index, value){
            flag = false;
            $.each($("#choice_attributes option:selected"), function(j, attribute){
                if(value == $(attribute).val()){
                    flag = true;
                }
            });
            if(!flag){
                $('input[name="choice_no[]"][value="'+value+'"]').parent().parent().remove();
            }
        });

        update_sku();
    });

    function fq_bought_product_selection_type(){
        var productSelectionType = $("input[name='frequently_bought_selection_type']:checked").val();
        if(productSelectionType == 'product'){
            $('.fq_bought_select_product_div').removeClass('d-none');
            $('.fq_bought_select_category_div').addClass('d-none');
        }
        else if(productSelectionType == 'category'){
            $('.fq_bought_select_category_div').removeClass('d-none');
            $('.fq_bought_select_product_div').addClass('d-none');
        }
    }

    function showFqBoughtProductModal() {
        $('#fq-bought-product-select-modal').modal('show', {backdrop: 'static'});
    }

    function filterFqBoughtProduct() {
        var productID = $('input[name=id]').val();
        var searchKey = $('input[name=search_keyword]').val();
        var fqBroughCategory = $('select[name=fq_brough_category]').val();
        $.post('{{ route('product.search') }}', { _token: AIZ.data.csrf, product_id: productID, search_key:searchKey, category:fqBroughCategory, product_type:"physical" }, function(data){
            $('#product-list').html(data);
            AIZ.plugins.sectionFooTable('#product-list');
        });
    }

    function addFqBoughtProduct() {
        var selectedProducts = [];
        $("input:checkbox[name=fq_bought_product_id]:checked").each(function() {
            selectedProducts.push($(this).val());
        });

        var fqBoughtProductIds = [];
        $("input[name='fq_bought_product_ids[]']").each(function() {
            fqBoughtProductIds.push($(this).val());
        });

        var productIds = selectedProducts.concat(fqBoughtProductIds.filter((item) => selectedProducts.indexOf(item) < 0))

        $.post('{{ route('get-selected-products') }}', { _token: AIZ.data.csrf, product_ids:productIds}, function(data){
            $('#fq-bought-product-select-modal').modal('hide');
            $('#selected-fq-bought-products').html(data);
            AIZ.plugins.sectionFooTable('#selected-fq-bought-products');
        });
    }

    // Warranty
    function warrantySelection(){
        if($('input[name="has_warranty"]').is(':checked')) {
            $('.warranty_selection_div').removeClass('d-none');
            $('#warranty_id').attr('required', true);
        }
        else {
            $('.warranty_selection_div').addClass('d-none');
            $('#warranty_id').removeAttr('required');
        }
    }
    
    function noteModal(noteType){
        $.post('{{ route('get_notes') }}',{_token:'{{ @csrf_token() }}', note_type: noteType}, function(data){
            $('#note_modal #note_modal_content').html(data);
            $('#note_modal').modal('show', {backdrop: 'static'});
        });
    }

    function addNote(noteId, noteType){
        var noteDescription = $('#note_description_'+ noteId).val();
        $('#'+noteType+'_note_id').val(noteId);
        $('#'+noteType+'_note').html(noteDescription);
        $('#'+noteType+'_note').addClass('border border-gray my-2 p-2');
        $('#note_modal').modal('hide');
    }

</script>
<script>
    $(document).ready(function(){
        var hash = document.location.hash;
        if (hash) {
            $('.nav-tabs a[href="'+hash+'"]').tab('show');
            $('#tab').val(location.hash.substr(1));
        }else{
            $('.nav-tabs a[href="#general"]').tab('show');
            $('#tab').val('general');
        }

        // Change hash for page-reload
        $('.nav-tabs a').on('shown.bs.tab', function (e) {
            window.location.hash = e.target.hash;
        });
    });

    function submitFormWithTab(){
        var hash = document.location.hash;
        if (hash) {
            $('#tab').val(location.hash.substr(1));
        }else{
            $('#tab').val('');
        }
        $('#choice_form').submit();
    }
</script>
{{-- 
<script type="text/javascript">
    $(document).ready(function() {
        warrantySelection();

        // Initialize AIZ validation
        // Initialize AIZ validation
        initValidate('#choice_form');

        $('#choice_form').on('submit', function (e) {
            // If AIZ validation fails, stop
            if (!$(this).valid()) {
                e.preventDefault();
                AIZ.plugins.notify('danger', 'Please fill all required fields.');
                return;
            }

            // Check if category_id is selected
            const categoryId = $('[name="category_id"]:checked').val();
            if (!categoryId) {
                e.preventDefault();
                AIZ.plugins.notify('danger', 'Please select a Main category.');
                return;
            }

            // ✅ Validate category_ids (checkbox group)
            const categoryIds = $('[name="category_ids[]"]:checked');
            if (categoryIds.length === 0) {
                e.preventDefault();
                AIZ.plugins.notify('danger', 'Please select the Same Product Category.');
                return;
            }

            // Check unit_price
            const unitPrice = parseFloat($('[name="unit_price"]').val());
            if (isNaN(unitPrice) || unitPrice <= 0) {
                e.preventDefault();
                AIZ.plugins.notify('danger', 'Please set the unit price.');
                return;
            }

            // Colors: if colors_active is checked then colors[] must have at least one selection
            const colorsActive = $('[name="colors_active"]').is(':checked');
            if (colorsActive) {
                const selectedColors = $('#colors').val() || [];
                if (selectedColors.length === 0) {
                    e.preventDefault();
                    AIZ.plugins.notify('danger', 'Please select at least one color.');

                    // activate Price & Stocks tab and scroll to the colors select (robust attempt)
                    const $tab = $('a[data-target="#price_and_stocks"], a[href="#price_and_stocks"], button[data-bs-target="#price_and_stocks"]');
                    if ($tab.length) {
                        try { $tab.first().tab('show'); } catch(err){ $tab.first().trigger('click'); }
                    }
                    setTimeout(function(){
                        const $colors = $('#colors');
                        if ($colors.length) {
                            // scroll to the select; works even if select is hidden by custom picker
                            $('html,body').animate({scrollTop: $colors.offset().top - 120}, 350);
                            // focus the underlying select (some pickers may need focusing their button)
                            try { $colors.focus(); } catch(e){}
                        }
                    }, 250);

                    return;
                }
            }

            // // Now check the special condition
            // const selectedChoices = $('#choice_attributes').val(); // returns array

            // if (!selectedChoices || selectedChoices.length === 0) {
            //     e.preventDefault();
            //     AIZ.plugins.notify('danger', 'Please select at least one attribute.');
            //     return;
            // }

            // if (!selectedChoices.includes('3')) {
            //     e.preventDefault();
            //     AIZ.plugins.notify('danger', 'Attribute Role must be selecte and its all Role (Pts, Ptr, Ptd, Gov, Expo)');
            //     return;
            // }

            this.submit();

        });



    });


</script> 
--}}

<!-- put this after your form (or in a scripts stack) -->
<script type="text/javascript">
$(document).ready(function () {
    warrantySelection();
    // Initialize custom field validation
    function initFieldValidation(selector) {
        $(selector).validate({
            errorElement: "div",
            errorPlacement: function (error, element) {
                // Remove previous error message for this element
                element.closest(".form-group").find(".invalid-feedback").remove();

                // Add Bootstrap's invalid-feedback styling
                error.addClass("invalid-feedback");

                // Insert error message directly after the input field itself
                if (element.parent('.input-group').length) {
                    error.insertAfter(element.parent()); // for input groups
                } else {
                    error.insertAfter(element); // normal fields
                }
            },
            highlight: function (element) {
                $(element).addClass("is-invalid");
                $(element).closest(".form-group").addClass("has-error");
            },
            unhighlight: function (element) {
                $(element).removeClass("is-invalid");
                $(element).closest(".form-group").removeClass("has-error");
                $(element).closest(".form-group").find(".invalid-feedback").remove();
            }
        });
    }

    // Usage
    initFieldValidation("#choice_form");

    // initValidate("#choice_form");

    const $form = $("#choice_form");

    function clearFrontendErrors() {
        $form.find(".is-invalid").removeClass("is-invalid");
        $form.find(".invalid-feedback.frontend").remove();
        $form.find("[data-frontend-id]").removeAttr("data-frontend-id");
    }

    $form.on("submit", function (e) {
        clearFrontendErrors();

        {{--
        // 1. Stop if jQuery validation fails
        // if (!$form.valid()) {
        //     e.preventDefault();
        //     AIZ.plugins.notify("danger", "Please fill all required fields.");
        //     return false;
        // }
        --}}

        // 2. Main category validation
        const categoryId = $('[name="category_id"]:checked').val();
        if (!categoryId) {
            e.preventDefault();
            AIZ.plugins.notify("danger", "Please select a Main category.");
            return false;
        }

        // 3. Same Product Category validation
        const categoryIds = $('[name="category_ids[]"]:checked');
        if (categoryIds.length === 0) {
            e.preventDefault();
            AIZ.plugins.notify("danger", "Please select the Same Product Category.");
            return false;
        }

        // 4. Colors validation
        const colorsActive = $('[name="colors_active"]').is(":checked");
        if (colorsActive) {
            const selectedColors = $("#colors").val() || [];
            if (selectedColors.length === 0) {
                e.preventDefault();
                AIZ.plugins.notify("danger", "Please select at least one color.");

                // Open tab and scroll to color select
                const $tab = $('a[data-target="#price_and_stocks"], a[href="#price_and_stocks"], button[data-bs-target="#price_and_stocks"]');
                if ($tab.length) {
                    try { $tab.first().tab("show"); } catch (err) { $tab.first().trigger("click"); }
                }
                setTimeout(function () {
                    const $colors = $("#colors");
                    if ($colors.length) {
                        $("html,body").animate({ scrollTop: $colors.offset().top - 120 }, 350);
                        try { $colors.focus(); } catch (e) {}
                    }
                }, 250);

                return false;
            }
        }

        // Custom required field validation for all fields
        const errors = [];
        let firstInvalidEl = null;
        let idx = 0;

        $form.find("input, select, textarea").each(function () {
            const $el = $(this);
            if ($el.prop("disabled")) return;

            idx++;
            const isRequired = $el.prop("required");

            // Checkbox / radio required
            if (isRequired && ($el.is(":checkbox") || $el.is(":radio"))) {
                const name = $el.attr("name");
                if ($form.find(`[name="${name}"]:checked`).length === 0) {
                    errors.push($el);
                    if (!firstInvalidEl) firstInvalidEl = $el;
                }
                return;
            }

            // Normal required fields
            if (isRequired) {
                const val = $el.val();
                if (val === null || (typeof val === "string" && $.trim(val) === "")) {
                    errors.push($el);
                    if (!firstInvalidEl) firstInvalidEl = $el;
                    return;
                }
            }

            // HTML5 checkValidity
            if ($el[0] && typeof $el[0].checkValidity === "function") {
                if (!$el[0].checkValidity()) {
                    errors.push($el);
                    if (!firstInvalidEl) firstInvalidEl = $el;
                }
            }
        });

        if (errors.length) {
            e.preventDefault();
            AIZ.plugins.notify("danger", "Please provide the required information in the highlighted fields.");

            // Highlight all invalid fields
            errors.forEach(function ($el) {
                $el.addClass("is-invalid");

                // Add invalid-feedback if it doesn't exist
                if ($el.next(".invalid-feedback").length === 0) {
                    $el.after('<div class="invalid-feedback frontend">This field is required.</div>');
                }
            });

            // Focus the first invalid element and show its tab
            if (firstInvalidEl) {
                const $pane = $(firstInvalidEl).closest(".tab-pane");
                const paneId = $pane.attr("id");
                if (paneId) {
                    const $tabLink = $(`a[data-target="#${paneId}"], a[href="#${paneId}"]`);
                    if ($tabLink.length) $tabLink.tab("show");
                }

                $("html,body").animate({ scrollTop: $(firstInvalidEl).offset().top - 100 }, 350);
                $(firstInvalidEl).focus();
            }

            return false;
        }

        // If no errors, allow submit
        return true;
    });

    // Clear error state on user input
    $form.on("input change", "input, select, textarea", function () {
        const $el = $(this);
        if ($el.hasClass("is-invalid")) {
            $el.removeClass("is-invalid");
            $el.next(".invalid-feedback.frontend").remove();
        }
    });

});
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.querySelector('input[name="reset_variant_prices"]');
        let originalValues = {};

        function applyChanges() {
            const skuCombination = document.getElementById('sku_combination');
            if (!skuCombination) return;

            const priceInputs = skuCombination.querySelectorAll(
                'input[name^="price_"], input[name^="mrp_price_"]');

            // Store original values only once
            if (Object.keys(originalValues).length === 0) {
                priceInputs.forEach(input => {
                    originalValues[input.name] = input.value;
                });
            }

            if (checkbox.checked) {
                // skuCombination.style.opacity = '1';
                // skuCombination.style.pointerEvents = 'auto';

                priceInputs.forEach(input => {
                    input.value = '0';
                });
            } else {
                // skuCombination.style.opacity = '0.5';
                // skuCombination.style.pointerEvents = 'none';

                priceInputs.forEach(input => {
                    if (originalValues.hasOwnProperty(input.name)) {
                        input.value = originalValues[input.name];
                    }
                });
            }
        }

        // Observe for dynamic addition of sku_combination
        const observer = new MutationObserver(function(mutationsList, observer) {
            for (let mutation of mutationsList) {
                if (mutation.type === 'childList') {
                    const skuCombination = document.getElementById('sku_combination');
                    if (skuCombination) {
                        applyChanges();
                        observer.disconnect();
                        break;
                    }
                }
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        // Run on page load if already there
        applyChanges();

        // Apply changes when checkbox is toggled
        checkbox.addEventListener('change', applyChanges);

        // Watch for any changes inside customer_choice_options
        const customerChoices = document.getElementById('customer_choice_options');
        if (customerChoices) {
            customerChoices.addEventListener('change', function() {
                setTimeout(function() {
                    if (!checkbox.checked) {
                        checkbox.checked = true;
                        applyChanges();
                    }
                }, 3000); // 3000 milliseconds = 3 seconds
            });
        }
    });
</script>

@endsection
