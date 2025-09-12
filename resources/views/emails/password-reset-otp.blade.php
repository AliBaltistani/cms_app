<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .otp-box {
            background-color: rgb(255, 106, 0);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin: 20px 0;
        }
        .otp-code {
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 5px;
            margin: 10px 0;
        }
        .warning {
            background-color: #f39c12;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .security-note {
            background-color: #e74c3c;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{ config('app.name') }}</div>
            <h2>Password Reset Request</h2>
        </div>

        <p>Hello {{ $userName }},</p>
        
        <p>We received a request to reset your password. To proceed with the password reset, please use the following One-Time Password (OTP):</p>

        <div class="otp-box">
            <div>Your OTP Code:</div>
            <div class="otp-code">{{ $otp }}</div>
        </div>

        <div class="warning">
            <strong> Important:</strong> This OTP will expire in 15 minutes
        </div>

        <p><strong>Instructions:</strong></p>
        <ol>
            <li>Go back to the password reset page</li>
            <li>Enter this OTP code: <strong>{{ $otp }}</strong></li>
            <li>Create your new password</li>
            <li>Confirm your new password</li>
        </ol>

        <div class="security-note">
            <strong>Security Notice:</strong><br>
            • If you didn't request this password reset, please ignore this email<br>
            • Never share this OTP with anyone<br>
            • This OTP can only be used once<br>
            • You have maximum 3 attempts to enter the correct OTP
        </div>

        <p>If you're having trouble with the password reset process, please contact our support team.</p>

        <div class="footer">
            <p>This is an automated message from {{ config('app.name') }}.<br>
            Please do not reply to this email.</p>
            <p><small>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</small></p>
        </div>
    </div>
</body>
</html>