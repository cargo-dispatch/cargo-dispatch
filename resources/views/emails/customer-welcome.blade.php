<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 560px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header { background: #1a1a2e; padding: 30px; text-align: center; }
        .header h1 { color: #F8C71F; margin: 0; font-size: 22px; }
        .header p { color: #aaa; margin: 6px 0 0; font-size: 13px; }
        .body { padding: 32px; }
        .body p { color: #444; line-height: 1.6; margin: 0 0 16px; }
        .credentials { background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 6px; padding: 20px; margin: 24px 0; }
        .credentials p { margin: 8px 0; font-size: 15px; color: #333; }
        .credentials strong { color: #1a1a2e; }
        .credentials .value { font-family: monospace; font-size: 16px; color: #333; background: transparent; padding: 0; border-radius: 0; }
        .warning { font-size: 13px; color: #888; border-top: 1px solid #eee; padding-top: 16px; margin-top: 8px; }
        .footer { background: #f4f4f4; padding: 20px; text-align: center; font-size: 12px; color: #aaa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚛 Cargo Dispatch</h1>
            <p>Your account is ready</p>
        </div>
        <div class="body">
            <p>Hello <strong>{{ $customerName }}</strong>,</p>
            <p>Your Cargo Dispatch customer account has been created. Use the credentials below to log in to the mobile app.</p>

            <div class="credentials">
                <p><strong>Email:</strong><br><span class="value">{{ $email }}</span></p>
                <p style="margin-top:16px;"><strong>Temporary Password:</strong><br><span class="value">{{ $tempPassword }}</span></p>
            </div>

            <p>Please log in and change your password as soon as possible.</p>
            <p class="warning">⚠️ This is a temporary password. Keep it secure and do not share it with anyone.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Cargo Dispatch. All rights reserved.
        </div>
    </div>
</body>
</html>
