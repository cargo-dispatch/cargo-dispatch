<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Download Cargo Dispatch Driver App</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Roboto', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #111;
            color: #fff;
            padding: 24px;
        }
        .card {
            max-width: 420px;
            width: 100%;
            background: rgba(0, 0, 0, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 32px 28px;
            text-align: center;
        }
        .logo { font-size: 2rem; margin-bottom: 8px; }
        h1 {
            color: #f8c71f;
            font-size: 1.35rem;
            margin-bottom: 8px;
        }
        .subtitle {
            color: #aaa;
            font-size: 0.9rem;
            margin-bottom: 28px;
            line-height: 1.5;
        }
        .stores {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .store-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 20px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            transition: transform 0.15s, opacity 0.15s;
        }
        .store-btn:hover { transform: translateY(-1px); opacity: 0.95; }
        .store-btn.android {
            background: #3ddc84;
            color: #000;
        }
        .store-btn.ios {
            background: #fff;
            color: #000;
        }
        .store-btn.disabled {
            background: #333;
            color: #888;
            pointer-events: none;
        }
        .note {
            margin-top: 20px;
            font-size: 0.75rem;
            color: #666;
            line-height: 1.5;
        }
        #redirect-msg {
            margin-top: 16px;
            color: #00e1ff;
            font-size: 0.85rem;
        }
        .qr-section {
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .qr-box {
            display: inline-block;
            background: #fff;
            padding: 10px;
            border-radius: 10px;
            margin: 12px 0;
        }
        .qr-hint {
            color: #888;
            font-size: 0.8rem;
            line-height: 1.5;
        }
        .share-link {
            display: flex;
            gap: 8px;
            margin-top: 14px;
        }
        .share-link input {
            flex: 1;
            min-width: 0;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #444;
            background: #1a1a1a;
            color: #ccc;
            font-size: 0.75rem;
        }
        .share-link button {
            padding: 10px 14px;
            border: none;
            border-radius: 8px;
            background: #f8c71f;
            color: #000;
            font-weight: 700;
            font-size: 0.75rem;
            cursor: pointer;
        }
        .stores-title {
            color: #aaa;
            font-size: 0.8rem;
            margin-bottom: 12px;
        }
        body.mobile-redirect .qr-section,
        body.mobile-redirect .stores { display: none; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">📱</div>
        <h1>Cargo Dispatch Driver App</h1>
        <p class="subtitle">Scan the QR code with your phone, or use the buttons below.</p>

        <div class="qr-section" id="qr-section">
            <div class="qr-box"><div id="qrCanvas"></div></div>
            <p class="qr-hint">Open your phone camera → scan → install<br>Works on Android &amp; iPhone</p>
            <div class="share-link">
                <input type="text" id="install-url" value="{{ $installUrl }}" readonly />
                <button type="button" id="copy-link-btn">Copy</button>
            </div>
        </div>

        <p class="stores-title">Or download directly:</p>
        <div class="stores">
            <a href="{{ $androidUrl }}" class="store-btn android" id="btn-android">
                <span>🤖</span> Download for Android
            </a>
            @if ($iosUrl)
                <a href="{{ $iosUrl }}" class="store-btn ios" id="btn-ios">
                    <span>🍎</span> Download for iPhone / iPad
                </a>
            @else
                <span class="store-btn ios disabled" id="btn-ios">
                    <span>🍎</span> iOS — link not configured yet
                </span>
            @endif
        </div>

        <p id="redirect-msg" style="display:none;">Redirecting…</p>

        @unless ($iosUrl)
            <p class="note">
                iOS installs require an App Store or TestFlight link.
                Set <code>DRIVER_APP_IOS_URL</code> in your server <code>.env</code> file.
            </p>
        @endunless
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        (function () {
            var installUrl = @json($installUrl);
            var iosUrl = @json($iosUrl);
            var androidUrl = @json($androidUrl);
            var ua = navigator.userAgent || '';
            var isIOS = /iPhone|iPad|iPod/i.test(ua);
            var isAndroid = /Android/i.test(ua);
            var msg = document.getElementById('redirect-msg');

            new QRCode(document.getElementById('qrCanvas'), {
                text: installUrl,
                width: 160,
                height: 160,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });

            document.getElementById('copy-link-btn').addEventListener('click', function () {
                var input = document.getElementById('install-url');
                input.select();
                input.setSelectionRange(0, 99999);
                navigator.clipboard.writeText(installUrl).then(function () {
                    document.getElementById('copy-link-btn').textContent = 'Copied!';
                    setTimeout(function () {
                        document.getElementById('copy-link-btn').textContent = 'Copy';
                    }, 2000);
                });
            });

            if (isIOS && iosUrl) {
                document.body.classList.add('mobile-redirect');
                msg.style.display = 'block';
                msg.textContent = 'Opening iOS install…';
                window.location.replace(iosUrl);
            } else if (isAndroid) {
                document.body.classList.add('mobile-redirect');
                msg.style.display = 'block';
                msg.textContent = 'Starting Android download…';
                window.location.replace(androidUrl);
            }
        })();
    </script>
</body>
</html>
