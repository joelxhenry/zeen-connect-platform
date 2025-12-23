<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Zeen Connect</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            padding: 24px;
            text-align: center;
        }
        .email-header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .email-body {
            padding: 32px 24px;
        }
        .email-footer {
            background-color: #f9fafb;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .email-footer p {
            color: #6b7280;
            font-size: 14px;
            margin: 0;
        }
        .email-footer a {
            color: #6366f1;
            text-decoration: none;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 16px;
        }
        .message {
            color: #4b5563;
            margin-bottom: 24px;
        }
        .info-card {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin: 24px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #6b7280;
            font-size: 14px;
        }
        .info-value {
            color: #111827;
            font-weight: 500;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
        }
        .btn-secondary {
            background: #e5e7eb;
            color: #374151 !important;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-confirmed {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-completed {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .highlight-box {
            background: linear-gradient(135deg, #eff6ff, #f5f3ff);
            border-left: 4px solid #6366f1;
            padding: 16px;
            border-radius: 0 8px 8px 0;
            margin: 24px 0;
        }
        .amount {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
        }
        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 24px 0;
        }
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 10px;
            }
            .email-body {
                padding: 24px 16px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <h1>Zeen Connect</h1>
            </div>
            <div class="email-body">
                @yield('content')
            </div>
            <div class="email-footer">
                <p>&copy; {{ date('Y') }} Zeen Connect. All rights reserved.</p>
                <p style="margin-top: 8px;">
                    <a href="{{ config('app.url') }}">Visit our website</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
