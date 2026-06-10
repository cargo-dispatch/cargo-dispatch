<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Reset Password - Cargo Dispatch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        html { overflow-y: scroll; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; font-family: 'Roboto', sans-serif; }

        .parallax-bg {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background-size: cover; background-position: center;
            z-index: -1; will-change: transform;
        }

        .overlay {
            background-color: rgba(0, 0, 0, 0.6);
            height: 100%; width: 100%;
            display: flex; align-items: center; justify-content: center;
            position: relative; z-index: 0;
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

        .form-wrapper h1 i { color: #00e1ff; }
        .form-wrapper h2 { font-size: 1.5rem; color: #fff; margin-bottom: 10px; text-align: center; }
        .form-wrapper .subtitle { color: #aaa; font-size: 0.95rem; text-align: center; margin-bottom: 30px; line-height: 1.5; }

        .form-group { margin-bottom: 20px; }
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

        .form-group input::placeholder { color: #888; }
        .form-group input:focus {
            outline: none;
            border-color: #00e1ff;
            box-shadow: 0 0 8px #00e1ff88;
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

        .btn-submit:hover { background-color: #00c0d9; }
        .btn-submit:disabled { background-color: #666; cursor: not-allowed; }

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

        .footer a:hover { color: #00c0d9; }

        @media (max-width: 768px) {
            .form-wrapper { padding: 20px; }
            .form-wrapper h1 { font-size: 2rem; }
            .form-wrapper h2 { font-size: 1.3rem; }
        }
    </style>
</head>
<body>

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


<!-- Background -->
<div class="parallax-bg" style="background-image: url('{{ asset('assets/img/logo.png') }}');"></div>

<div class="overlay">
    <div class="form-wrapper">
        <h1><i class="fas fa-lock"></i> Cargo Dispatch</h1>

     
        <h2>Set Your New Password</h2>
        <p class="subtitle">
            Enter your new password and confirm it to complete the reset process.
        </p>

        <form method="POST" action="{{ route('password.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="form-group">
                <label for="email">Email Address</label>
                <input id="email" type="email" name="email" placeholder="Email Address" value="{{ old('email', $request->email) }}" required autofocus />
                @error('email')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">New Password</label>
                <input id="password" type="password" name="password" placeholder="New Password" required />
                @error('password')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm New Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Confirm Password" required />
                @error('password_confirmation')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-sync-alt"></i> Reset Password
            </button>
        </form>

        <div class="footer">
            <a href="{{ route('admin.login') }}">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>
</div>
</body>
</html>
