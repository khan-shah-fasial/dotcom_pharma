@extends('backend.layouts.app')

@section('content')

@php
	$categoryById = $categories->keyBy('id');
	$categoryPath = function ($category) use ($categoryById) {
		$path = collect();
		$seen = [];

		while ($category && !in_array((int) $category->id, $seen, true)) {
			$seen[] = (int) $category->id;
			$path->prepend($category->getTranslation('name'));
			$category = $category->parent_id ? $categoryById->get($category->parent_id) : null;
		}

		return $path->implode(' > ');
	};
	$filters = $filters ?? [];
	$sortBy = $sortBy ?? 'name';
	$sortDir = $sortDir ?? 'asc';
	$filtersApplied = filled($sort_search) || collect($filters)->contains(function ($value) {
		return $value !== null && $value !== '';
	});
@endphp

<style>
	.brand-list-table {
		min-width: 1200px;
	}
	.brand-category-list {
		min-width: 180px;
		max-width: 320px;
		white-space: normal;
	}
	.brand-category-list .badge {
		white-space: normal;
		text-align: left;
		line-height: 1.4;
	}
</style>

<div class="aiz-titlebar text-left mt-2 mb-3">
	<div class="row align-items-center">
		<div class="col-md-6">
			<h1 class="h3">{{translate('All Brands')}}</h1>
		</div>
		@can('add_brand')
			<div class="col-md-6 text-md-right">
				<a href="{{ route('brands.create') }}" class="btn btn-circle btn-info">
					<span>{{ translate('Add New Brand') }}</span>
				</a>
			</div>
		@endcan
	</div>
</div>

<div class="row">
	<div class="col-lg-12">
		<div class="card">
		    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
				<div class="mb-2">
					<h5 class="mb-md-0 h6">{{ translate('Brands') }}</h5>
					@if ($filtersApplied)
						<span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
					@endif
				</div>
				<div>
					<button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal" data-target="#brandFilterModal">
						{{ translate('Open Filters') }}
					</button>
					<a href="{{ route('brands.index') }}" class="btn btn-danger mb-2">{{ translate('Reset') }}</a>
				</div>
		    </div>
		    <div class="card-body">
				<div class="table-responsive">
					<table class="table aiz-table mb-0 brand-list-table">
						<thead>
							<tr>
								<th>{{ translate('Sr.No') }}</th>
								@include('backend.inc.sortable_th', ['column' => 'name', 'label' => translate('Brand Name'), 'routeName' => 'brands.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
								<th>{{ translate('Company Code') }}</th>
								@include('backend.inc.sortable_th', ['column' => 'company_name', 'label' => translate('Company Name'), 'routeName' => 'brands.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
								@include('backend.inc.sortable_th', ['column' => 'company_type', 'label' => translate('Company Type'), 'routeName' => 'brands.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
								@include('backend.inc.sortable_th', ['column' => 'deals_in', 'label' => translate('Deal In Category'), 'routeName' => 'brands.index', 'sortBy' => $sortBy, 'sortDir' => $sortDir])
								<th>{{ translate('Brand Logo') }}</th>
								<th class="text-right">{{ translate('Options') }}</th>
							</tr>
						</thead>
						<tbody>
							@forelse($brands as $key => $brand)
								@php
									$company = $brand->company;
								@endphp
								<tr>
									<td>{{ $brands->firstItem() + $key }}</td>
									<td>{{ $brand->getTranslation('name') }}</td>
									<td>{{ $company->code ?? '-' }}</td>
									<td>{{ $company->company_name ?? '-' }}</td>
									<td>{{ $company->company_type ?? '-' }}</td>
									<td class="brand-category-list">
										@if ($company)
											@forelse ($company->categories->sortBy(fn ($category) => $categoryPath($category)) as $category)
												<span class="badge badge-inline badge-soft-info mb-1">
													{{ $categoryPath($category) }}
												</span>
											@empty
												-
											@endforelse
										@else
											-
										@endif
									</td>
									<td>
										<img src="{{ uploaded_asset($brand->logo) }}" alt="{{ $brand->getTranslation('name') }}" class="h-50px">
									</td>
									<td class="text-right">
										@can('edit_brand')
											<a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{route('brands.edit', ['id'=>$brand->id, 'lang'=>env('DEFAULT_LANGUAGE')] )}}" title="{{ translate('Edit') }}">
												<i class="las la-edit"></i>
											</a>
										@endcan
										@can('delete_brand')
											<a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{route('brands.destroy', $brand->id)}}" title="{{ translate('Delete') }}">
												<i class="las la-trash"></i>
											</a>
										@endcan
									</td>
								</tr>
							@empty
								<tr>
									<td colspan="8" class="text-center">{{ translate('No brands found.') }}</td>
								</tr>
							@endforelse
						</tbody>
					</table>
				</div>
		        <div class="aiz-pagination">
                	{{ $brands->appends(request()->input())->links() }}
            	</div>
		    </div>
		</div>
	</div>
</div>

@endsection

@section('modal')
    @include('modals.delete_modal')
    <form action="{{ route('brands.index') }}" method="GET">
        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
        <div class="modal fade" id="brandFilterModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ translate('Filter Brands') }}</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>{{ translate('Brand Name') }}</label>
                            <input type="text" class="form-control" name="brand_name" value="{{ $filters['brand_name'] ?? '' }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Company Name') }}</label>
                            <input type="text" class="form-control" name="company_name" value="{{ $filters['company_name'] ?? '' }}">
                        </div>
                        <div class="form-group">
                            <label>{{ translate('Type') }}</label>
                            <input type="text" class="form-control" name="company_type" value="{{ $filters['company_type'] ?? '' }}">
                        </div>
                        <div class="form-group mb-0">
                            <label>{{ translate('Deals In') }}</label>
                            <input type="text" class="form-control" name="deals_in" value="{{ $filters['deals_in'] ?? '' }}">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('brands.index') }}" class="btn btn-danger">{{ translate('Reset') }}</a>
                        <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

