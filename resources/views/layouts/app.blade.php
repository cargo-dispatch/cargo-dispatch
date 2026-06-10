<!DOCTYPE html>
<html lang="en" data-theme="{{ session('theme', 'light') }}">

<head>
    <base href="/">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">
 <script>
    (function() {
        const sessionTheme = '{{ session('theme', 'light') }}';
        const localTheme = localStorage.getItem('theme');
        
        // If localStorage has a theme, use it and update the data attribute
        if (localTheme && localTheme !== sessionTheme) {
            document.documentElement.setAttribute('data-theme', localTheme);
        } else if (sessionTheme) {
            // Otherwise, sync localStorage with session
            localStorage.setItem('theme', sessionTheme);
        }
    })();
</script>


    <title>@yield('title', config('app.name', 'Cargo Dispatch'))</title>

    <!-- Vendor CSS -->
    <link href="{{ asset('assets/vendor/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/fontawesome-free/css/fa6.min.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-css/bootstrap.min.css') }}" rel="stylesheet">

    @stack('styles')

    <!-- Custom styles -->
    <link href="{{ asset('assets/css/sb-admin-2.css') }}?v={{ filemtime(public_path('assets/css/sb-admin-2.css')) }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/tom-select/tom-select.bootstrap5.min.css') }}" rel="stylesheet">

    <!-- Chat CSS -->
    <link href="{{ asset('assets/css/chat.css') }}" rel="stylesheet">

    <!-- Theme CSS -->
    <link href="{{ asset('assets/css/theme.css') }}?v=1.3" rel="stylesheet">
    <link href="{{ asset('assets/css/page-layout.css') }}?v={{ filemtime(public_path('assets/css/page-layout.css')) }}" rel="stylesheet">
    <link href="{{ asset('assets/css/style.css') }}?v={{ filemtime(public_path('assets/css/style.css')) }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/calendar.css') }}">

    <!-- Vendor JS (head — needed before DOM) -->
    <script src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/masking/inputmask.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/tom-select/tom-select.complete.min.js') }}"></script>
    <script src="https://unpkg.com/react@18/umd/react.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/connectycube@4/dist/connectycube.min.js"></script>
    <script src="https://unpkg.com/@connectycube/chat-widget@latest/dist/index.umd.js"></script>

    @vite(['resources/js/app.js'])

    <script>
        window.APP_URL = "{{ config('app.url') }}";
        window.userId = {{ auth()->id() ?? 'null' }};
        window.userIsAdmin = true;
    </script>

</head>

<body id="page-top">
    <div id="pageLoader" class="page-loader">
        <div class="custom-loader">
            <img src="{{ asset(config('app.logo')) }}" alt="Loading Logo" class="loader-logo">
            <div class="loader"></div>
        </div>
    </div>

    <div id="wrapper" class="wrapper-color">
        @include('layouts.sidebar')

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content" class="wrapper-color">
                @include('layouts.navbar')
                @yield('content')
                @include('connectycube-chat.index-js')
            </div>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">Ã—</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/jquery-easing/jquery.easing.min.js') }}"></script>
    <script src="{{ asset('assets/js/sb-admin-2.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('assets/js/demo/datatables-demo.js') }}"></script>
    <script src="{{ asset('assets/js/notification-poller.js') }}"></script>
    <script src="{{ asset('assets/js/theme-toggle.js') }}"></script>
    <script src="{{ asset('assets/js/time-date-format.js') }}"></script>
    <script src="{{ asset('assets/js/calendar.js') }}"></script>
    <script src="{{ asset('assets/js/pagination-utils.js') }}"></script>
    <script src="{{ asset('assets/js/app-ui.js') }}"></script>

    @yield('scripts')
</body>

</html>