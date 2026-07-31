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
	<div class="align-items-center">
		<h1 class="h3">{{translate('All Brands')}}</h1>
	</div>
</div>

<div class="row">
	<div class="@if(auth()->user()->can('add_brand')) col-lg-8 @else col-lg-12 @endif">
		<div class="card">
		    <div class="card-header row gutters-5">
				<div class="col text-center text-md-left">
					<h5 class="mb-md-0 h6">{{ translate('Brands') }}</h5>
				</div>
				<div class="col-md-4">
					<form class="" id="sort_brands" action="" method="GET">
						<div class="input-group input-group-sm">
							<input type="text" class="form-control" id="search" name="search"@isset($sort_search) value="{{ $sort_search }}" @endisset placeholder="{{ translate('Search brand or company') }}">
						</div>
					</form>
				</div>
		    </div>
		    <div class="card-body">
				<div class="table-responsive">
					<table class="table aiz-table mb-0 brand-list-table">
						<thead>
							<tr>
								<th>{{ translate('Sr.No') }}</th>
								<th>{{ translate('Brand Name') }}</th>
								<th>{{ translate('Company Code') }}</th>
								<th>{{ translate('Company Name') }}</th>
								<th>{{ translate('Company Type') }}</th>
								<th>{{ translate('Deal In Category') }}</th>
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
	@can('add_brand')
		<div class="col-lg-4">
			<div class="card">
				<div class="card-header">
					<h5 class="mb-0 h6">{{ translate('Add New Brand') }}</h5>
				</div>
				<div class="card-body">
					<form action="{{ route('brands.store') }}" method="POST">
						@csrf
						<div class="form-group mb-3">
							<label for="company_id">{{ translate('Company Name') }}</label>
							<select id="company_id" name="company_id" class="form-control aiz-selectpicker"
								data-live-search="true" data-placeholder="{{ translate('Select Company') }}">
								<option value="">{{ translate('Select Company') }}</option>
								@foreach ($companies as $company)
									<option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>
										{{ $company->company_name }}
									</option>
								@endforeach
							</select>
							@error('company_id')
								<span class="text-danger small">{{ $message }}</span>
							@enderror
						</div>
						<div class="form-group mb-3">
							<label for="name">{{translate('Name')}}</label>
							<input type="text" placeholder="{{translate('Name')}}" name="name" class="form-control" required>
						</div>
						<div class="form-group mb-3">
							<label for="name">{{translate('Logo')}} <small>({{ translate('120x80') }})</small></label>
							<div class="input-group" data-toggle="aizuploader" data-type="image">
								<div class="input-group-prepend">
										<div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
								</div>
								<div class="form-control file-amount">{{ translate('Choose File') }}</div>
								<input type="hidden" name="logo" class="selected-files">
							</div>
							<div class="file-preview box sm">
							</div>
                            <small class="text-muted">{{ translate('Minimum dimensions required: 126px width X 100px height.') }}</small>
						</div>
						<div class="form-group mb-3">
							<label for="name">{{translate('Meta Title')}}</label>
							<input type="text" class="form-control" name="meta_title" placeholder="{{translate('Meta Title')}}">
						</div>
						<div class="form-group mb-3">
							<label for="name">{{translate('Meta Description')}}</label>
							<textarea name="meta_description" rows="5" class="form-control"></textarea>
						</div>
						<div class="form-group mb-3 text-right">
							<button type="submit" class="btn btn-primary">{{translate('Save')}}</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	@endcan
</div>

@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection

@section('script')
<script type="text/javascript">
    function sort_brands(el){
        $('#sort_brands').submit();
    }
</script>
@endsection
