<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | The Turnstile middleware supports three environments: production, local
    | and testing. On production, you should use your secret key. On local,
    | the token is bypassed. On testing, the response challenge is faked.
    |
    | Setting this to "false" will disable it completely.
    |
    */

    'env' => env('TURNSTILE_ENV'),

    /*
    |--------------------------------------------------------------------------
    | Key
    |--------------------------------------------------------------------------
    |
    | By default, the library will check for the Cloudflare Turnstile response
    | token using this key name in the Request. If you have a custom frontend
    | that uses another key name, you can change this default key name here.
    |
    */

    'key' => \Laragear\Turnstile\Turnstile::KEY,

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    |
    | This array is passed down to the underlying HTTP Client which will make
    | the request to Turnstile servers. By default, it will use HTTP/1.1 for
    | the request. You can change, remove or add more options in the array.
    |
    | @see https://docs.guzzlephp.org/en/stable/request-options.html
    */

    'client' => [
        \GuzzleHttp\RequestOptions::VERSION => 1.1, // You may test 3.0 in your platform.
    ],

    /*
    |--------------------------------------------------------------------------
    | Credentials
    |--------------------------------------------------------------------------
    |
    | This holds the site key (frontend) and secret key (backend). The site key
    | will be used to generate the challenge token, and the secret key will be
    | used to retrieve the challenge result from Turnstile from your backend.
    |
    */

    'site_key' => env('TURNSTILE_SITE_KEY'),
    'secret_key' => env('TURNSTILE_SECRET_KEY'),
];
