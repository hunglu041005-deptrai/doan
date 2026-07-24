<?php
// Social Login Configuration
return [
    'google' => [
        'client_id' => 'YOUR_GOOGLE_CLIENT_ID',
        'client_secret' => 'YOUR_GOOGLE_CLIENT_SECRET',
        'redirect_uri' => 'http://localhost/badminton-booking/auth/google-callback.php'
    ],
    'facebook' => [
        'app_id' => 'YOUR_FACEBOOK_APP_ID',
        'app_secret' => 'YOUR_FACEBOOK_APP_SECRET',
        'redirect_uri' => 'http://localhost/badminton-booking/auth/facebook-callback.php'
    ]
];
?>