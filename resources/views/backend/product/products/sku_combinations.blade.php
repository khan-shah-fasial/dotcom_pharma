<style>
.badge {
    height: auto;
    width: auto;
    font-size: 16px;
}
.card .sku-card-header {
    background: #616161;
    border-bottom: 1px solid #e0e6ed;
}
.batch-table {
    border: 1px solid #e0e6ed;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 0;
}
.batch-table thead {
    background: #f8f9fa;
}
.batch-table thead th {
    font-weight: 600;
    font-size: 13px;
    color: #495057;
    padding: 12px 8px;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
    text-align: left;
    vertical-align: middle;
}
.batch-table thead th.text-center {
    text-align: center;
}
.batch-table tbody td {
    padding: 12px 8px;
    vertical-align: middle;
    border-bottom: 1px solid #f0f0f0;
}
.batch-table tbody td.text-center {
    text-align: center;
}
.batch-table tbody tr:last-child td {
    border-bottom: none;
}
.batch-table tbody tr:hover {
    background-color: #f8f9fa;
}
.batch-table .form-control-sm {
    font-size: 13px;
    padding: 6px 10px;
    height: auto;
    line-height: 1.5;
}
.batch-table .form-control-sm:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
.coa-uploader-cell {
    min-width: 200px;
    max-width: 250px;
}
.coa-uploader-wrapper {
    position: relative;
}
.coa-uploader-wrapper .input-group {
    margin-bottom: 0;
}
.coa-uploader-wrapper .input-group-text {
    font-size: 11px;
    padding: 4px 8px;
    white-space: nowrap;
}
.coa-uploader-wrapper .form-control.file-amount {
    font-size: 11px;
    padding: 4px 8px;
    min-height: 28px;
}
.coa-uploader-wrapper .file-preview {
    margin-top: 5px;
    max-height: 60px;
    overflow-y: auto;
    font-size: 11px;
}
.batch-table .btn-xs {
    padding: 4px 8px;
    font-size: 12px;
    line-height: 1.2;
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
			$variantKey = strtolower(str_replace(['.', ' ', '-'], '_', $str));
		@endphp
		@if(strlen($str) > 0)
		<div class="card mb-3">
			<div class="card-header d-flex justify-content-between align-items-center sku-card-header" id="heading-{{ $key }}">
				<div class="d-flex align-items-center" style="gap:10px;">
					<span class="badge badge-primary">{{ $str }}</span>
				</div>
				<button class="btn btn-sm btn-outline-warning" type="button"
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
							<div class="border rounded p-2 h-100">
								<h6 class="text-muted mb-3">{{ translate('Identifiers') }}</h6>
								<div class="form-group mb-3">
									<label class="form-label mb-1">{{ translate('SKU') }}</label>
									<input
										type="text"
										name="sku_{{ $str }}"
										value="{{ request('sku_'.$str, '') }}"
										class="form-control"
										required
									>
								</div>

								<div class="col-sm-12 mb-2">
									<div class="d-flex align-items-start">
										<label class="aiz-switch aiz-switch-success mb-0 mr-2 mt-1">
											<input
												type="checkbox"
												name="is_hidden_{{ $str }}"
												value="1"
												{{ request('is_hidden_'.$str) ? 'checked' : '' }}
											>
											<span></span>
										</label>
										<div>
											<label class="form-label mb-0 d-block">{{ translate('Hide Variant from Product Details') }}</label>
											<small class="text-muted">{{ translate('Enable this to keep this variant unavailable on the product page.') }}</small>
										</div>
									</div>
								</div>

							</div>
						</div>

						<div class="col-md-8">
							<div class="border rounded p-2 h-100">
								<h6 class="text-muted mb-3">{{ translate('Inventory') }}</h6>

								<div class="form-row mb-3">
									<div class="col-sm-6 col-lg-4 mb-3 d-none">
										<label class="form-label mb-1">{{ translate('Selling Price') }}</label>
										<input
											type="number"
											lang="en"
											name="price_{{ $str }}"
											value="{{ request('price_'.$str, $unit_price) }}"
											min="0"
											step="0.01"
											class="form-control"
											readonly
										>
									</div>
									<div class="col-sm-6 col-lg-4 mb-3">
										<label class="form-label mb-1">{{ translate('Min Order Qty') }}</label>
										<input
											type="number"
											lang="en"
											name="min_qty_{{ $str }}"
											class="form-control"
											placeholder="{{ translate('Min Qty') }}"
											step="1"
											min="1"
											value="{{ request('min_qty_'.$str, 1) }}"
											required
										>
									</div>
									<div class="col-sm-6 col-lg-4 mb-3">
										<label class="form-label mb-1">{{ translate('Package Count') }}</label>
										<input
											type="number"
											lang="en"
											name="count_{{ $str }}"
											class="form-control"
											placeholder="{{ translate('Package Count') }}"
											step="0.01"
											min="0"
											value="{{ request('count_'.$str) }}"
											required
										>
									</div>

									<div class="col-md-6 col-lg-4">
										<label class="form-label mb-1">{{ translate('Photo') }}</label>
										<div class=" input-group " data-toggle="aizuploader" data-type="image">
											<div class="input-group-prepend">
												<div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
											</div>
											<div class="form-control file-amount text-truncate">{{ translate('Choose File') }}</div>
											<input
												type="hidden"
												name="img_{{ $str }}"
												class="selected-files"
												value="{{ request('img_'.$str) }}"
											>
										</div>
										<div class="file-preview box sm"></div>
									</div>

								</div>
							</div>
						</div>
					</div>

					<div class="row gutters-10 mt-3">
						<div class="col-lg-4">
							<div class="border rounded p-2 h-100">
								<div class="d-flex justify-content-between align-items-center mb-2">
									<h6 class="text-muted mb-0">{{ translate('Each Piece (Base)') }}</h6>
								</div>
								<div class="form-row">
									<div class="col-6 mb-3">
										<label class="form-label mb-1">{{ translate('Qty per Piece') }}</label>
										<input
											type="number"
											lang="en"
											name="qty_per_piece_{{ $str }}"
											class="form-control"
											placeholder="{{ translate('Enter qty per piece') }}"
											step="1"
											min="0"
											value="{{ request('qty_per_piece_'.$str) }}"
										>
									</div>
									<div class="col-6 mb-3">
										<label class="form-label mb-1">{{ translate('Weight Of Each Piece') }}</label>
										<input
											type="number"
											lang="en"
											name="weight_{{ $str }}"
											class="form-control"
											placeholder="{{ translate('Weight / Volume') }}"
											step="0.001"
											min="0"
											value="{{ request('weight_'.$str) }}"
											required
										>
									</div>
									<div class="col-4 mb-3">
										<label class="form-label mb-1">{{ translate('Piece Length (cm)') }}</label>
										<input
											type="number"
											lang="en"
											name="length_{{ $str }}"
											class="form-control"
											placeholder="{{ translate('Length (cm)') }}"
											step="0.01"
											min="0"
											value="{{ request('length_'.$str) }}"
											required
										>
									</div>
									<div class="col-4 mb-3">
										<label class="form-label mb-1">{{ translate('Piece Width (cm)') }}</label>
										<input
											type="number"
											lang="en"
											name="width_{{ $str }}"
											class="form-control"
											placeholder="{{ translate('Width (cm)') }}"
											step="0.01"
											min="0"
											value="{{ request('width_'.$str) }}"
											required
										>
									</div>
									<div class="col-4 mb-3">
										<label class="form-label mb-1">{{ translate('Piece Height (cm)') }}</label>
										<input
											type="number"
											lang="en"
											name="height_{{ $str }}"
											class="form-control"
											placeholder="{{ translate('Height (cm)') }}"
											step="0.01"
											min="0"
											value="{{ request('height_'.$str) }}"
											required
										>
									</div>
								</div>
							</div>
						</div>

						<div class="col-lg-4">
							<div class="border rounded p-2 h-100">
								<div class="d-flex justify-content-between align-items-center mb-2">
									<h6 class="text-muted mb-0">{{ translate('Inner Buffer Box / Shrink Pack') }}</h6>
								</div>
								<div class="form-row">
									<div class="col-6 mb-3">
										<label class="form-label mb-1">{{ translate('Qty Per Inner Buffer Box / Shrink Pack') }}</label>
										<input
											type="number"
											name="qty_per_buffer_box_{{ $str }}"
											class="form-control"
											step="1"
											min="0"
											placeholder="{{ translate('Units per buffer box') }}"
											value="{{ request('qty_per_buffer_box_'.$str) }}"
										>
									</div>
									<div class="col-6 mb-3">
										<label class="form-label mb-1">{{ translate('Weight Of Inner Buffer Box / Shrink Pack') }}</label>
										<input
											type="number"
											name="weight_buffer_box_{{ $str }}"
											class="form-control"
											step="0.001"
											min="0"
											placeholder="{{ translate('Weight per buffer box') }}"
											value="{{ request('weight_buffer_box_'.$str) }}"
										>
									</div>
									<div class="col-4 mb-3">
										<label class="form-label mb-1">{{ translate('Buffer Length (cm)') }}</label>
										<input
											type="number"
											name="buffer_length_{{ $str }}"
											class="form-control"
											step="0.01"
											min="0"
											placeholder="{{ translate('Length') }}"
											value="{{ request('buffer_length_'.$str) }}"
										>
									</div>
									<div class="col-4 mb-3">
										<label class="form-label mb-1">{{ translate('Buffer Width (cm)') }}</label>
										<input
											type="number"
											name="buffer_width_{{ $str }}"
											class="form-control"
											step="0.01"
											min="0"
											placeholder="{{ translate('Width') }}"
											value="{{ request('buffer_width_'.$str) }}"
										>
									</div>
									<div class="col-4 mb-3">
										<label class="form-label mb-1">{{ translate('Buffer Height (cm)') }}</label>
										<input
											type="number"
											name="buffer_height_{{ $str }}"
											class="form-control"
											step="0.01"
											min="0"
											placeholder="{{ translate('Height') }}"
											value="{{ request('buffer_height_'.$str) }}"
										>
									</div>
								</div>
							</div>
						</div>

						<div class="col-lg-4">
							<div class="border rounded p-2 h-100">
								<div class="d-flex justify-content-between align-items-center mb-2">
									<h6 class="text-muted mb-0">{{ translate('Outer Case/Shipper/Carton') }}</h6>
								</div>
								<div class="form-row">
									<div class="col-6 mb-3">
										<label class="form-label mb-1">{{ translate('Total Qty of Outer Case/Shipper/Carton') }}</label>
										<input
											type="number"
											name="total_qty_per_case_{{ $str }}"
											class="form-control"
											step="1"
											min="0"
											placeholder="{{ translate('Total units per case') }}"
											value="{{ request('total_qty_per_case_'.$str) }}"
										>
									</div>
									<div class="col-6 mb-3">
										<label class="form-label mb-1">{{ translate('Total Weight Of Outer Case/Shipper/Carton') }}</label>
										<input
											type="number"
											name="weight_case_{{ $str }}"
											class="form-control"
											step="0.001"
											min="0"
											placeholder="{{ translate('Weight per case') }}"
											value="{{ request('weight_case_'.$str) }}"
										>
									</div>
									<div class="col-4 mb-3">
										<label class="form-label mb-1">{{ translate('Case Length (cm)') }}</label>
										<input
											type="number"
											name="case_length_{{ $str }}"
											class="form-control"
											step="0.01"
											min="0"
											placeholder="{{ translate('Length') }}"
											value="{{ request('case_length_'.$str) }}"
										>
									</div>
									<div class="col-4 mb-3">
										<label class="form-label mb-1">{{ translate('Case Width (cm)') }}</label>
										<input
											type="number"
											name="case_width_{{ $str }}"
											class="form-control"
											step="0.01"
											min="0"
											placeholder="{{ translate('Width') }}"
											value="{{ request('case_width_'.$str) }}"
										>
									</div>
									<div class="col-4 mb-3">
										<label class="form-label mb-1">{{ translate('Case Height (cm)') }}</label>
										<input
											type="number"
											name="case_height_{{ $str }}"
											class="form-control"
											step="0.01"
											min="0"
											placeholder="{{ translate('Height') }}"
											value="{{ request('case_height_'.$str) }}"
										>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="row gutters-10">

					</div>

					<!-- Batches section (bottom, with Role Base Price per batch) -->

					<div class="row gutters-10 mt-4">
						<div class="col-12">
							<div class="border rounded p-3 bg-light">
								<div class="d-flex justify-content-between align-items-center mb-3">
									<h6 class="text-muted mb-0">{{ translate('Batches') }}</h6>
									<button type="button" class="btn btn-sm btn-soft-primary" onclick="addBatchRow('{{ $variantKey }}')">
										<i class="las la-plus"></i> {{ translate('Add Batch') }}
									</button>
								</div>
								<div class="table-responsive">
									<table class="table batch-table mb-0">
										<thead>
											<tr>
												<th style="width: 12%;">{{ translate('Batch Code') }}</th>
												<th style="width: 9%;">{{ translate('Mfg Month') }}</th>
												<th style="width: 9%;">{{ translate('Expiry Month') }}</th>
												<th style="width: 8%;">{{ translate('MRP Price') }}</th>
												<th style="width: 8%;">{{ translate('Stock Qty') }}</th>
												<th style="width: 7%;">{{ translate('Offer Active') }}</th>
												<th style="width: 8%;">{{ translate('Discount Type') }}</th>
												<th style="width: 8%;">{{ translate('Discount') }}</th>
												<th style="width: 9%;">{{ translate('Offer Start') }}</th>
												<th style="width: 9%;">{{ translate('Offer End') }}</th>
												<th style="width: 12%;">{{ translate('COA Document') }}</th>
												<th style="width: 8%;">{{ translate('Role Base Price') }}</th>
												<th style="width: 3%;" class="text-center">{{ translate('Action') }}</th>
											</tr>
										</thead>
										<tbody id="batch-rows-{{ $variantKey }}">
											@php
												$defaultDiscountActive = (int) data_get(request()->input('batches', []), $variantKey.'.0.discount_active', 0) === 1;
											@endphp
											<tr class="batch-row">
												<td>
													<input type="text" name="batches[{{ $variantKey }}][0][batch]" class="form-control form-control-sm" placeholder="{{ translate('Batch code') }}" required>
												</td>
												<td>
													<input type="month" name="batches[{{ $variantKey }}][0][manufacturing_date]" class="form-control form-control-sm">
												</td>
												<td>
													<input type="month" name="batches[{{ $variantKey }}][0][product_exp_date]" class="form-control form-control-sm">
												</td>
												<td>
													<input type="number" lang="en" name="batches[{{ $variantKey }}][0][mrp_price]" value="{{ $unit_price }}" min="0" step="0.01" class="form-control form-control-sm" required>
												</td>
												<td>
													<input type="number" lang="en" name="batches[{{ $variantKey }}][0][qty]" value="10" min="0" step="1" class="form-control form-control-sm" required>
												</td>
												<td class="text-center">
													<input type="hidden" name="batches[{{ $variantKey }}][0][discount_active]" value="0">
													<input
														type="checkbox"
														name="batches[{{ $variantKey }}][0][discount_active]"
														value="1"
														class="batch-discount-active"
														onchange="toggleBatchDiscountFields(this)"
														{{ $defaultDiscountActive ? 'checked' : '' }}
													>
												</td>
												<td>
													<select
														name="batches[{{ $variantKey }}][0][discount_type]"
														class="form-control form-control-sm batch-discount-type"
														{{ $defaultDiscountActive ? '' : 'disabled' }}
														{{ $defaultDiscountActive ? 'required' : '' }}
													>
														<option value="">{{ translate('Select') }}</option>
														<option value="percent" {{ data_get(request()->input('batches', []), $variantKey.'.0.discount_type') === 'percent' ? 'selected' : '' }}>{{ translate('Percent') }}</option>
														<option value="flat" {{ data_get(request()->input('batches', []), $variantKey.'.0.discount_type') === 'flat' ? 'selected' : '' }}>{{ translate('Flat') }}</option>
													</select>
												</td>
												<td>
													<input
														type="number"
														lang="en"
														name="batches[{{ $variantKey }}][0][discount]"
														value="{{ data_get(request()->input('batches', []), $variantKey.'.0.discount', '') }}"
														min="0"
														step="0.01"
														class="form-control form-control-sm batch-discount-value"
														{{ $defaultDiscountActive ? '' : 'disabled' }}
														{{ $defaultDiscountActive ? 'required' : '' }}
													>
												</td>
												<td>
													<input
														type="date"
														name="batches[{{ $variantKey }}][0][discount_start_date]"
														value="{{ data_get(request()->input('batches', []), $variantKey.'.0.discount_start_date', '') }}"
														class="form-control form-control-sm batch-discount-start"
														{{ $defaultDiscountActive ? '' : 'disabled' }}
													>
												</td>
												<td>
													<input
														type="date"
														name="batches[{{ $variantKey }}][0][discount_end_date]"
														value="{{ data_get(request()->input('batches', []), $variantKey.'.0.discount_end_date', '') }}"
														class="form-control form-control-sm batch-discount-end"
														{{ $defaultDiscountActive ? '' : 'disabled' }}
													>
												</td>
												<td class="coa-uploader-cell">
													<div class="coa-uploader-wrapper" id="coa-wrapper-{{ $variantKey }}-0">
														<div class="input-group" data-toggle="aizuploader" data-type="document">
															<div class="input-group-prepend">
																<div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
															</div>
															<div class="form-control file-amount text-truncate">{{ translate('Choose PDF') }}</div>
															<input type="hidden" name="batches[{{ $variantKey }}][0][coa]" class="selected-files">
														</div>
														<div class="file-preview box sm"></div>
													</div>
												</td>
												<td>
													<input type="hidden" name="batches[{{ $variantKey }}][0][role_price]" class="batch-role-price-input" value="">
													<small class="text-muted">{{ translate('Auto from MRP') }}</small>
												</td>
												<td class="text-center">
													<button type="button" class="btn btn-xs btn-soft-danger" onclick="removeBatchRow(this)" title="{{ translate('Remove') }}">
														<i class="las la-trash"></i>
													</button>
												</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
					
				</div>
			</div>
		</div>
		@endif
	@endforeach
</div>
@endif

<script type="text/javascript">
	function addBatchRow(variantKey) {
		var $tbody = $('#batch-rows-' + variantKey);
		if ($tbody.length === 0) {
			return;
		}

		var index = $tbody.find('tr.batch-row').length;
		var wrapperId = 'coa-wrapper-' + variantKey + '-' + index;
		var rowHtml = `
			<tr class="batch-row">
				<td>
					<input type="text" name="batches[` + variantKey + `][` + index + `][batch]" class="form-control form-control-sm" placeholder="{{ translate('Batch code') }}" required>
				</td>
				<td>
					<input type="month" name="batches[` + variantKey + `][` + index + `][manufacturing_date]" class="form-control form-control-sm">
				</td>
				<td>
					<input type="month" name="batches[` + variantKey + `][` + index + `][product_exp_date]" class="form-control form-control-sm">
				</td>
				<td>
					<input type="number" lang="en" name="batches[` + variantKey + `][` + index + `][mrp_price]" min="0" step="0.01" class="form-control form-control-sm" required>
				</td>
				<td>
					<input type="number" lang="en" name="batches[` + variantKey + `][` + index + `][qty]" min="0" step="1" class="form-control form-control-sm" required>
				</td>
				<td class="text-center">
					<input type="hidden" name="batches[` + variantKey + `][` + index + `][discount_active]" value="0">
					<input type="checkbox" name="batches[` + variantKey + `][` + index + `][discount_active]" value="1" class="batch-discount-active" onchange="toggleBatchDiscountFields(this)">
				</td>
				<td>
					<select name="batches[` + variantKey + `][` + index + `][discount_type]" class="form-control form-control-sm batch-discount-type" disabled>
						<option value="">{{ translate('Select') }}</option>
						<option value="percent">{{ translate('Percent') }}</option>
						<option value="flat">{{ translate('Flat') }}</option>
					</select>
				</td>
				<td>
					<input type="number" lang="en" name="batches[` + variantKey + `][` + index + `][discount]" min="0" step="0.01" class="form-control form-control-sm batch-discount-value" disabled>
				</td>
				<td>
					<input type="date" name="batches[` + variantKey + `][` + index + `][discount_start_date]" class="form-control form-control-sm batch-discount-start" disabled>
				</td>
				<td>
					<input type="date" name="batches[` + variantKey + `][` + index + `][discount_end_date]" class="form-control form-control-sm batch-discount-end" disabled>
				</td>
				<td class="coa-uploader-cell">
					<div class="coa-uploader-wrapper" id="` + wrapperId + `">
						<div class="input-group" data-toggle="aizuploader" data-type="document">
							<div class="input-group-prepend">
								<div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
							</div>
							<div class="form-control file-amount text-truncate">{{ translate('Choose PDF') }}</div>
							<input type="hidden" name="batches[` + variantKey + `][` + index + `][coa]" class="selected-files">
						</div>
						<div class="file-preview box sm"></div>
					</div>
				</td>
				<td>
					<input type="hidden" name="batches[` + variantKey + `][` + index + `][role_price]" class="batch-role-price-input" value="">
					<small class="text-muted">{{ translate('Auto from MRP') }}</small>
				</td>
				<td class="text-center">
					<button type="button" class="btn btn-xs btn-soft-danger" onclick="removeBatchRow(this)" title="{{ translate('Remove') }}">
						<i class="las la-trash"></i>
					</button>
				</td>
			</tr>
		`;

		$tbody.append(rowHtml);
		toggleBatchDiscountFields($tbody.find('tr.batch-row:last .batch-discount-active')[0]);
		
		// Initialize aizuploader for the new row
		if (typeof AIZ !== 'undefined' && AIZ.uploader) {
			setTimeout(function() {
				AIZ.uploader.previewGenerate();
			}, 100);
		}
	}

	function removeBatchRow(el) {
		var $row = $(el).closest('tr.batch-row');
		var $tbody = $row.parent();
		
		// Check if this is the last row
		if ($tbody.find('tr.batch-row').length <= 1) {
			alert('{{ translate("At least one batch is required") }}');
			return;
		}
		
		// Extract variantKey from tbody ID
		var tbodyId = $tbody.attr('id');
		var variantKey = tbodyId.replace('batch-rows-', '');
		
		$row.remove();

		// Reindex remaining rows to keep names compact
		$tbody.find('tr.batch-row').each(function (i, tr) {
			var $tr = $(tr);
			$tr.find('input, select').each(function () {
				var name = $(this).attr('name');
				if (!name) return;
				name = name.replace(/\[\d+\]/, '[' + i + ']');
				$(this).attr('name', name);
			});
			
			// Update wrapper ID
			var $wrapper = $tr.find('.coa-uploader-wrapper');
			if ($wrapper.length) {
				var newWrapperId = 'coa-wrapper-' + variantKey + '-' + i;
				$wrapper.attr('id', newWrapperId);
			}
		});
	}

	function toggleBatchDiscountFields(el) {
		var $row = $(el).closest('tr.batch-row');
		var isActive = $(el).is(':checked');

		$row.find('.batch-discount-type')
			.prop('disabled', !isActive)
			.prop('required', isActive);
		$row.find('.batch-discount-value')
			.prop('disabled', !isActive)
			.prop('required', isActive);
		$row.find('.batch-discount-start').prop('disabled', !isActive);
		$row.find('.batch-discount-end').prop('disabled', !isActive);
	}

	$(document).ready(function() {
		$('.batch-discount-active').each(function () {
			toggleBatchDiscountFields(this);
		});
	});
</script>
