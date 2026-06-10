<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Account Approved</title>
<style>
  body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
  .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
  .header { background: #1cc88a; padding: 32px; text-align: center; }
  .header h1 { color: #fff; margin: 0; font-size: 26px; }
  .header p { color: rgba(255,255,255,.8); margin: 4px 0 0; }
  .body { padding: 36px 40px; color: #333; }
  .creds { background: #f0fff8; border: 1px solid #1cc88a; border-radius: 6px; padding: 20px 24px; margin: 24px 0; }
  .creds table { width: 100%; border-collapse: collapse; }
  .creds td { padding: 6px 0; font-size: 15px; }
  .creds td:first-child { color: #666; width: 140px; }
  .creds td:last-child { font-weight: bold; color: #1a1a2e; font-family: monospace; }
  .btn-wrap { text-align: center; margin: 28px 0; }
  .btn { background: #1cc88a; color: #fff !important; text-decoration: none; padding: 14px 36px; border-radius: 6px; font-size: 16px; font-weight: bold; display: inline-block; }
  .warning { font-size: 13px; color: #e74c3c; background: #fff5f5; border-left: 3px solid #e74c3c; padding: 10px 14px; border-radius: 4px; }
  .footer { background: #f4f4f4; padding: 20px; text-align: center; font-size: 12px; color: #999; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>✅ You're Approved!</h1>
    <p>Welcome to {{ $companyName }}</p>
  </div>
  <div class="body">
    <h2>Congratulations, {{ $driverName }}!</h2>
    <p>Your driver application has been reviewed and <strong>approved</strong>. Your account is now active and you can start receiving shipment assignments.</p>

    <div class="creds">
      <p style="margin:0 0 12px;font-weight:bold;color:#1a1a2e;">Your Login Credentials</p>
      <table>
        <tr>
          <td>Email:</td>
          <td>{{ $driver->email }}</td>
        </tr>
        <tr>
          <td>Password:</td>
          <td>{{ $plainPassword }}</td>
        </tr>
      </table>
    </div>

    <div class="warning">
      🔒 Please change your password after your first login for security.
    </div>

    <div class="btn-wrap">
      <a href="{{ $loginUrl }}" class="btn">Login to Driver App</a>
    </div>

    <p style="font-size:13px;color:#666;">
      Download the driver app or access it via the web portal at the link above.
      If you have trouble logging in, contact your dispatcher.
    </p>
  </div>
  <div class="footer">
    &copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.
  </div>
</div>
</body>
</html>
