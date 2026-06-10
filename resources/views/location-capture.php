<!DOCTYPE html>
<html>
<head>
    <title>Location Capture</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h2>Capturing your location...</h2>

    <script>
        const baseUrl = "{{ config('app.url') }}";

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const long = position.coords.longitude;

                fetch(`${APP_URL}/{{route('save.location')}}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        latitude: lat,
                        longitude: long
                    })
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                })
                .catch(err => {
                    alert("Error saving location");
                });
            }, function(error) {
                alert("Location permission denied.");
            });
        } else {
            alert("Geolocation not supported by your browser.");
        }
    </script>
</body>
</html>
