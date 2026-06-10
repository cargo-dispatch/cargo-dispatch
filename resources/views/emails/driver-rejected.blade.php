<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Application Update</title>
<style>
  body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
  .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
  .header { background: #e74a3b; padding: 32px; text-align: center; }
  .header h1 { color: #fff; margin: 0; font-size: 24px; }
  .header p { color: rgba(255,255,255,.8); margin: 4px 0 0; }
  .body { padding: 36px 40px; color: #333; }
  .reason-box { background: #fff5f5; border: 1px solid #f5c6cb; border-radius: 6px; padding: 20px 24px; margin: 24px 0; }
  .reason-box p { margin: 0; color: #333; line-height: 1.6; }
  .footer { background: #f4f4f4; padding: 20px; text-align: center; font-size: 12px; color: #999; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>Application Update</h1>
    <p>{{ $companyName }}</p>
  </div>
  <div class="body">
    <h2>Hi {{ $driverName }},</h2>
    <p>Thank you for submitting your driver application to <strong>{{ $companyName }}</strong>.</p>
    <p>After reviewing your submitted documents, we are unable to approve your application at this time for the following reason:</p>

    <div class="reason-box">
      <p>{{ $reason }}</p>
    </div>

    <p>If you believe this is an error or you have updated documents to submit, please contact your recruiter or dispatcher directly.</p>

    <p style="font-size:13px;color:#999;margin-top:24px;">
      We appreciate your interest in joining {{ $companyName }} and wish you the best.
    </p>
  </div>
  <div class="footer">
    &copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.
  </div>
</div>
</body>
</html>
