<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment failed</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .wrapper {
            max-width: 560px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
        }

        .header {
            background: #111827;
            padding: 32px 40px;
            text-align: center;
        }

        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 22px;
        }

        .body {
            padding: 40px;
            color: #374151;
            line-height: 1.6;
        }

        .body p {
            margin: 0 0 16px;
        }

        .box {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
            background: #f9fafb;
        }

        .box p {
            margin: 0 0 8px;
            font-size: 14px;
        }

        .box p:last-child {
            margin-bottom: 0;
        }

        .error {
            border-left: 4px solid #dc2626;
            background: #fef2f2;
            padding: 12px 14px;
            margin: 0 0 24px;
        }

        .error p {
            margin: 0;
            font-size: 14px;
            color: #7f1d1d;
        }

        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: #111827;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 15px;
        }

        .note {
            font-size: 13px;
            color: #6b7280;
            margin-top: 24px;
        }

        .footer {
            background: #f9fafb;
            padding: 24px 40px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="header">
            <h1>T-Shirts Lab</h1>
        </div>

        <div class="body">
            <p>Hi {{ $firstName }},</p>
            <p>We could not process your payment for order <strong>{{ $orderNumber }}</strong>.</p>

            <div class="box">
                <p><strong>Order number:</strong> {{ $orderNumber }}</p>
                <p><strong>Order total:</strong> ${{ number_format($total, 2) }}</p>
            </div>

            @if($failureReason)
            <div class="error">
                <p><strong>Reason:</strong> {{ $failureReason }}</p>
            </div>
            @endif

            @if($checkoutUrl)
            <p style="text-align:center; margin: 32px 0;">
                <a href="{{ $checkoutUrl }}" class="btn">Try payment again</a>
            </p>
            @endif

            <p class="note">No charge was completed. You can retry with another payment method.</p>
        </div>

        <div class="footer">© {{ date('Y') }} T-Shirts Lab. All rights reserved.</div>
    </div>
</body>

</html>
