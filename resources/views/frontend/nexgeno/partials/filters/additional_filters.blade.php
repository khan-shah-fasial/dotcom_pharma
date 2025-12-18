@php
  $isMobile = $is_mobile ?? false;

  $selectedDrugName      = $drug_name ?? '';
  $selectedBrandId       = $filter_brand_id ?? null;
  $selectedRoleLabel     = $filter_role_label ?? '';
  $selectedProductOrigin = $filter_product_origin ?? '';

  $brands  = $brands ?? collect();
  $roles   = $roles ?? [];
  $origins = $origins ?? [];

@endphp

<div class="background-none-filter light_bg_gray mb-0">

  <div class="p-3">
    <div class="mb-3">
      <label class="fs-12 fw-600 mb-1">{{ translate('Drug Name') }}</label>
      <input
        type="text"
        class="form-control form-control-sm js-filter-drug"
        placeholder="{{ translate('Search drug or product name') }}"
        value="{{ $selectedDrugName }}"
      >
    </div>

    <div class="mb-3">
      <label class="fs-12 fw-600 mb-1">{{ translate('Brand Name') }}</label>
      <select class="form-control form-control-sm js-filter-brand">
        <option value="">{{ translate('All') }}</option>
        @foreach($brands as $b)
          <option value="{{ $b->id }}" @selected((string)$selectedBrandId === (string)$b->id)>{{ $b->getTranslation('name') }}</option>
        @endforeach
      </select>
    </div>

    <div class="mb-3">
      <label class="fs-12 fw-600 mb-1">{{ translate('Drug Role') }}</label>
      <select class="form-control form-control-sm js-filter-role">
        <option value="">{{ translate('All') }}</option>
        @foreach($roles as $role)
          @php $role = (string)$role; @endphp
          @if($role !== '')
            <option value="{{ $role }}" @selected((string)$selectedRoleLabel === $role)>{{ $role }}</option>
          @endif
        @endforeach
      </select>
    </div>

    <div class="mb-1">
      <label class="fs-12 fw-600 mb-1">{{ translate('Country Of Origin') }}</label>
      <select class="form-control form-control-sm js-filter-origin">
        <option value="">{{ translate('All') }}</option>
        @foreach($origins as $origin)
          @php $origin = (string)$origin; @endphp
          @if($origin !== '')
            <option value="{{ $origin }}" @selected((string)$selectedProductOrigin === $origin)>{{ $origin }}</option>
          @endif
        @endforeach
      </select>
    </div>
  </div>
</div>
