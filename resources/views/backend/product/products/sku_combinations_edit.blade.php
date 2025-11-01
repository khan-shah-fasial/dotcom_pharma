@if (count($combinations) > 0)
    <table class="table table-bordered aiz-table">
        <thead>
            <tr>
                <td class="text-center">
                    {{ translate('Variant') }}
                </td>
                <td class="text-center">
                    {{ translate('SKU') }}
                </td>
                <td class="text-center">
                    {{ translate('MRP Price') }}
                </td>
                <td class="text-center">
                    {{ translate('Selling Price') }}
                </td>
                {{-- <td class="text-center">
                    {{ translate('Dimension') }}
                </td> --}}
                <td class="text-center">
                    {{ translate('L x W x H (cm)') }}
                </td>
                <td class="text-center">
                    {{ translate('Weight / Volume') }}
                </td>
                <td class="text-center">
                    {{ translate('Package Count') }}
                </td>
                <td class="text-center" data-breakpoints="lg">
                    {{ translate('Quantity') }}
                </td>
                <td class="text-center" data-breakpoints="lg">
                    {{ translate('COA') }}
                </td>
                <td class="text-center" data-breakpoints="lg">
                    {{ translate('Photo') }}
                </td>
                <td class="text-center" data-breakpoints="lg">
                    {{ translate('Role Base Price') }}
                </td>
            </tr>
        </thead>
        <tbody>

            @foreach ($combinations as $key => $combination)
                @php
                    $variation_available = false;
                    $sku = '';
                    foreach (explode(' ', $product_name) as $key => $value) {
                        $sku .= substr($value, 0, 1);
                    }

                    $str = '';
                    foreach ($combination as $key => $item) {
                        if ($key > 0) {
                            $str .= '-' . str_replace(' ', '', $item);
                            $sku .= '-' . str_replace(' ', '', $item);
                        } else {
                            if ($colors_active == 1) {
                                $color_name = \App\Models\Color::where('code', $item)->first()->name;
                                $str .= $color_name;
                                $sku .= '-' . $color_name;
                            } else {
                                $str .= str_replace(' ', '', $item);
                                $sku .= '-' . str_replace(' ', '', $item);
                            }
                        }
                        $stock = $product->stocks->where('variant', $str)->first();
                        // if($stock != null) {
                        //     $variation_available = true;
                        // }
                    }
                @endphp



                @if (strlen($str) > 0)
                    <tr class="variant">
                        <td>
                            <label for="" class="control-label">{{ $str }}</label>
                        </td>
                        <td>
                            <input type="text" name="sku_{{ $str }}"
                                value="@php
                            if($stock != null) {
                                echo $stock->sku;
                            }
                            else {
                                echo $str;
                            } @endphp"
                                class="form-control" required>
                        </td>
                        <td>
                            <input type="number" lang="en" name="mrp_price_{{ $str }}"
                                value="@php
                            if ($product->unit_price == $unit_price) {
                                if($stock != null){
                                    echo $stock->mrp_price;
                                }
                                else {
                                    // echo $unit_price;
                                    echo 0;
                                }
                            }
                            else{
                                // echo $unit_price;
                                echo 0;
                            } @endphp"
                                min="0" step="0.01" class="form-control" required>
                        </td>
                        <td>
                            <input type="number" lang="en" name="price_{{ $str }}"
                                value="@php
                            if ($product->unit_price == $unit_price) {
                                if($stock != null){
                                    echo $stock->price;
                                }
                                else {
                                    // echo $unit_price;
                                    echo 0;
                                }
                            }
                            else{
                                // echo $unit_price;
                                echo 0;
                            } @endphp"
                                min="0" step="0.01" class="form-control" required readonly>
                        </td>
                        {{-- <td>
                            <input type="text" lang="en" name="dimension_{{ $str }}"
                                value="@php
                            if($stock != null){
                                echo $stock->dimension;
                            }
                            else {
                                echo $str;
                            } @endphp"
                                class="form-control" required>
                        </td> --}}
                        <td class="d-flex" style="gap:5px;">
                            <input type="number" lang="en" name="length_{{ $str }}"
                                value="@php
                            if($stock != null){
                                echo $stock->length;
                            }
                            else {
                                echo $str;
                            } @endphp"
                                class="form-control" placeholder="L (cm)" step="0.01" min="0" required>
                            <input type="number" lang="en" name="width_{{ $str }}"
                                value="@php
                            if($stock != null){
                                echo $stock->width;
                            }
                            else {
                                echo $str;
                            } @endphp"
                                class="form-control" placeholder="W (cm)" step="0.01" min="0" required>
                            <input type="number" lang="en" name="height_{{ $str }}"
                                value="@php
                            if($stock != null){
                                echo $stock->height;
                            }
                            else {
                                echo $str;
                            } @endphp"
                                class="form-control" placeholder="H (cm)" step="0.01" min="0" required>
                        </td>
                        <td>
                            <input type="number" name="weight_{{ $str }}"
                                value="@php
                            if($stock != null) {
                                echo $stock->weight;
                            }
                            else {
                                echo $str;
                            } @endphp"
                                class="form-control" step="0.01" min="0" required>
                        </td>
                        <td>
                            <input type="text" name="count_{{ $str }}"
                                value="@php
                            if($stock != null) {
                                echo $stock->count;
                            }
                            else {
                                echo $str;
                            } @endphp"
                                class="form-control" required>
                        </td>
                        <td>
                            <input type="number" lang="en" name="qty_{{ $str }}"
                                value="@php
                            if($stock != null){
                                echo $stock->qty;
                            }
                            else{
                                echo '10';
                            } @endphp"
                                min="0" step="1" class="form-control" required>
                        </td>

                        <td>
                            <div class="input-group" data-toggle="aizuploader" data-type="document">
                                <div class="input-group-prepend">
                                    <div class="input-group-text bg-soft-secondary font-weight-medium">
                                        {{ translate('Browse') }}
                                    </div>
                                </div>
                                <div class="form-control file-amount text-truncate">
                                    {{ translate('Choose PDF File') }}
                                </div>

                                <input type="hidden" name="coa_{{ $str }}" class="selected-files"
                                    value="{{ $stock && $stock->coa ? $stock->coa : '' }}">
                            </div>

                            <div class="file-preview box sm">
                                {{-- @if ($stock && $stock->coa)
                                    <a href="{{ uploaded_asset($stock->coa) }}" target="_blank" class="btn btn-soft-primary btn-sm mt-2">
                                        <i class="las la-file-pdf"></i> {{ translate('View Current PDF') }}
                                    </a>
                                @endif --}}
                            </div>
                        </td>

                        <td>
                            <div class="input-group" data-toggle="aizuploader" data-type="image">
                                <div class="input-group-prepend">
                                    <div class="input-group-text bg-soft-secondary font-weight-medium">
                                        {{ translate('Browse') }}</div>
                                </div>
                                <div class="form-control file-amount text-truncate">{{ translate('Choose File') }}
                                </div>
                                <input type="hidden" name="img_{{ $str }}" class="selected-files"
                                    value="@php
                                if($stock != null){
                                    echo $stock->image;
                                }
                                else{
                                    echo null;
                                } @endphp">
                            </div>
                            <div class="file-preview box sm"></div>
                        </td>


                        <td>
                            @php
                                $role_base_price = json_decode($stock->role_price, true); // decode JSON to array
                            @endphp

                            @if(!empty($role_base_price) && count($role_base_price) > 0)
                                <div class="overflow-x-auto border border-gray-200 rounded-lg shadow-sm">
                                    <table id="rolePriceTable" class="border border-gray-200 rounded-lg text-center">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="text-sm font-medium text-gray-600 uppercase tracking-wider text-center">
                                                    Role
                                                </th>
                                                <th class="text-sm font-medium text-gray-600 uppercase tracking-wider text-center">
                                                    Price
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 bg-white">
                                            @foreach ($role_base_price as $role => $price)
                                                <tr>
                                                    <td class="text-sm text-gray-700 text-center">
                                                        {{ strtoupper($role) }}
                                                    </td>
                                                    <td class="text-sm text-gray-700 text-center">
                                                        {{ $price }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p>No data</p>
                            @endif
                        </td>




                    </tr>
                @endif
            @endforeach

        </tbody>
    </table>
@endif
