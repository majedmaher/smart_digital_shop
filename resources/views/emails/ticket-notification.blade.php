<!DOCTYPE html>
<html dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}" lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8" />
    <title>{{ __('messages.ticket_reply_notification') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet" />

    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif !important;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #0a0a0a;
            color: #ffffff;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        table {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
            width: 100%;
        }

        img {
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
        }

        .email-wrapper {
            width: 100%;
            background-color: #0a0a0a;
            padding: 40px 0;
        }

        .container {
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            background-color: #111111;
            border-radius: 16px;
            overflow: hidden;
            box-shadow:
                0 25px 50px -12px rgba(255, 255, 255, 0.05),
                0 0 0 1px rgba(255, 255, 255, 0.05);
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 25%, #1e40af 50%, #1e3a8a 75%, #1e1b4b 100%);
            background-size: 400% 400%;
            animation: gradientShift 8s ease infinite;
            padding: 48px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 20%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 70% 80%, rgba(147, 51, 234, 0.15) 0%, transparent 50%);
            pointer-events: none;
        }

        @keyframes gradientShift {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        .logo-container {
            position: relative;
            z-index: 2;
        }

        .logo {
            max-width: 200px;
            height: auto;
            display: block;
            margin: 0 auto;
            filter: brightness(1.1) contrast(1.1);
        }

        .header-title {
            margin-top: 24px;
            font-size: 18px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* Content */
        .content {
            padding: 56px 40px;
            background-color: #111111;

            text-align: {
                    {
                    app()->getLocale()=='ar' ? 'right': 'left'
                }
            }

            ;
        }

        .content-title {
            margin: 0 0 32px 0;
            font-size: 32px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.2;
            letter-spacing: -0.5px;
        }

        .greeting {
            font-size: 18px;
            font-weight: 500;
            color: #e5e7eb;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .description {
            font-size: 16px;
            line-height: 1.7;
            color: #d1d5db;
            margin-bottom: 32px;
        }

        /* Ticket Info Card */
        .ticket-card {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 24px;
            margin: 32px 0;
        }

        .ticket-subject {
            font-size: 18px;
            font-weight: 600;
            color: #fbbf24;
            margin-bottom: 16px;
        }

        .ticket-message {
            font-size: 15px;
            line-height: 1.7;
            color: #d1d5db;
            background: rgba(0, 0, 0, 0.3);
            padding: 16px;
            border-radius: 10px;
            border-left: 3px solid #3b82f6;
            margin-top: 16px;
        }

        /* Action Button */
        .cta-button {
            display: inline-block;
            margin: 32px 0;
            padding: 14px 32px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: #ffffff;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 16px rgba(59, 130, 246, 0.3);
            transition: all 0.3s ease;
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 48px 40px;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .company-name {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 24px;
            letter-spacing: -0.5px;
        }

        .contact-grid {
            display: grid;
            gap: 16px;
            margin-bottom: 32px;
            text-align: center;
        }

        .contact-item {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.5;
        }

        .contact-label {
            font-weight: 600;
            color: #e2e8f0;
            margin-right: 8px;
        }

        .contact-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .contact-link:hover {
            color: #60a5fa;
        }

        .divider {
            margin: 0 12px;
            color: #475569;
        }

        .copyright {
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 12px;
            color: #64748b;
            font-weight: 400;
        }

        /* Responsive */
        @media only screen and (max-width: 640px) {
            .container {
                margin: 0 16px;
                border-radius: 12px;
            }

            .header,
            .content,
            .footer {
                padding: 32px 24px;
            }

            .content-title {
                font-size: 28px;
            }

            .ticket-card {
                padding: 20px;
            }

            .ticket-message {
                font-size: 14px;
            }
        }

        @media only screen and (max-width: 480px) {
            .content-title {
                font-size: 24px;
            }

            .greeting,
            .description {
                font-size: 16px;
            }
        }

        @media (prefers-color-scheme: dark) {

            .container,
            .content {
                background-color: #111111 !important;
            }
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <table role="presentation" cellpadding="0" cellspacing="0">
            <tr>
                <td align="center">
                    <table role="presentation" class="container" cellpadding="0" cellspacing="0">
                        <!-- Header -->
                        <tr>
                            <td class="header">
                                <div class="logo-container">
                                    <img src="{{ asset('enjoy-logo.PNG') }}" alt="Enjoy Games" class="logo"
                                        width="200" />
                                    <div class="header-title">Gaming Excellence</div>
                                </div>
                            </td>
                        </tr>

                        <!-- Main Content -->
                        <tr>
                            <td class="content">
                                <h1 class="content-title">📬 {{ __('messages.ticket_reply_notification') }}</h1>

                                <p class="greeting">{{ __('messages.hello') }},</p>

                                <p class="description">{{ __('messages.new_reply_on_ticket') }}</p>

                                <!-- Ticket Info -->
                                <div class="ticket-card">
                                    <div class="ticket-subject">
                                        "{{ $ticket->subject }}"
                                    </div>
                                    <p class="description">
                                        {{ __('messages.ticket_reply_received') }}
                                    </p>
                                    <div class="ticket-message">
                                        {{ $reply_message }}
                                    </div>
                                </div>

                                <!-- CTA Button -->
                                <div style="text-align: center;">
                                    <a href="https://enjoy-games.vercel.app/tickets/{{ $ticket->id }}" target="_blank"
                                        class="cta-button">
                                        {{ __('messages.view_ticket') }}
                                    </a>
                                </div>

                                <p class="description" style="margin-top: 24px;">
                                    {{ __('messages.thank_you_contacting_us') }}
                                </p>
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td class="footer">
                                <div class="company-name">Enjoy Games</div>

                                <div class="contact-grid">
                                    <div class="contact-item">
                                        <span class="contact-label">{{ __('messages.email') }}:</span>
                                        <a href="mailto:Info@enjoygames.shop"
                                            class="contact-link">Info@enjoygames.shop</a>
                                        <span class="divider">|</span>
                                        <a href="mailto:Support@chargerspeed.online"
                                            class="contact-link">Support@chargerspeed.online</a>
                                    </div>
                                    <div class="contact-item">
                                        <span class="contact-label">{{ __('messages.phone') }}:</span>
                                        <a href="tel:+96893809093" class="contact-link">+968 9380 9093</a>
                                    </div>
                                    <div class="contact-item">
                                        <span class="contact-label">{{ __('messages.website') }}:</span>
                                        <a href="https://enjoy-games.vercel.app" target="_blank"
                                            class="contact-link">enjoy-games.vercel.app</a>
                                    </div>
                                    <div class="contact-item">
                                        <span class="contact-label">{{ __('messages.address') }}</span>
                                    </div>
                                </div>

                                <div class="copyright">
                                    &copy; {{ date('Y') }} Enjoy Games. {{ __('messages.rights_reserved') }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>