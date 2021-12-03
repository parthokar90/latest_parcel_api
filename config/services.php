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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'firebase' => [
        'apiKey' => 'AIzaSyAm0y_pq6kqLRZUbVwKIunTanW_lhmS1v4',
        'authDomain' => 'parcel-magic-notification.firebaseapp.com',
        'databaseURL' => 'https://parcel-magic-notification-default-rtdb.asia-southeast1.firebasedatabase.app',
        'projectId' => 'parcel-magic-notification',
        'storageBucket' => 'parcel-magic-notification.appspot.com',
        'messagingSenderId' => 256910940868,
        'appId' => '1:256910940868:web:afb8d6848ebd2818174791',
        'measurementId' => 'G-JEKMLG1X7K',
    ],

];
