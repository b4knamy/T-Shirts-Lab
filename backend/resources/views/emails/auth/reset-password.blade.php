<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset your password</title>
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
            <p>We received a request to reset the password for your account. Click the button below to choose a new one:</p>
            <p style="text-align:center; margin: 32px 0;">
                <a href="{{ $resetUrl }}" class="btn">Reset Password</a>
            </p>
            <p>This link will expire in <strong>{{ $expiresInMinutes }} minutes</strong>.</p>
            <p>If you didn't request a password reset, you can safely ignore this email — your password will not change.</p>
            <p class="note">If the button above doesn't work, copy and paste this URL into your browser:<br>
                <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
            </p>
        </div>
        <div class="footer">© {{ date('Y') }} T-Shirts Lab. All rights reserved.</div>
    </div>
</body>

</html>