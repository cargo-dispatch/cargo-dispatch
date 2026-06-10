<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Forgot Password - Cargo Dispatch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">


    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

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
            background-color: rgba(0, 0, 0, 0.85);
            padding: 40px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            color: #fff;
        }

        .form-wrapper h1 {
            font-size: 2.5rem;
            color: #f39c12;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .form-wrapper h1 i {
            color: #f8c71f;
        }

        .form-wrapper h2 {
            font-size: 1.5rem;
            color: #fff;
            margin-bottom: 10px;
            text-align: center;
        }

        .form-wrapper .subtitle {
            color: #aaa;
            font-size: 0.95rem;
            text-align: center;
            margin-bottom: 30px;
            line-height: 1.5;
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
            color: #f8c71f !important;
            border-bottom: 2px solid  #f8c71f;
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

        .form-group input::placeholder {
            color: #888;
        }

        .form-group input:focus {
            outline: none;
            border-color:  #f8c71f;
            box-shadow: 0 0 8px #f8c71f;
        }

        .error-message {
            color: #e74c3c;
            font-size: 0.85rem;
            margin-top: 5px;
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
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #00c0d9;
        }

        .btn-submit:disabled {
            background-color: #666;
            cursor: not-allowed;
        }

        .footer {
            margin-top: 20px;
            font-size: 0.9rem;
            text-align: center;
        }

        .footer a {
            color: #00e1ff;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: #00c0d9;
        }

        .back-to-login {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 15px;
        }

        .back-to-login i {
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {
            .form-wrapper {
                padding: 20px;
            }

            .form-wrapper h1 {
                font-size: 2rem;
            }

            .form-wrapper h2 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>

@if (session('status'))
<script>
    $(document).ready(function () {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end', // top-right corner
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            background: '#2c2c2c',
            color: '#fff',
            didOpen: (toast) => {
                toast.style.border = '1px solid #00e1ff';
            }
        });

        Toast.fire({
            icon: 'success',
            title: '{{ session('status') }}'
        });
    });
</script>
@endif


    <!-- Fixed parallax background -->
    <div class="parallax-bg" style="background-image: url('{{ asset('assets/img/logo.png') }}');"></div>

    <div class="overlay">
        <div class="form-wrapper">
            <h1><i class="fas fa-map-marker-alt"></i> Cargo Dispatch</h1>

            <div class="tabs">
                <div class="logo-color">Reset Password</div>
            </div>

            <h2 class="logo-color"> Forgot Your Password?</h2>
            <p class="subtitle">
                No worries — just enter your email address below, and we'll send you a link to reset your password.
            </p>

            <form id="forgot-password-form" method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input id="email" type="email" name="email" placeholder="Enter Email Address..." required autofocus />
                    @if($errors->has('email'))
                        <div class="error-message">{{ $errors->first('email') }}</div>
                    @endif
                </div>

                <button type="submit" class="btn-submit button-action-yellow" id="submit-btn">
                    <i class="fas fa-paper-plane"></i> Reset Password
                </button>
            </form>

            <div class="footer">
                <a href="{{ route('admin.login') }}" class="logo-color">
                    <i class=" logo-color"></i> Already have an account? Login!
                </a>
            </div>
        </div>
    </div>

    <script>
        $('#forgot-password-form').on('submit', function (e) {
            e.preventDefault();

            const email = $('#email').val().trim();
            const submitBtn = $('#submit-btn');

            if (!email) {
                Swal.fire({
                    icon: 'error',
                    title: 'Email Required',
                    text: 'Please enter your email address.',
                    background: '#2c2c2c',
                    color: '#fff',
                    confirmButtonColor: '#00e1ff'
                });
                return;
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Email',
                    text: 'Please enter a valid email address.',
                    background: '#2c2c2c',
                    color: '#fff',
                    confirmButtonColor: '#00e1ff'
                });
                return;
            }

            // Disable button and show loading state
            submitBtn.prop('disabled', true);
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Sending...');

            // Submit the form if valid
            this.submit();
        });

        // Re-enable button if there's an error and page reloads
        $(document).ready(function() {
            const submitBtn = $('#submit-btn');
            submitBtn.prop('disabled', false);
            submitBtn.html('<i class="fas fa-paper-plane"></i> Reset Password');
        });
    </script>
</body>
</html>