<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.code_delivery_subject') }}</title>


    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@100;200;300;400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
        * {
            font-family: "IBM Plex Sans Arabic", sans-serif !important;
        }

        body {
            font-family: "IBM Plex Sans Arabic", sans-serif;
        }

        .ibm-plex-sans-arabic-thin {
            font-family: "IBM Plex Sans Arabic", sans-serif;
            font-weight: 100;
            font-style: normal;
        }

        .ibm-plex-sans-arabic-extralight {
            font-family: "IBM Plex Sans Arabic", sans-serif;
            font-weight: 200;
            font-style: normal;
        }

        .ibm-plex-sans-arabic-light {
            font-family: "IBM Plex Sans Arabic", sans-serif;
            font-weight: 300;
            font-style: normal;
        }

        .ibm-plex-sans-arabic-regular {
            font-family: "IBM Plex Sans Arabic", sans-serif;
            font-weight: 400;
            font-style: normal;
        }

        .ibm-plex-sans-arabic-medium {
            font-family: "IBM Plex Sans Arabic", sans-serif;
            font-weight: 500;
            font-style: normal;
        }

        .ibm-plex-sans-arabic-semibold {
            font-family: "IBM Plex Sans Arabic", sans-serif;
            font-weight: 600;
            font-style: normal;
        }

        .ibm-plex-sans-arabic-bold {
            font-family: "IBM Plex Sans Arabic", sans-serif;
            font-weight: 700;
            font-style: normal;
        }
    </style>

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
                            <img src="{{ asset('images/logo.png') }}" alt="Logo" style="max-height:50px;">
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td
                            style="padding:30px; text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; color:#333;">
                            <h1 style="color:#7338f0; margin-top:0;">📦 {{ __('messages.code_delivery_subject') }}</h1>

                            <p style="font-size:15px; line-height:1.6; margin:15px 0;">
                                {{ __('messages.hello') }},
                            </p>
                            <p style="font-size:15px; line-height:1.6; margin:15px 0;">
                                {{ __('messages.thank_you_for_your_purchase') }}
                            </p>

                            <p style="font-size:15px; margin:20px 0;">
                                {{ __('messages.code_delivery_line') }}
                                <strong>{{ $product_title }}</strong>
                            </p>

                            <div style="text-align:center; margin:25px 0;">
                                <p style="font-size:22px; font-weight:bold; color:green; margin:0;">
                                    {{ $code }}
                                </p>
                            </div>

                            <p style="font-size:14px; line-height:1.6; color:#555; margin-top:20px;">
                                {{ __('messages.keep_code_safe') }}
                            </p>

                            <p style="margin-top:30px; font-size:13px; color:#555;">{{ __('messages.best_regards') }}
                            </p>
                            <p style="font-size:13px; font-weight:bold; color:#333;">{{__('messages.project_name')}}</p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#090f30; color:#fff; text-align:center; padding:15px; font-size:12px;">
                            <p style="margin:5px 0;">© {{ date('Y') }} {{__('messages.project_name')}}. {{
                                __('messages.rights_reserved') }}</p>
                            <p style="margin:5px 0;">
                                <a href="{{ env('WEBSITE_URL') }}" style="color:#fff; text-decoration:none;">{{
                                    __('messages.our_website') }}</a>
                            </p>
                            <p style="margin:5px 0;">📧 info@enjoygames.com | 📞 +966 123456789</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>