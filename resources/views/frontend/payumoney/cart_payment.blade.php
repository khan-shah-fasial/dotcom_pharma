
@php
    // PayU Merchant Details
    $key = env("PAYUMONEY_SALT");
    $salt = env("PAYUMONEY_KEY");

    // Transaction Details
    $pum = [
        'key' => $key,
        'salt' => $salt,
        'txnid' => strtoupper(substr(md5(uniqid(rand(), true)), 0, 20)), // Unique Transaction ID
        'amount' => \App\Models\CombinedOrder::findOrFail(Session::get('combined_order_id'))->grand_total,
        'productinfo' => "Order #" . rand(1000, 9999),
        'firstname' => auth()->user()->name,
        'email' => auth()->user()->email,
        'phone' => auth()->user()->phone,
        'surl' => route('payumoney.success', ['coid' => Session::get('combined_order_id')]),
        'furl' => route('payumoney.failure', ['coid' => Session::get('combined_order_id')]),      
        'udf1' => auth()->user()->id, 
        'udf2' => 'cart_payment',
    ];

    // Generate Hash (Include udf1 and udf2 in the hash string)
    $hashString = $pum['key'] . '|' . $pum['txnid'] . '|' . $pum['amount'] . '|' . $pum['productinfo'] . '|' . $pum['firstname'] . '|' . $pum['email'] . '|' . $pum['udf1'] . '|' . $pum['udf2'] . '|||||||||' . $pum['salt'];
    $pum['hash'] = strtolower(hash('sha512', $hashString));
@endphp

<form action="https://secure.payu.in/_payment" method="POST">
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

    <!-- Additional Parameters -->
    <input type="hidden" name="udf1" value="{{ $pum['udf1'] }}" />
    <input type="hidden" name="udf2" value="{{ $pum['udf2'] }}" />

    <button type="submit">Pay Now</button>
</form>