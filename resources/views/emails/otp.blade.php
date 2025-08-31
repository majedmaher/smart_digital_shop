<!DOCTYPE html>
<html dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}" lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8" />
    <title>{{ __('messages.verification_code') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- Inter Font (Professional Typography) -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet" />

    <style>
        /* Reset and base styles */
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

        /* Main wrapper */
        .email-wrapper {
            width: 100%;
            background-color: #0a0a0a;
            padding: 40px 0;
        }

        /* Container */
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

        /* Header section */
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

        /* Content section */
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
            margin-bottom: 40px;
        }

        /* Premium OTP Section */
        .otp-section {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 48px 32px;
            margin: 48px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .otp-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4, #3b82f6);
            background-size: 200% 100%;
            animation: shimmer 3s linear infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        .otp-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .otp-code {
            display: inline-block;
            font-size: 36px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 12px;
            padding: 24px 40px;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            border: 2px solid #3b82f6;
            border-radius: 12px;
            box-shadow:
                0 0 0 1px rgba(59, 130, 246, 0.2),
                0 8px 32px rgba(59, 130, 246, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', 'Courier New', monospace !important;
            transition: all 0.3s ease;
        }

        .otp-expiry {
            margin-top: 24px;
            font-size: 14px;
            color: #9ca3af;
            font-weight: 500;
        }

        .otp-timer {
            display: inline-block;
            padding: 6px 12px;
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 6px;
            color: #fca5a5;
            font-weight: 600;
            margin-left: 8px;
        }

        /* Instructions Card */
        .instructions-card {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 32px;
            margin: 40px 0;
        }

        .instructions-title {
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }

        .instructions-icon {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .instructions-text {
            font-size: 15px;
            line-height: 1.6;
            color: #cbd5e1;
            margin: 0;
        }

        /* Security Notice */
        .security-notice {
            background: linear-gradient(135deg, #451a03 0%, #7c2d12 100%);
            border: 1px solid rgba(251, 146, 60, 0.3);
            border-radius: 12px;
            padding: 24px;
            margin: 32px 0;
            display: flex;
            align-items: flex-start;
        }

        .security-icon {
            width: 24px;
            height: 24px;
            margin-right: 16px;
            margin-top: 2px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .security-text {
            font-size: 14px;
            line-height: 1.6;
            color: #fed7aa;
            margin: 0;
            font-weight: 500;
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
            grid-template-columns: 1fr;
            gap: 16px;
            margin-bottom: 32px;
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

        /* Responsive Design */
        @media only screen and (max-width: 640px) {
            .email-wrapper {
                padding: 20px 0;
            }

            .container {
                margin: 0 16px;
                border-radius: 12px;
            }

            .header {
                padding: 32px 24px;
            }

            .content {
                padding: 40px 24px;
            }

            .content-title {
                font-size: 28px;
            }

            .otp-section {
                padding: 32px 20px;
                margin: 32px 0;
            }

            .otp-code {
                font-size: 28px;
                letter-spacing: 8px;
                padding: 20px 28px;
            }

            .instructions-card {
                padding: 24px;
            }

            .footer {
                padding: 32px 24px;
            }

            .company-name {
                font-size: 20px;
            }
        }

        @media only screen and (max-width: 480px) {
            .otp-code {
                font-size: 24px;
                letter-spacing: 6px;
                padding: 16px 24px;
            }

            .content-title {
                font-size: 24px;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .container {
                background-color: #111111 !important;
            }

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
                                    <img src="{{asset('enjoy-logo.PNG')}}" alt="Enjoy Games" class="logo" width="200" />
                                    <div class="header-title">Gaming Excellence</div>
                                </div>
                            </td>
                        </tr>

                        <!-- Main Content -->
                        <tr>
                            <td class="content">
                                <h1 class="content-title">{{ __('messages.verification_code') }}</h1>

                                <p class="greeting">{{ __('messages.hello') }}</p>

                                <p class="description">{{ __('messages.verification_code_is') }}</p>

                                <!-- OTP Section -->
                                <div class="otp-section">
                                    <span class="otp-label">{{ __('messages.verification_code') }}</span>
                                    <div class="otp-code">{{ $code }}</div>
                                    <p class="otp-expiry">
                                        {{ __('messages.code_expires_in') }}
                                        <span class="otp-timer">10 {{ __('messages.minutes') }}</span>
                                    </p>
                                </div>

                                <!-- Instructions -->
                                <div class="instructions-card">
                                    <h3 class="instructions-title">
                                        <span class="instructions-icon">📋</span>
                                        {{ __('messages.how_to_use') }}
                                    </h3>
                                    <p class="instructions-text">{{ __('messages.verification_message') }}</p>
                                </div>

                                <!-- Security Notice -->
                                <div class="security-notice">
                                    <div class="security-icon">⚠️</div>
                                    <p class="security-text">{{ __('messages.security_notice') }}</p>
                                </div>
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