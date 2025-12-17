<style>
.badge {
    height: auto;
    width: auto;
    font-size: 16px;
}
</style>
@if(count($combinations) > 0)
<div class="accordion" id="skuAccordion">
	@foreach ($combinations as $key => $combination)
		@php
			$sku = '';
			foreach (explode(' ', $product_name) as $value) {
				$sku .= substr($value, 0, 1);
			}

			$str = '';
			foreach ($combination as $index => $item){
				if($index > 0 ){
					$str .= '-'.str_replace(' ', '', $item);
					$sku .='-'.str_replace(' ', '', $item);
				}
				else{
					if($colors_active == 1){
						$color_name = \App\Models\Color::where('code', $item)->first()->name;
						$str .= $color_name;
						$sku .='-'.$color_name;
					}
					else{
						$str .= str_replace(' ', '', $item);
						$sku .='-'.str_replace(' ', '', $item);
					}
				}
			}
			$isOpen = 'show';
		@endphp
		@if(strlen($str) > 0)
		<div class="card mb-3">
			<div class="card-header d-flex justify-content-between align-items-center" id="heading-{{ $key }}">
				<div class="d-flex align-items-center" style="gap:10px;">
					<span class="badge badge-primary">{{ $str }}</span>
				</div>
				<button class="btn btn-sm btn-outline-secondary" type="button"
						data-toggle="collapse" data-target="#collapse-{{ $key }}"
						aria-expanded="true"
						aria-controls="collapse-{{ $key }}">
					{{ translate('Edit fields') }}
				</button>
			</div>
			<div id="collapse-{{ $key }}" class="collapse {{ $isOpen }}" aria-labelledby="heading-{{ $key }}">
				<div class="card-body">
					<div class="row gutters-10">
						<div class="col-md-4">
							<h6 class="text-muted mb-2">{{ translate('Identifiers') }}</h6>
							<div class="form-group mb-3">
								<label class="form-label mb-1">{{ translate('SKU') }}</label>
								<input type="text" name="sku_{{ $str }}" value="" class="form-control" required>
							</div>
						</div>

						<div class="col-md-4">
							<h6 class="text-muted mb-2">{{ translate('Pricing & Inventory') }}</h6>
							<div class="form-row">
								<div class="col-6 mb-3">
									<label class="form-label mb-1">{{ translate('MRP Price') }}</label>
									<input type="number" lang="en" name="mrp_price_{{ $str }}" value="{{ $unit_price }}" min="0" step="0.01" class="form-control" required>
								</div>
								<div class="col-6 mb-3">
									<label class="form-label mb-1">{{ translate('Selling Price') }}</label>
									<input type="number" lang="en" name="price_{{ $str }}" value="{{ $unit_price }}" min="0" step="0.01" class="form-control" required readonly>
								</div>
								<div class="col-6 mb-3">
									<label class="form-label mb-1">{{ translate('Min Purchase Qty') }}</label>
									<input type="number" lang="en" name="min_qty_{{ $str }}" class="form-control" placeholder="{{ translate('Min Qty') }}" step="1" min="1" value="1" required>
								</div>
								<div class="col-6 mb-3">
									<label class="form-label mb-1">{{ translate('Stock Quantity') }}</label>
									<input type="number" lang="en" name="qty_{{ $str }}" value="10" min="0" step="1" class="form-control" required>
								</div>
								<div class="col-12 mb-3">
									<label class="form-label mb-1">{{ translate('Product Expiry Date') }}</label>
									<input type="date" name="product_exp_date_{{ $str }}" class="form-control" placeholder="{{ translate('Expiry Date') }}">
								</div>
							</div>
						</div>

						<div class="col-md-4">
							<h6 class="text-muted mb-2">{{ translate('Dimensions & Packaging') }}</h6>
							<div class="form-row">
								<div class="col-4 mb-3">
									<label class="form-label mb-1">{{ translate('Length (cm)') }}</label>
									<input type="number" lang="en" name="length_{{ $str }}" class="form-control"
										placeholder="{{ translate('Length (cm)') }}" step="0.01" min="0" required>
								</div>
								<div class="col-4 mb-3">
									<label class="form-label mb-1">{{ translate('Width (cm)') }}</label>
									<input type="number" lang="en" name="width_{{ $str }}" class="form-control"
										placeholder="{{ translate('Width (cm)') }}" step="0.01" min="0" required>
								</div>
								<div class="col-4 mb-3">
									<label class="form-label mb-1">{{ translate('Height (cm)') }}</label>
									<input type="number" lang="en" name="height_{{ $str }}" class="form-control"
										placeholder="{{ translate('Height (cm)') }}" step="0.01" min="0" required>
								</div>
								<div class="col-6 mb-3">
									<label class="form-label mb-1">{{ translate('Weight / Volume') }}</label>
									<input type="number" lang="en" name="weight_{{ $str }}" class="form-control"
										placeholder="{{ translate('Weight / Volume') }}" step="0.001" min="0" required>
								</div>
								<div class="col-6 mb-3">
									<label class="form-label mb-1">{{ translate('Package Count') }}</label>
									<input type="number" lang="en" name="count_{{ $str }}" class="form-control"
										placeholder="{{ translate('Package Count') }}" step="0.01" min="0" required>
								</div>
							</div>
						</div>

						<div class="col-md-6 mt-3">
							<h6 class="text-muted mb-2">{{ translate('COA') }}</h6>
							<div class="input-group" data-toggle="aizuploader" data-type="document">
								<div class="input-group-prepend">
									<div class="input-group-text bg-soft-secondary font-weight-medium">
										{{ translate('Browse') }}
									</div>
								</div>
								<div class="form-control file-amount text-truncate">
									{{ translate('Choose PDF File') }}
								</div>
								<input type="hidden" name="coa_{{ $str }}" class="selected-files">
							</div>
							<div class="file-preview box sm"></div>
						</div>

						<div class="col-md-6 mt-3">
							<h6 class="text-muted mb-2">{{ translate('Photo') }}</h6>
							<div class=" input-group " data-toggle="aizuploader" data-type="image">
								<div class="input-group-prepend">
									<div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
								</div>
								<div class="form-control file-amount text-truncate">{{ translate('Choose File') }}</div>
								<input type="hidden" name="img_{{ $str }}" class="selected-files">
							</div>
							<div class="file-preview box sm"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		@endif
	@endforeach
</div>
@endif
