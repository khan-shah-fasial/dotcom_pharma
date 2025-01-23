@php
    /*
        Common Troubleshoots
        -- make sure all the data is exact same from request to response
        -- make sure to use key & salt properly in proper enviroment
        -- https://docs.payu.in/docs/generate-hash-merchant-hosted -- docs for hash generation
        -- https://docs.payu.in/docs/test-cards-upi-id-and-wallets -- docs for test payment cards
    */

    // PayU Merchant Details
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
        'surl' => route('payumoney.success'),
        'furl' => route('payumoney.failure'),      
        'udf1' => auth()->user()->id,
        'udf2' => 'cart_payment',
        'udf3' => Session::get('combined_order_id'),
        'udf4' => 'none', 
        'udf5' => Session::get('payment_data')['payment_method'], 
    ];

    //sha512(key|txnid|amount|productinfo|firstname|email|||||||||||SALT) -- this is hash making mechanism during request by docs 
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
    <input type="hidden" name="udf1" value="{{ $pum['udf1'] }}" />
    <input type="hidden" name="udf2" value="{{ $pum['udf2'] }}" />
    <input type="hidden" name="udf3" value="{{ $pum['udf3'] }}" />
    <input type="hidden" name="udf4" value="{{ $pum['udf4'] }}" />
    <input type="hidden" name="udf5" value="{{ $pum['udf5'] }}" />
    <button type="submit" style="display: none;">Pay Now</button>
</form>

<script>
    // Automatically submit the form on page load
    window.onload = function() {
        document.getElementById('payumoney-form').submit();
    };
</script>