<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Start Your Story' }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            line-height: 1.6;
        }

        table {
            border-collapse: collapse;
            border-spacing: 0;
        }

        img {
            border: 0;
            outline: none;
            text-decoration: none;
            display: block;
        }

        a {
            text-decoration: none;
        }

        .email-wrapper {
            width: 100%;
            background: #f8fafc;
            padding: 40px 16px;
        }

        .email-container {
            width: 100%;
            max-width: 600px;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05);
        }

        .header {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            text-align: center;
            padding: 32px;
        }

        .logo {
            height: 42px;
            width: auto;
            margin: 0 auto 14px auto;
        }

        .heading {
            margin: 0;
            color: #ffffff;
            font-size: 26px;
            font-weight: 700;
            line-height: 1.3;
        }

        .content {
            padding: 40px 36px;
        }

        .content p {
            margin: 0 0 18px;
            font-size: 16px;
            color: #4b5563;
            line-height: 1.8;
        }

        .content strong {
            color: #111827;
        }

        .button {
            display: inline-block;
            background: #2563eb;
            color: #ffffff !important;
            padding: 14px 28px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
        }

        .button:hover {
            background: #1d4ed8;
        }

        .info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 14px 16px;
            margin-top: 24px;
        }

        .info-box p {
            margin: 0;
            color: #1e40af;
            font-size: 14px;
        }

        .footer {
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            padding: 24px 32px;
        }

        .footer p {
            margin: 0;
            font-size: 13px;
            color: #6b7280;
            line-height: 1.8;
        }

        .footer p+p {
            margin-top: 6px;
        }

        @media only screen and (max-width: 600px) {

            .email-wrapper {
                padding: 16px 10px;
            }

            .content {
                padding: 28px 22px;
            }

            .header {
                padding: 28px 20px;
            }

            .footer {
                padding: 20px;
            }

            .heading {
                font-size: 22px;
            }

            .logo {
                height: 36px;
            }

            .button {
                display: block;
                width: 100%;
                text-align: center;
            }
        }
    </style>


</head>

<body>

    <table role="presentation" width="100%" class="email-wrapper">
        <tr>
            <td align="center">

                <table role="presentation" width="600" class="email-container">

                    <!-- Header -->
                    <tr>
                        <td class="header">


                            <h1 class="heading">
                                {{ $heading ?? 'Start Your Story' }}
                            </h1>

                        </td>
                    </tr>

                    <!-- Main Content -->
                    <tr>
                        <td class="content">

                            @yield('content')

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="footer">

                            <p>
                                © {{ date('Y') }} Start Your Story. All rights reserved.
                            </p>

                            <p>
                                Connecting CA Students, Professionals and Firms.
                            </p>

                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>


</body>

</html>
