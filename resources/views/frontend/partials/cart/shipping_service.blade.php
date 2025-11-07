@if(isset($shipping_methods) && $shipping_methods->count())
<style>
    .aiz-megabox .aiz-megabox-elem:hover{
        background-color: #2b56a1 !important;
    }
    .aiz-megabox>input:checked~.aiz-megabox-elem, .aiz-megabox>input:checked~.aiz-megabox-elem {
    border-color: #2b56a1 !important;
}
</style>
    <div class="mb-4">
        <h3 class="fs-16 fw-700 text-dark">
            {{ translate('Select a shipping service') }}
        </h3>
        <div class="row gutters-10">
            @foreach($shipping_methods as $method)
                <div class="col-xl-4 col-md-6">
                    <label class="aiz-megabox d-block mb-3">
                        <input
                            type="radio"
                            name="shipping_method_id"
                            value="{{ $method->id }}"
                            {{ $loop->first ? 'checked' : '' }}
                        >
                        <span class="d-flex align-items-center justify-content-between aiz-megabox-elem rounded-0 p-3">
                            <span class="d-block fw-400 fs-14">
                                {{ $method->name }}
                            </span>
                        </span>
                    </label>
                </div>
            @endforeach
        </div>
    </div>
@endif
