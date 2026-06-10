<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Google Maps (real provider when you add a key)
    'google' => [
        'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    // Brevo email
    'brevo' => [
        'key' => env('BREVO_API_KEY'),
    ],

    // Gemini AI (used for load/driver matching assistant)
  'groq' => [
    'key'   => env('GROQ_API_KEY'),
    'model' => env('GROQ_MODEL', 'llama3-8b-8192'),
],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'connectycube' => [
        'app_id' => env('CONNECTYCUBE_APP_ID', 9277),
        'auth_key' => env('CONNECTYCUBE_AUTH_KEY', 'F2695385-56A2-45DD-A2DE-CF2310792F24'),
        'auth_secret' => env('CONNECTYCUBE_AUTH_SECRET'),
        'admin_login' => env('CONNECTYCUBE_ADMIN_LOGIN'),
        'admin_password' => env('CONNECTYCUBE_ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration API Keys — just add the key, mock → real automatically
    |--------------------------------------------------------------------------
    |
    | Each provider checks if its key is set. If yes → real API.
    | If no key → built-in mock data. No other changes needed.
    |
    */

    // ELD / Telematics  →  https://developer.samsara.com/
    'samsara' => [
        'key' => env('SAMSARA_API_KEY', ''),
    ],

    // Load Board  →  https://www.dat.com/
    'dat' => [
        'username' => env('DAT_USERNAME', ''),
        'password' => env('DAT_PASSWORD', ''),
    ],

    // Weather  →  https://www.tomorrow.io/ (free tier available)
    'tomorrow_io' => [
        'key' => env('TOMORROW_IO_KEY', ''),
    ],

    // Fuel stations (FREE)  →  https://developer.nrel.gov/signup/
    'nrel' => [
        'key' => env('NREL_API_KEY', ''),
    ],

    // Diesel prices (FREE)  →  https://www.eia.gov/opendata/register.php
    'eia' => [
        'key' => env('EIA_API_KEY', ''),
    ],

    // SMS Notifications  →  https://www.twilio.com/
    'twilio' => [
        'sid'   => env('TWILIO_SID', ''),
        'token' => env('TWILIO_TOKEN', ''),
        'from'  => env('TWILIO_FROM', ''),
    ],

    // Compliance / DOT (FREE)  →  https://mobile.fmcsa.dot.gov/developer/home.page
    'fmcsa' => [
        'key' => env('FMCSA_API_KEY', ''),
    ],

    // Payments  →  https://stripe.com/
    'stripe' => [
        'key'    => env('STRIPE_KEY', ''),
        'secret' => env('STRIPE_SECRET_KEY', ''),
    ],

    // Google Maps already above → set GOOGLE_MAPS_API_KEY → maps auto-switch
    // AWS / Textract already above → set AWS_ACCESS_KEY_ID → document AI auto-switch

    // Driver mobile app (login page QR + /app download page)
    'driver_app' => [
        // App Store or TestFlight URL — required for iOS installs
        'ios_url' => env('DRIVER_APP_IOS_URL', ''),
        // Google Play URL; leave empty to serve APK from /download/android
        'android_url' => env('DRIVER_APP_ANDROID_URL', ''),
    ],

];
