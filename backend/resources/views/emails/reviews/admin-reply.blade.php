<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Review reply</title>
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

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
            background: #f9fafb;
        }

        .card p {
            margin: 0 0 10px;
            font-size: 14px;
        }

        .card p:last-child {
            margin-bottom: 0;
        }

        .reply {
            border-left: 4px solid #111827;
            background: #f3f4f6;
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
            <p>Our team replied to your review for <strong>{{ $productName }}</strong>.</p>

            <div class="card">
                <p><strong>Your rating:</strong> {{ $rating }}/5</p>
                <p><strong>Your comment:</strong> {{ $userComment ?: 'No comment provided.' }}</p>
            </div>

            <div class="card reply">
                <p><strong>Admin reply:</strong></p>
                <p>{{ $adminReply }}</p>
            </div>

            @if($productUrl)
            <p style="text-align:center; margin: 28px 0 0;">
                <a href="{{ $productUrl }}" class="btn">View product</a>
            </p>
            @endif
        </div>

        <div class="footer">© {{ date('Y') }} T-Shirts Lab. All rights reserved.</div>
    </div>
</body>

</html>
