@extends('backend.layouts.app')

@section('content')

<div class="row">

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Price Settings') }}</h5>
                <p class="text-muted mb-0">
                    <strong>Note:</strong> Define the percentage markup for each user role. The final product price will be increased based on these percentages relative to the base price.
                </p>
            </div>
            <div class="card-body">
                <form class="form-horizontal" action="{{ route('business_settings.update') }}" method="POST">
                    @csrf
                    <div class="form-group row">

                        @php
                            $roles = json_decode(get_setting('get_customer_roles'), true);
                        @endphp

                        @foreach($roles as $key)
                            <div class="col-lg-6 mb-4">
                                <label class="control-label">{{ ucwords($key) }} %</label>
                                <input 
                                    value="{{ get_setting('product-price-percentage-' . $key) }}" 
                                    name="product-price-percentage-{{ $key }}" 
                                    type="text" 
                                    class="form-control"
                                >
                                <input 
                                    type="hidden" 
                                    name="types[]" 
                                    value="product-price-percentage-{{ $key }}"
                                >
                            </div>
                        @endforeach

                        <div class="col-lg-12">
                            <button class="btn btn-sm btn-primary" type="submit">{{ translate('Save') }}</button>
                        </div>
                        
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection