<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.payment_confirmed') }}</title>

    <!DOCTYPE html>
    <html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

    <head>
        <meta charset="UTF-8">
        <title>{{ __('messages.payment_confirmed') }}</title>
    </head>

<body style="margin:0; padding:0; background-color:#f7f7f7; font-family:Arial, sans-serif;">
    <table align="center" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f7f7; padding:20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0"
                    style="background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,0.05);">

                    <!-- Header with logo -->
                    <tr>
                        <td align="center" style="background-color:#fff; padding:20px;">
                            <img src="{{ asset('logo.webp') }}" alt="Logo" style="max-height:50px;">
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td
                            style="padding:30px; text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; color:#333;">
                            <h1 style="color:#7338f0; margin-top:0;">✅ {{ __('messages.payment_confirmed') }}</h1>
                            <h2 style="margin:10px 0;">{{ __('messages.hello') }} {{ $user->name }},</h2>
                            <p style="font-size:15px; line-height:1.6; margin:15px 0;">
                                {{ __('messages.thank_you_order') }}
                            </p>

                            <table width="100%" cellpadding="5" cellspacing="0"
                                style="margin:20px 0; font-size:14px; color:#333;">
                                <tr>
                                    <td><strong>{{ __('messages.order_number') }}:</strong></td>
                                    <td>#{{ $order->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('messages.order_date') }}:</strong></td>
                                    <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('messages.product_count') }}:</strong></td>
                                    <td>{{ $order->items->count() }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('messages.total_amount') }}:</strong></td>
                                    <td>{{ number_format($order->total_price_user_currency, 2) .
                                        $order->currency_code}}</td>
                                </tr>
                            </table>

                            <p style="font-size:14px; line-height:1.6; color:#555;">
                                📢 {{ __('messages.order_processing') }}
                            </p>

                            <p style="font-size:14px; line-height:1.6; color:#555;">
                                {{ __('messages.contact_us_if_any') }}
                            </p>

                            <div style="text-align:center; margin:30px 0;">
                                <a href="{{ env('WEBSITE_URL') }}" target="_blank" rel="noopener noreferrer"
                                    style="display:inline-block; padding:12px 25px; background:#7338f0; color:#fff; text-decoration:none; border-radius:6px; font-weight:bold;">
                                    {{ __('messages.visit_website') }}
                                </a>
                            </div>

                            <p style="margin-top:30px; font-size:13px; color:#555;">{{ __('messages.thank_you') }}</p>
                            <p style="font-size:13px; font-weight:bold; color:#333;">{{ __(env('WEBSITE_URL')) }}</p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#090f30; color:#fff; text-align:center; padding:15px; font-size:12px;">
                            <p style="margin:5px 0;">© {{ date('Y') }} {{ __('messages.project_name') }}. {{
                                __('messages.rights_reserved') }}
                            </p>
                            <p style="margin:5px 0;">
                                <a href="{{ env('WEBSITE_URL') }}" style="color:#fff; text-decoration:none;">{{
                                    __('messages.project_name') }}</a>
                            </p>
                            <p style="margin:5px 0;">📧 info@enjoygames.com | 📞 93809093</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
</head>

<body style="margin:0; padding:0; background-color:#f7f7f7; font-family:Arial, sans-serif;">
    <table align="center" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f7f7f7; padding:20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0"
                    style="background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,0.05);">

                    <!-- Header with logo -->
                    <tr>
                        <td align="center" style="background-color:#fff; padding:20px;">
                            <img src="{{ asset('logo.webp') }}" alt="Logo" style="max-height:50px;">
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td
                            style="padding:30px; text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; color:#333;">
                            <h1 style="color:#7338f0; margin-top:0;">✅ {{ __('messages.payment_confirmed') }}</h1>
                            <h2 style="margin:10px 0;">{{ __('messages.hello') }} {{ $user->name }},</h2>
                            <p style="font-size:15px; line-height:1.6; margin:15px 0;">
                                {{ __('messages.thank_you_order') }}
                            </p>

                            <table width="100%" cellpadding="5" cellspacing="0"
                                style="margin:20px 0; font-size:14px; color:#333;">
                                <tr>
                                    <td><strong>{{ __('messages.order_number') }}:</strong></td>
                                    <td>#{{ $order->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('messages.order_date') }}:</strong></td>
                                    <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('messages.product_count') }}:</strong></td>
                                    <td>{{ $order->items->count() }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('messages.total_amount') }}:</strong></td>
                                    <td>{{ number_format($order->total_price_user_currency, 2) .
                                        $order->currency_code}}</td>
                                </tr>
                            </table>

                            <p style="font-size:14px; line-height:1.6; color:#555;">
                                📢 {{ __('messages.order_processing') }}
                            </p>

                            <p style="font-size:14px; line-height:1.6; color:#555;">
                                {{ __('messages.contact_us_if_any') }}
                            </p>

                            <div style="text-align:center; margin:30px 0;">
                                <a href="{{ env('WEBSITE_URL') }}" target="_blank" rel="noopener noreferrer"
                                    style="display:inline-block; padding:12px 25px; background:#7338f0; color:#fff; text-decoration:none; border-radius:6px; font-weight:bold;">
                                    {{ __('messages.visit_website') }}
                                </a>
                            </div>

                            <p style="margin-top:30px; font-size:13px; color:#555;">{{ __('messages.thank_you') }}</p>
                            <p style="font-size:13px; font-weight:bold; color:#333;">{{ __(env('WEBSITE_URL')) }}</p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#090f30; color:#fff; text-align:center; padding:15px; font-size:12px;">
                            <p style="margin:5px 0;">© {{ date('Y') }} {{ __('messages.project_name') }}. {{
                                __('messages.rights_reserved') }}
                            </p>
                            <p style="margin:5px 0;">
                                <a href="{{ env('WEBSITE_URL') }}" style="color:#fff; text-decoration:none;">{{
                                    __('messages.project_name') }}</a>
                            </p>
                            <p style="margin:5px 0;">📧 info@enjoygames.com | 📞 93809093</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>