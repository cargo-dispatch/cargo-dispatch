<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/png" href="{{ asset('react-assets/assets/logo-GmhYhKM_.png') }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cargo Dispatch</title>

    <script>
      (function() {
        try {
          const savedTheme = localStorage.getItem('theme');
          const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
          let theme = 'light';
          if (savedTheme) { theme = savedTheme; }
          else if (systemPrefersDark) { theme = 'dark'; }
          document.documentElement.setAttribute('data-theme', theme);
          document.documentElement.style.visibility = 'hidden';
          document.documentElement.style.opacity = '0';
          window.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
              document.documentElement.style.visibility = 'visible';
              document.documentElement.style.opacity = '1';
              document.documentElement.style.transition = 'opacity 0.1s ease';
            }, 50);
          });
        } catch (error) {}
      })();
    </script>

    <!-- CSS file -->
    <link rel="stylesheet" href="{{ asset('react-assets/assets/index-B5-qWBVa.css') }}" />

    <!-- JS file -->
    <script type="module" crossorigin src="{{ asset('react-assets/assets/index-Ds3dmgYM.js') }}"></script>
</head>
<body>
    <div id="root"></div>
</body>
</html>
