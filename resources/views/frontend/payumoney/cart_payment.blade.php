@php
    // PayU Merchant Details
    //https://docs.payu.in/docs/generate-hash-merchant-hosted
    //https://docs.payu.in/docs/test-cards-upi-id-and-wallets
    $key = env("PAYUMONEY_KEY");
    $salt = env("PAYUMONEY_SALT");

    // Transaction Details
    $pum = [
        'key' => $key,
        'salt' => $salt,
        'txnid' => strtoupper(substr(md5(uniqid(rand(), true)), 0, 20)), // Unique Transaction ID
        'amount' => number_format(\App\Models\CombinedOrder::findOrFail(Session::get('combined_order_id'))->grand_total, 2, '.', ''),
        'productinfo' => "Order-" . rand(10000, 99999),
        'firstname' => auth()->user()->name,
        'email' => auth()->user()->email,
        'phone' => auth()->user()->phone,
        'surl' => route('payumoney.success', ['coid' => Session::get('combined_order_id')]),
        'furl' => route('payumoney.failure', ['coid' => Session::get('combined_order_id')]),      
        'udf1' => auth()->user()->id, // Dynamic user ID
        'udf2' => 'cart_payment', // Static data
        'udf3' => Session::get('combined_order_id'),
        'udf4' => 'udf4', 
        'udf5' => 'udf5', 
        //'udf6' => 'udf6', 
        //'udf7' => 'udf7', 
        //'udf8' => 'udf8', 
        //'udf9' => 'udf9', 
        //'udf10' => 'udf10',
    ];

    //sha512(key|txnid|amount|productinfo|firstname|email|||||||||||SALT)
    //$hashString = $pum['key'] . '|' . $pum['txnid'] . '|' . $pum['amount'] . '|' . $pum['productinfo'] . '|' . $pum['firstname'] . '|' . $pum['email'] . '|' . $pum['udf1'] . '|' . $pum['udf2'] . '|' . $pum['udf3'] . '|' . $pum['udf4'] . '|' . $pum['udf5'] . '|' . $pum['udf6'] . '|' . $pum['udf7'] . '|' . $pum['udf8'] . '|' . $pum['udf9'] . '|' . $pum['udf10'] . '|' . $pum['salt'];
    $hashString = $pum['key'] . '|' . $pum['txnid'] . '|' . $pum['amount'] . '|' . $pum['productinfo'] . '|' . $pum['firstname'] . '|' . $pum['email'] . '|' . $pum['udf1'] . '|' . $pum['udf2'] . '|' . $pum['udf3'] . '|' . $pum['udf4'] . '|' . $pum['udf5'] . '||||||' . $pum['salt'];
    $pum['hash'] = strtolower(hash('sha512', $hashString));

    $payumoneyUrl = (get_setting('payumoney_sandbox') == 1) ? 'https://test.payu.in/_payment' : 'https://secure.payu.in/_payment';

@endphp

<form id="payumoney-form" action="{{$payumoneyUrl}}" method="POST">
    @csrf
    <input type="hidden" name="key" value="{{ $pum['key'] }}" />
    <input type="hidden" name="txnid" value="{{ $pum['txnid'] }}" />
    <input type="hidden" name="amount" value="{{ $pum['amount'] }}" />
    <input type="hidden" name="productinfo" value="{{ $pum['productinfo'] }}" />
    <input type="hidden" name="firstname" value="{{ $pum['firstname'] }}" />
    <input type="hidden" name="email" value="{{ $pum['email'] }}" />
    <input type="hidden" name="phone" value="{{ $pum['phone'] }}" />
    <input type="hidden" name="surl" value="{{ $pum['surl'] }}" />
    <input type="hidden" name="furl" value="{{ $pum['furl'] }}" />
    <input type="hidden" name="hash" value="{{ $pum['hash'] }}" />

    <!-- Additional Parameters (udf1 to udf10) -->
    <input type="hidden" name="udf1" value="{{ $pum['udf1'] }}" />
    <input type="hidden" name="udf2" value="{{ $pum['udf2'] }}" />
    <input type="hidden" name="udf3" value="{{ $pum['udf3'] }}" />
    <input type="hidden" name="udf4" value="{{ $pum['udf4'] }}" />
    <input type="hidden" name="udf5" value="{{ $pum['udf5'] }}" />
    {{--<input type="hidden" name="udf6" value="{{ $pum['udf6'] }}" />
    <input type="hidden" name="udf7" value="{{ $pum['udf7'] }}" />
    <input type="hidden" name="udf8" value="{{ $pum['udf8'] }}" />
    <input type="hidden" name="udf9" value="{{ $pum['udf9'] }}" />
    <input type="hidden" name="udf10" value="{{ $pum['udf10'] }}" />--}}

    <button type="submit" style="display: none;">Pay Now</button>
</form>

<script>
    // Automatically submit the form on page load
    window.onload = function() {
        //document.getElementById('payumoney-form').submit();
    };
</script>
