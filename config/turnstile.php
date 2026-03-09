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
    | the request to Turnstile servers. By default, we use HTTP/3 (QUIC) for
    | the cURL request, with a graceful fallback to 2.0 in any other cases.
    |
    | @see https://docs.guzzlephp.org/en/stable/request-options.html
    */

    'client' => array_merge_recursive([
        \GuzzleHttp\RequestOptions::VERSION => 2.0,
        // ...Add your config here.
    ],
        // This will detect if your cURL version has HTTP/3 support and prioritize it.
        defined('CURL_VERSION_HTTP3') && curl_version()['features'] & CURL_VERSION_HTTP3
            ? ['curl' => [CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_3]]
            : [],
    ),

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

    /*
    |--------------------------------------------------------------------------
    | Interstitial Middleware
    |--------------------------------------------------------------------------
    |
    | When using the Interstitial middleware this config will handle the view
    | that will be shown for the challenge, the controller that will receive
    | the response token, and the session key name to store the completion.
    |
    | When "global" is set to true, it will be registered site-wide.
    |
    */

    'interstitial' => [
        'key' => '_turnstile.interstitial',
        'view' => 'turnstile::interstitial',
        'route' => 'turnstile.interstitial',
        'duration' => true,
    ],
];
