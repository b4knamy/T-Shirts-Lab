<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Order update</title>
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

        .body h2 {
            margin: 0 0 16px;
            font-size: 20px;
            color: #111827;
        }

        .summary {
            margin-bottom: 24px;
        }

        .order-box {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 18px;
            margin: 18px 0 24px;
            background: #f9fafb;
        }

        .order-box p {
            margin: 0 0 8px;
            font-size: 14px;
        }

        .order-box p:last-child {
            margin-bottom: 0;
        }

        .notes-box {
            border-left: 4px solid #111827;
            background: #f3f4f6;
            padding: 12px 14px;
            margin: 0 0 24px;
        }

        .notes-box p {
            margin: 0;
            font-size: 14px;
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
            <h2>{{ $headline }}</h2>
            <p class="summary">{{ $summary }}</p>

            <div class="order-box">
                <p><strong>Order number:</strong> {{ $orderNumber }}</p>
                @if($previousStatus && $previousStatus !== $currentStatus)
                <p><strong>Previous status:</strong> {{ ucfirst(strtolower($previousStatus)) }}</p>
                @endif
                <p><strong>Current status:</strong> {{ $statusLabel }}</p>
                <p><strong>Order total:</strong> ${{ number_format($total, 2) }}</p>
            </div>

            @if($adminNotes)
            <div class="notes-box">
                <p><strong>Admin notes:</strong> {{ $adminNotes }}</p>
            </div>
            @endif

            @if($orderUrl)
            <p style="text-align:center; margin: 32px 0;">
                <a href="{{ $orderUrl }}" class="btn">View order details</a>
            </p>
            @endif

            <p class="note">If you have any questions, reply to this email and our support team will help you.</p>
        </div>

        <div class="footer">© {{ date('Y') }} T-Shirts Lab. All rights reserved.</div>
    </div>
</body>

</html>
