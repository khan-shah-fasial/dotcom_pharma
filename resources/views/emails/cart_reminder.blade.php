<!DOCTYPE html>
<html>

<head>
  <title>Cart Reminder</title>
</head>

<body style="background-color: #ffffff; color: #000000; font-family: Arial, sans-serif;">
  <img style="width:250px;margin-left:auto;margin-right:auto;display:block;padding-bottom: 20px;"
    src="https://dotcompharma.webtesting.pw/public/assets/img/logo.png">
  <div style="width: 600px;margin-left:auto;margin-right:auto;display:block;background: #f5f5f5;padding: 20px 30px;">
    <p style="text-align:center;padding-bottom: 16px;">Dear {{ $user->name }},</p>
    <img style="width:55px; margin-left:auto; margin-right:auto; display:block;"
      src="https://dotcompharma.webtesting.pw/public/assets/img/cart_reminder2.png">
    <p style="font-size: 30px; text-align: center; padding-bottom: 0px; margin: 0px; color: #000000; font-weight: 600; padding-top: 16px;">Still in your cart</p>
    <p style="text-align: center; font-size: 16px; margin: 0; padding-top: 6px; padding-bottom: 22px;">You have items in your cart that are still waiting for you!</p>
    <h3 style="font-size: 24px; text-align: center; margin: 0;">Here are your items</h3>
    <div style="padding: 1.5rem;">
      <table class="padding text-center small" style="width: 100%; border-collapse: collapse; border: 1px solid #333;">
        <thead>
          <tr style="background: #e0e0e0; text-align: center; color: #000;">
            <th width="35%" style="border: 1px solid #333; padding: 8px;">{{ translate('Product Name') }}</th>
            <th width="10%" style="border: 1px solid #333; padding: 8px;">{{ translate('Qty') }}</th>
            <th width="15%" style="border: 1px solid #333; padding: 8px;">{{ translate('Unit Price') }}</th>
          </tr>
        </thead>
        <tbody style="font-weight: bold; color: #000;">
          @foreach ($user->carts as $key => $cartItem)
            @if ($cartItem->product != null)
              <tr>
                <td style="border: 1px solid #333; padding: 8px; text-align: center;">
                  {{ $cartItem->product->getTranslation('name') }}
                  @if($cartItem->variation != null) ({{ $cartItem->variation }}) @endif
                </td>
                <td style="border: 1px solid #333; padding: 8px; text-align: center;">{{ $cartItem->quantity }}</td>
                <td style="border: 1px solid #333; padding: 8px; text-align: center;">{{ single_price($cartItem->price/$cartItem->quantity) }}</td>
              </tr>
            @endif
          @endforeach
        </tbody>
      </table>
    </div>

    <div style="text-align: center;">
      <a href="{{ route('cart') }}?source=gmail" style="text-align:center;background: #000000;color: #ffffff;padding: 7px 16px;border-radius: 43px;display: inline-block;margin-top: 20px;cursor: pointer; font-size:12px; text-decoration:none; font-weight:600;">Complete Your Order</a>
    </div>
    <p style="font-size: 24px; text-align:center; padding-top:20px;">Thank you!</p>
  </div>
</body>

</html>
