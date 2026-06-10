<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Driver Invitation</title>
<style>
  body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
  .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
  .header { background: #1a1a2e; padding: 32px; text-align: center; }
  .header h1 { color: #fff; margin: 0; font-size: 24px; }
  .header p { color: #aaa; margin: 4px 0 0; font-size: 13px; }
  .body { padding: 36px 40px; color: #333; }
  .body h2 { margin-top: 0; color: #1a1a2e; }
  .steps { background: #f9f9f9; border-radius: 6px; padding: 20px 24px; margin: 24px 0; }
  .steps h4 { margin: 0 0 12px; color: #555; font-size: 13px; text-transform: uppercase; letter-spacing: .5px; }
  .step { display: flex; align-items: flex-start; margin-bottom: 10px; font-size: 14px; }
  .step-num { background: #4e73df; color: #fff; width: 22px; height: 22px; border-radius: 50%; font-size: 11px; font-weight: bold; display: inline-flex; align-items: center; justify-content: center; margin-right: 10px; flex-shrink: 0; }
  .btn-wrap { text-align: center; margin: 28px 0; }
  .btn { background: #4e73df; color: #fff !important; text-decoration: none; padding: 14px 36px; border-radius: 6px; font-size: 16px; font-weight: bold; display: inline-block; }
  .docs { font-size: 13px; color: #666; background: #fffbea; border-left: 3px solid #f0ad4e; padding: 12px 16px; border-radius: 4px; margin-top: 20px; }
  .expiry { font-size: 13px; color: #e74c3c; margin-top: 16px; text-align: center; }
  .footer { background: #f4f4f4; padding: 20px; text-align: center; font-size: 12px; color: #999; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>{{ $companyName }}</h1>
    <p>Driver Management Platform</p>
  </div>
  <div class="body">
    <h2>Hi {{ $driverName }},</h2>
    <p>You've been invited to join <strong>{{ $companyName }}</strong> as a driver. To get started, please complete your profile and upload your required documents.</p>

    <div class="steps">
      <h4>What you'll need to complete:</h4>
      <div class="step"><span class="step-num">1</span> Personal information & contact details</div>
      <div class="step"><span class="step-num">2</span> CDL (Commercial Driver License) — front & back</div>
      <div class="step"><span class="step-num">3</span> Medical card (DOT physical)</div>
      <div class="step"><span class="step-num">4</span> Drug test result</div>
      <div class="step"><span class="step-num">5</span> Any other required compliance documents</div>
    </div>

    <div class="btn-wrap">
      <a href="{{ $registrationUrl }}" class="btn">Complete My Driver Profile</a>
    </div>

    <div class="docs">
      <strong>Tip:</strong> Have your CDL, medical card, and drug test results ready before starting. The form saves your progress automatically.
    </div>

    <p class="expiry">⏳ This invitation link expires {{ $expiresIn }}</p>

    <p style="font-size:13px;color:#999;margin-top:24px;">
      If you didn't expect this invitation, you can ignore this email.
      If you have questions, contact us at <a href="mailto:support@{{ parse_url(config('app.url'), PHP_URL_HOST) }}">support</a>.
    </p>
  </div>
  <div class="footer">
    &copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.
  </div>
</div>
</body>
</html>
