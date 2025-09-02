<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color:#f9f9f9;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f9f9f9; padding:20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td align="center" style="background:#2c3e50; padding:20px;">
                            <h2 style="color:#ffffff; margin:0;">{{ translate('Product Enquiry') }}</h2>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:30px;">
                            <p style="font-size:16px; color:#333; line-height:1.6; margin:0 0 20px;">
                                <strong>{{ translate('Name') }}:</strong> {{ $name }} <br>
                                <strong>{{ translate('Email') }}:</strong> {{ $email }} <br>
                                @if (!empty($phone))
                                    <strong>{{ translate('Phone') }}:</strong> {{ $phone }} <br>
                                @endif
                                <strong>{{ translate('Product Url') }}:</strong> 
                                <a href="{{ $url }}" target="_blank" style="color:#3498db; text-decoration:none;">
                                    {{ $url }}
                                </a>
                            </p>

                            <!-- Product Image -->
                            {{-- <div style="margin:20px 0; text-align:center;">
                                <img src="{{ $product_img }}" alt="Product Image" style="max-width:200px; border-radius:8px; border:1px solid #ddd;">
                            </div> --}}

                            <!-- Message Content -->
                            <div style="margin:20px 0; padding:15px; border:1px solid #eee; border-radius:8px; background:#fafafa;">
                                <h4 style="margin:0 0 10px; font-size:16px; color:#333; font-weight:bold;">
                                    {{ translate('Customer Message') }}
                                </h4>
                                <p style="font-size:15px; color:#555; line-height:1.6; margin:0;">
                                    {!! $content !!}
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background:#f1f1f1; padding:20px; font-size:14px; color:#777;">
                            <p style="margin:0;">
                                <a href="{{ env('APP_URL') }}" style="color:#3498db; text-decoration:none;">
                                    {{ translate('Go to the website') }}
                                </a>
                            </p>
                            <p style="margin-top:10px;">&copy; {{ date('Y') }} {{ env('APP_NAME') }}. All rights reserved.</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>