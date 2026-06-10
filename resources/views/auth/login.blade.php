<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Login - Cargo Dispatch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    <!-- Font Awesome - Updated CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* Fix: Reserve scrollbar space to avoid layout shift */
        html {
            overflow-y: scroll; /* keeps scrollbar space reserved always */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        .login-btn{
            background-color: #f8c71f  !important;
            color: white;
        }

        html,
        body {
            height: 100%;
            font-family: 'Roboto', sans-serif;
        }

        /* Remove background from body */
        body {
            /* no background here */
        }

        /* New fixed background div */
        .parallax-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-size: cover;
            background-position: center;
            z-index: -1;
            will-change: transform;
        }

        .overlay {
            background-color: rgba(0, 0, 0, 0.6);
            height: 100%;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 0;
        }

        .form-wrapper {
            padding: 40px;
            color: #fff;
        }

        .form-wrapper h1 {
            font-size: 2.5rem;
            color: #f8c71f ;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .form-wrapper h1 i {
            color: #f8c71f ;
        }

        .tabs {
            display: flex;
            gap: 20px;
            border-bottom: 1px solid #333;
            margin-bottom: 30px;
        }

        .tab {
            padding: 10px 0;
            color: #ccc;
            cursor: pointer;
        }

        .tab.active {
            border-bottom: 2px solid #f8c71f ;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-size: 0.95rem;
            color: #aaa;
            display: block;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            background: transparent;
            border: 1px solid #666;
            color: #fff;
            border-radius: 4px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #00e1ff;
            box-shadow: 0 0 8px #00e1ff88;
        }

        /* Password wrapper and toggle styles */
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-wrapper input {
            padding-right: 45px;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            cursor: pointer;
            color: #aaa;
            font-size: 1.1rem;
            transition: color 0.3s;
            user-select: none;
        }

        .password-toggle:hover {
            color: #00e1ff;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .form-check input[type='checkbox'] {
            accent-color: #f1c40f;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #00e1ff;
            color: #000;
            font-weight: bold;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-submit:hover {
            background-color: #00c0d9;
        }

        .footer {
            margin-top: 20px;
            font-size: 0.9rem;
            text-align: center;
        }

        .footer a {
            color: #00e1ff;
            text-decoration: none;
        }

        .login-container {
            display: flex;
            gap: 0;
            background-color: rgba(0, 0, 0, 0.85);
            border-radius: 12px;
            overflow: hidden;
            max-width: 820px;
            width: 95%;
        }
        .form-wrapper {
            background: transparent !important;
            border-radius: 0 !important;
            flex: 1;
            padding: 40px;
            min-width: 0;
        }
        .qr-panel {
            width: 240px;
            flex-shrink: 0;
            background: rgba(255,255,255,0.04);
            border-left: 1px solid rgba(255,255,255,0.08);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
            text-align: center;
        }
        .qr-panel .qr-icon {
            font-size: 1.8rem;
            margin-bottom: 8px;
        }
        .qr-panel .qr-title {
            color: #f8c71f;
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .qr-panel .qr-subtitle {
            color: #aaa;
            font-size: 0.72rem;
            margin-bottom: 16px;
            line-height: 1.4;
        }
        .qr-box {
            background: #fff;
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
        }
        .qr-panel .qr-hint {
            margin-top: 12px;
            color: #666;
            font-size: 0.68rem;
            line-height: 1.5;
        }
        .qr-platforms {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            font-size: 0.7rem;
            color: #aaa;
        }
        .qr-platforms span {
            background: rgba(255,255,255,0.06);
            padding: 4px 10px;
            border-radius: 12px;
        }
        .qr-panel .qr-download-btn {
            margin-top: 14px;
            display: inline-block;
            background: #f8c71f;
            color: #000;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 7px 16px;
            border-radius: 20px;
            text-decoration: none;
            letter-spacing: 0.3px;
        }
        .qr-panel .qr-download-btn:hover {
            background: #e6b800;
        }
        @media (max-width: 650px) {
            .login-container { flex-direction: column; }
            .qr-panel {
                width: 100%;
                border-left: none;
                border-top: 1px solid rgba(255,255,255,0.08);
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .form-wrapper {
                padding: 20px;
            }

            .form-wrapper h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
@if ($errors->any())
<script>
    $(document).ready(function () {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            background: '#2c2c2c',
            color: '#fff',
            didOpen: (toast) => {
                toast.style.border = '1px solid #e74c3c';
            }
        });

        @foreach ($errors->all() as $error)
            Toast.fire({
                icon: 'error',
                title: @json($error)
            });
        @endforeach
    });
</script>
@endif
@if (session('status'))
<script>
    $(document).ready(function () {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: '{{ session('status') }}',
            showConfirmButton: false,
            timer: 3000,
            background: '#2c2c2c',
            color: '#fff',
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.style.border = '1px solid #00e1ff';
            }
        });
    });
</script>
@endif

    <!-- Fixed parallax background -->
    <div class="parallax-bg" style="background-image: url('{{ asset('assets/img/logo.png') }}');"></div>

    <div class="overlay">
        <div class="login-container">
        <div class="form-wrapper">
            <h1> Cargo Dispatch</h1>

            <div class="tabs">
                <div class="tab active text-sidebar-color">Login</div>
                <!-- <div class="tab">Email Login</div> -->
            </div>

            <form id="login-form" method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input id="email" type="email" name="email" autofocus />
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input id="password" type="password" name="password" />
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                    </div>
                </div>

                <div class="form-check">
                    <input type="checkbox" name="remember" id="remember" />
                    <label for="remember">Remember my login</label>
                </div>

                <button type="submit" class="btn-submit login-btn">GO</button>
            </form>

            <div class="footer">
                <a href="{{ route('password.request') }}" class="logo-color">Forgot password</a>
            </div>

        </div>

        {{-- QR Panel --}}
        <div class="qr-panel">
            <div class="qr-icon">📱</div>
            <div class="qr-title">Driver App</div>
            <div class="qr-subtitle">Scan with your phone camera to download</div>
            <div class="qr-box">
                <div id="qrCanvas"></div>
            </div>
            <div class="qr-platforms">
                <span>🤖 Android</span>
                <span>🍎 iOS</span>
            </div>
            <div class="qr-hint">Opens the right store for your device<br>Free download</div>
            <a href="{{ route('app.install') }}" class="qr-download-btn">⬇ Get the App</a>
        </div>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        // QR → smart download page (Android APK or iOS App Store / TestFlight)
        new QRCode(document.getElementById("qrCanvas"), {
            text: "{{ route('app.install') }}",
            width: 120,
            height: 120,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    </script>

    <script>
        // Toggle password visibility
        $(document).ready(function() {
            $('#togglePassword').on('click', function() {
                const passwordField = $('#password');
                const currentType = passwordField.attr('type');
                
                if (currentType === 'password') {
                    passwordField.attr('type', 'text');
                    $(this).removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    passwordField.attr('type', 'password');
                    $(this).removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
        });
    </script>

</body>
</html>