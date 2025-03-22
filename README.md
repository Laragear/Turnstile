# Turnstile
[![Latest Version on Packagist](https://img.shields.io/packagist/v/laragear/turnstile.svg)](https://packagist.org/packages/laragear/turnstile)
[![Latest stable test run](https://github.com/Laragear/Turnstile/workflows/Tests/badge.svg)](https://github.com/Laragear/Turnstile/actions)
[![Codecov coverage](https://codecov.io/gh/Laragear/Turnstile/branch/1.x/graph/badge.svg?token=5U6BJUEA4T)](https://codecov.io/gh/Laragear/Turnstile)
[![Maintainability](https://qlty.sh/badges/c82a8142-06a9-4700-8eee-b6bab1e69087/maintainability.svg)](https://qlty.sh/gh/Laragear/projects/Turnstile)
[![Sonarcloud Status](https://sonarcloud.io/api/project_badges/measure?project=Laragear_Turnstile&metric=alert_status)](https://sonarcloud.io/dashboard?id=Laragear_Turnstile)
[![Laravel Octane Compatibility](https://img.shields.io/badge/Laravel%20Octane-Compatible-success?style=flat&logo=laravel)](https://laravel.com/docs/11.x/octane#introduction)

Use Cloudflare's no-CAPTCHA alternative in your Laravel application.

```php
use Illuminate\Support\Facades\Route;

Route::post('login', function () {
    // ...
})->middleware('turnstile');
```

## Become a sponsor

[![](.github/assets/support.png)](https://github.com/sponsors/DarkGhostHunter)

Your support allows me to keep this package free, up-to-date and maintainable. Alternatively, you can **spread the word on social media!**.

## Requirements

* Laravel 11 or later

## Installation

You can install the package via Composer:

```bash
composer require laragear/turnstile
```

## Setup

This library comes already with the **official demonstration keys** to start developing your application with Cloudflare Turnstile immediately.

Once in **production**, you will require real keys, both of them obtainable through your [Cloudflare Dashboard](https://dash.cloudflare.com), and set as [environment variables](#credentials):

```dotenv
TURNSTILE_SITE_KEY=...
TURNSTILE_SECRET_KEY=...
```

## Frontend integration

This library comes with two [Blade Components](https://laravel.com/docs/12.x/blade#components) to easy your development pain: `<x-turnstile::script />` and `<x-turnstile::widget />`.

### Script

You can use the `<x-turnstile::script />` Blade Component to implement the Cloudflare Turnstile script in your `<head>` tag of your HTML view.

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ... -->
    <title>My application</title>
    
    <x-turnstile::script />
</head>
<body>
  ...
</body>
</html>
```

The script will render a `<script>` tag using `async` and `defer` by default. You can set any of these to `false`.

```blade
<x-turnstile::script :async="false" :defer="false" />
```

You may also set `explicit` to `true` to make widgets be rendered only explicitly by your frontend JavaScript.

```blade
<x-turnstile::script :explicit="true" />
```

Finally, you can also set a custom callback name to be executed once the script is completely loaded in your frontend, especially if you're using explicit rendering, with the `onload` attribute.

```blade
<x-turnstile::script :explicit="true" onload="renderAllWidgets" />
```

### Widget

> [!IMPORTANT]
> 
> Remember that the Widget Mode is controlled via your [Cloudflare Dashboard](https://dash.cloudflare.com), not here. On development, this is controlled with [testing keys](#testing-keys).

You can use the `<x-turnstile::widget />` Blade Component to add the Turnstile Widget in your forms. Depending on the Widget Mode, the Widget may render as usual or be invisible at Turnstile discretion.

```blade
<form id='login' method="POST">
    @csrf
    <input type="email" name="email">
    <input type="password" name="password">
    
    <x-turnstile::widget />
    
    <button type="submit">
        Login
    </button>
</form>
```

You can pass HTML attributes and [data attributes](https://developers.cloudflare.com/turnstile/get-started/client-side-rendering/) to change the widget behavior. For example, you can use the `data-action` to differentiate multiple widgets in your application, or `data-error-callback` to execute a JavaScript function in your frontend if the challenge fails.

```bladehtml
<x-turnstile::widget class="shadow-lg" data-action="auth-login" data-error-callback="tryAgain" />
```

```html
<div
    class="cf-turnstile shadow-lg"
    data-sitekey="..."
    data-action="auth-login"
    data-error-callback="tryAgain"
></div>
```

> [!TIP]
>
> Classes are automatically appended, so you shouldn't worry about overwriting the `cf-turnstile` class used by the Widget to render.

### Retrieving the Site Key

If you're using a custom JavaScript frontend, you may require your Site Key available _somewhere_ so the frontend library can use it. You may do that by just simply using the `siteKey()` method of the `Turnstile` facade inside the `<meta>` header in your main HTML view.

```blade
@php
    use Laragear\Turnstile\Facades\Turnstile;
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My awesome app</title>
    <meta name="turnstile-site-key" content="{{ Turnstile::sitekey() }}">
</head>
<body>
    // ...
</body>
</html>
```

Then later you will be able to retrieve the site key through Javascript.

```vue
<script setup>
const siteKey = document.querySelector('meta[name="test-tag"]').content;

// ...
</script>

<template>
    <div class="cf-turnstile shadow-lg" :data-sitekey="siteKey"></div>
</template>
```

## Backend integration

When issuing a form, you have three alternatives to ensure the Turnstile challenge is valid and successful, from the easiest to the more flexible:

- Use the [`TurnstileRequest` request](#validating-with-request) on the controller action.
- Use the [`turnstile` middleware](#validating-with-middleware) on the route.
- Use the [`turnstile` rule](#validating-with-rule) on the Request validation.
- Manually [retrieve the Challenge](#validating-manually).

> [!WARNING]
> 
> All methods will fail on server-side errors:
>
> - The Cloudflare Turnstile servers are unreachable.
> - The request to Cloudflare Turnstile servers is malformed.
> - The token is duplicated or had a timeout.
> 
> Connection problems will always throw an exception.

### Validating with Request

The easiest and less intrusive way to check the Turnstile challenge is to use the `Laragear\Turnstile\Http\Requests\TurnstileRequest` instance in your controller. This is great if you only have a few controllers where you want to check for a successful Turnstile Challenge.

```php
use App\Models\Comment;
use Illuminate\Support\Facades\Route;
use Laragear\Turnstile\Http\Requests\TurnstileRequest;

Route::post('comment', function (TurnstileRequest $request) {
    $request->validate([
        'body' => 'required|string'
    ]);
    
    return Comment::create($request->only('body'));
})
```

You can have access to the Cloudflare Turnstile Challenge object through the `challenge()` method. For example, you may use it to double-check if the action is equal to something you expect.

```php
use App\Models\Comment;
use Illuminate\Support\Facades\Route;
use Laragear\Turnstile\Http\Requests\TurnstileRequest;

Route::post('comment', function (TurnstileRequest $request) {
    $request->validate([
        'body' => 'required|string'
    ]);
    
    if ($request->challenge()->isAction('comment:store')) {
        return back()->withErrors('Invalid action');
    }
    
    return Comment::create($request->only('body'));
})
```

> [!IMPORTANT]
> 
> The Request will check for the `cf-turnstile-response` key [by default](#form-key), plus a successful Challenge. If you need more fine-tuning, consider using the [middleware](#validating-with-middleware), [rule](#validating-with-rule), or [validating manually](#validating-manually).

#### Extending the Form Request

If you need to create a form request and also validate the Turnstile Challenge, you may safely extend the `TurnstileRequest` instead. The class runs the validation _before_ your form request authorization and rules.

```php
namespace App\Http\Requests;

use Laragear\Turnstile\Http\Requests\TurnstileRequest;

class CommentStoreRequest extends TurnstileRequest
{
    public function rules(): array
    {
        return [
            'body' => 'required|string'
        ];
    }
}
```

This means your controller can safely retrieve the validated data using `$request->validated()`.

```php
use App\Models\Comment;
use Illuminate\Support\Facades\Route;
use App\Http\Requests\CommentStoreRequest;

Route::post('comment', function (CommentStoreRequest $request) {
    return Comment::create($request->validated());
})
```

### Validating with Middleware

The `turnstile` middleware is a great way to check if a form submission contains a successful challenge. Simply add the middleware to the route (or group of routes) that receive the form submission, like a `POST`, `PUT` or `PATCH`. 

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('comment', function (Request $request) {
    // ...
})->middleware('turnstile');
```

> [!NOTE]
> 
> Is not suggested to use the middleware on `GET` methods or similar. Some browsers (or extensions) may _cache_ or _inspect_ ahead document links. 

If you want to configure the middleware behaviour, you should use the `TurnstileMiddleware` class and the static methods.

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laragear\Turnstile\Http\Middleware\TurnstileMiddleware;

Route::post('comment', function (Request $request) {
    // ...
})->middleware(TurnstileMiddleware::acceptFailed())
```

#### Custom challenge key

The middleware will check for the `cf-turnstile-response` key set in the form or JSON, [by default](#form-key). If you have edited your frontend to use another key, use the `input()` method of the middleware class with the key name.

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laragear\Turnstile\Http\Middleware\TurnstileMiddleware;

Route::post('comment', function (Request $request) {
    // ...
})->middleware(TurnstileMiddleware::input('my-response-token-key'))
```

#### Middleware bypass when authenticated

You can configure the authentication guards to bypass the challenge requirement if the user is authenticated through the `auth()` method.

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laragear\Turnstile\Http\Middleware\TurnstileMiddleware;

Route::post('comment', function (Request $request) {
    // ...
})->middleware(TurnstileMiddleware::auth());
```

By default, it will check the default authentication guard of your application. You may set specific guards by just naming them.

```php
use Laragear\Turnstile\Http\Middleware\TurnstileMiddleware;

TurnstileMiddleware::auth('admin');
```

To complement this, you should add the [widget](#widget) to your forms only if user is a guest for the given guards.

```blade
<form id='login' method="POST">
    <input type="email" name="email">
    <input type="password" name="password">
    
    @guest('admin')
      <x-turnstile::widget />    
    @endguest
    
    <button type="submit">
        Login
    </button>
</form>
```

#### Middleware accepts failed challenges

You can allow the route to continue even if the challenge failed using the `acceptFailed()` method.

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laragear\Turnstile\Http\Middleware\TurnstileMiddleware;

Route::post('comment', function (Request $request) {
    // ...
})->middleware(TurnstileMiddleware::acceptFailed());
```

#### Middleware checks action

If you have multiple Cloudflare Turnstile widgets in your application, and you have separated them through actions names, you can add a check to match the action name in the backend. If the action doesn't match, a validation exception will be thrown.

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laragear\Turnstile\Http\Middleware\TurnstileMiddleware;

Route::post('comment', function (Request $request) {
    // ...
})->middleware(TurnstileMiddleware::action('comment:store'));
```

### Validating with Rule

You can use the `turnstile` rule to check if the Turnstile challenge is present and is successful. The easiest way is to unpack the default rule contained in the `rules()` method of the `Turnstile` facade.

```php
use Illuminate\Http\Request;
use Laragear\Turnstile\Facades\Turnstile;

public function create(Request $request)
{
    $request->validate([
        'comment' => 'required|string',
        // 'cf-turnstile-response' => 'turnstile',
        ...Turnstile::rules()
    ]);
        
    // ...
}
```

For more granular control, you can use the `key` method of the `Turnstile` facade to use the [default key](#form-key)that the Cloudflare Turnstile script injects into the form, and put your own additional validation rules if necessary. 

```php
use App\Rules\MyCustomRule;
use Illuminate\Http\Request;
use Laragear\Turnstile\Facades\Turnstile;

public function create(Request $request)
{
    $request->validate([
        // ...
        Turnstile::key() => ['turnstile', new MyCustomRule],
    ]);
        
    // ...
}
```

#### Rule bypass when authenticated

If you want to bypass the rule check if the user is authenticated, set the `auth` parameter on the rule.

```php
use Illuminate\Http\Request;
use Laragear\Turnstile\Facades\Turnstile;

public function create(Request $request)
{
    $request->validate([
        Turnstile::key() => 'turnstile:auth',
    ]);
        
    // ...
}
```

You may also add a list of guards to check by adding them after `=` and separating them by `,`.

```php
$request->validate([
    Turnstile::key() => 'turnstile:auth=admin,developer',
]);
```

#### Rule accepts failed challenges 

The rule supports not checking if the challenge is successful by setting the `accept-failed` parameter. This can be useful to retrieve the response later and programmatically continue based on the response result through the `sucess()` and `failed()` methods of the `Turnstile` facade.

```php
use Illuminate\Http\Request;
use Laragear\Turnstile\Facades\Turnstile;

public function create(Request $request)
{
    $request->validate([
        Turnstile::key() => 'required|turnstile:accept-failed'
    ]);
        
    if (Turnstile::success()) {
        // ...
    }
}
```

### Validating Manually

> [!IMPORTANT]
>
> The challenge is automatically retrieved by the [request](#validating-with-request), [middleware](#validating-with-middleware) and [rule](#validating-with-rule). If that's case, you may [use the `challenge()` method](#retrieving-an-already-received-challenge).

To validate the Challenge manually, first you require the Turnstile Response Token that is sent by the frontend, and optionally the IP of the Request.

Once identified, you should use the `getChallenge()` method of `Turnstile` facade to retrieve the Challenge from Cloudflare Turnstile servers.

You will receive a `Laragear\Turnstile\Challenge` instance with some useful helpers to check the challenge status.

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laragear\Turnstile\Facades\Turnstile;

Route::post('comment', function (Request $request) {
    $challenge = Turnstile::getChallenge(
        $request->input('cf-turnstile-response'), $request->ip()
    );
    
    if ($challenge->failed || $challenge->isNotAction('comment:store')) {
        // ... throw an exception.
    }
    
    // ... save the comment.
})
```

Alternatively, if you're already using the default configuration, you can just use `getChallengeFromRequest()` which will automatically resolve the Request from the Container and find the token using the [default key name](#form-key).

```php
use Laragear\Turnstile\Facades\Turnstile;

$challenge = Turnstile::getChallengeFromRequest();
```

Once the challenge is retrieved, is saved into the Application Container. This makes easier to retrieve the challenge elsewhere in your application. If you don't want to save the Challenge, set the `save` parameter to `false`.

```php
use Laragear\Turnstile\Facades\Turnstile;

$challenge = Turnstile::getChallenge('token', save: false);
```

### Idempotency Keys

Because Cloudflare Turnstile Siteverify API will return an error when retrieving the same Challenge more than once, an idempotency key can be used in case of duplicate submissions.

How idempotency is handled will be up to your application. While most of the time is not needed at all, on some frontends the token may be resent anyway. To avoid errors, you can add a UUID string to both `getChallenge()` and `getChallengeFromRequest()` methods of the `Turnstile` facade.

```php
use App\Uuid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laragear\Turnstile\Challenge;
use Laragear\Turnstile\Facades\Turnstile;

Route::post('comment', function (Request $request) {
    // Generate a UUID using the hash of the input as the seed.
    $uuid = Uuid::generateWithSeed(md5(json_encode($request->input('body')));

    Turnstile::getChallengeFromRequest(idempotencyKey: $uuid);
        
    // ...
});
```

### Getting the correct client IP

If you're under a Cloudflare Proxy, can get the correct client IP through [the `CF-Connecting-IP` header](https://developers.cloudflare.com/fundamentals/reference/http-headers/#cf-connecting-ip). This is set as a constant in the `Turnstile` class, so you can use it when retrieving the challenge:

```php
use App\Uuid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laragear\Turnstile\Challenge;use Laragear\Turnstile\Turnstile;

Route::post('comment', function (Request $request, Turnstile $turnstile) {
    $challenge = $turnstile->getChallenge(
        $request->input($turnstile->key()),
        $request->header(Turnstile::HEADER)
    );
        
    // ...
});
```

### Retrieving the Challenge on failure

If there is a server or backend error, the challenge retrieval will fail. If you still want to proceed, you may capture the exception and retrieve the Challenge with a try-catch block.

```php
use Laragear\Turnstile\Exceptions\InvalidChallengeException;use Laragear\Turnstile\Facades\Turnstile;

try {
    $challenge = Turnstile::getChallenge();
} catch (InvalidChallengeException $exception) {
    $challenge = $exception->getChallenge();
}
```

## Retrieving an already received Challenge

The `challenge()` method of the `Turnstile` facade can be used to retrieve an already saved Turnstile Challenge inside the Application Container.

```php
use Laragear\Turnstile\Facades\Turnstile;

$challenge = Turnstile::challenge();
```

If you're not sure if the Challenge was received and saved, you can use both `hasChallenge()` and `missingChallenge()` beforehand.

```php
use Laragear\Turnstile\Facades\Turnstile;

if (Turnstile::missingChallenge()) {
    return Turnstile::getChallengeFromRequest(); 
}

return Turnstile::challenge();
```

Alternatively, you can use both `success()` and `failed()` methods to check if the challenge is successful or has failed, respectively. Of course, these must be invoked **after the challenge have been retrieved**.

```php
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laragear\Turnstile\Facades\Turnstile;

Route::middleware('turnstile:accept-failed')
    ->post('comment', function (Request $request) {
        $request->validate([
            'body' => 'required|string',
        ])
        
        $comment = Comment::make($request->only('body'));
        
        // If the challenge is successful, show the comment.
        if (Turnstile::success()) {
            $comment->approved_at = now();
        }
        
        $comment->save();
        
        return back();
    });
```

Finally, you can always inject the `Laragear\Tunrstile\Challenge` anywhere in your application. For example, in your route controller action.

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laragear\Turnstile\Challenge;

Route::middleware('turnstile')
    ->post('comment', function (Request $request, Challenge $challenge) {
        if ($challenge->isAction('comment:store')) {
            // ....
        }
        
        // ...
    });
```

## Advanced configuration

Laragear Turnstile is intended to work out-of-the-box, but you can publish the configuration file for fine-tuning the Challenge verification.

```bash
php artisan vendor:publish --provider="Laragear\Turnstile\TurnstileServiceProvider" --tag="config"
```

You will get a config file with this array:

```php
<?php

return [
    'env' => env('TURNSTILE_ENV', env('APP_ENV')),
    'key' => \Laragear\Turnstile\Turnstile::KEY,
    'client' => [
        \GuzzleHttp\RequestOptions::VERSION => 1.1,
    ],
    'site_key' => env('TURNSTILE_SITE_KEY'),
    'secret_key' => env('TURNSTILE_SECRET_KEY'),
];
``` 

### Environment

```php
return [
    'env' => env('TURNSTILE_ENV'),
];
```

This sets which environment the library should run as. When `null`, it will mirror your current application environment.

- On `production`, you will require your Site Key and Secret Key from Cloudflare Turnstile.
- On `testing`, challenges will be faked regardless, no keys needed.
- On the rest of environments, [testing keys](#testing-keys) will be automatically injected.

If you set `false` as the environment value, both [script](#script) and [widget](#widget) won't be rendered, and the [request](#validating-with-request), [middleware](#validating-with-middleware) and [rule](#validating-with-rule) won't retrieve challenges.

> [!WARNING]
> 
> When using [manual validation](#validating-manually) with the environment set as `false`, you will receive a successful fake Challenge.

### Form Key

```php
return [
    'key' => \Laragear\Turnstile\Turnstile::KEY,
];
```

This sets the default key to check for in the Request for the Turnstile response. By default, is `cf-turnstile-response`, but if you're using a custom frontend, you may change it here.

### HTTP Client options

```php
return [
    'client' => [
        \GuzzleHttp\RequestOptions::VERSION => 1.1,
    ],
];
```

This array sets the options for the outgoing request to Cloudflare Turnstile servers. [This is handled by Guzzle](https://docs.guzzlephp.org/en/stable/request-options.html), which in turn will pass it to the underlying transport. Depending on your system, it will probably be cURL.

By default, it instructs Guzzle to use HTTP/1.1, but [you can upgrade if available in your platform](#http2-or-http3-and-curl).

### Credentials

```php
return [
    'site_key' => env('TURNSTILE_SITE_KEY'),
    'secret_key' => env('TURNSTILE_SECRET_KEY'),
];
```

Here is the full array of Turnstile Site Key (public) and Secret Key (private) to use. These can be obtained through your [Cloudflare Dashboard](https://dash.cloudflare.com). Do not change the array unless you know what you're doing. If you want to set your keys, use the environment variables instead:

```dotenv
TURNSTILE_SITE_KEY=...
TURNSTILE_SECRET_KEY=...
```

## Testing

On testing, or when the [environment is `testing`](#environment), the library will automatically fake successful Challenges without contacting Cloudflare Turnstile servers.

You may create your own fake challenges easily using the `fake()` method of the `Tunrstile` facade. It accepts any of the [Turnstile Challenge attributes](https://developers.cloudflare.com/turnstile/get-started/server-side-validation/#accepted-parameters), which is great to test multiple responses from Turnstile in your application.

```php
use Laragear\Turnstile\Facades\Turnstile;

public function test_comment_is_moderated_when_bot_detected()
{
    Turnstile::fake([
        'success' => false
    ]);
    
    $this->post(['comment' => 'test_comment']);
    
    $this->assertDatabaseHas('comments', [
        'body' => 'test_comment',
        'is_moderated' => true    
    ])
}
```

#### Testing keys

If you want to swap keys to test challenges from the Cloudflare Turnstile servers, use the `useTestingSiteKey()` and `useTestingSecretKey()` methods of the `Turnstile` facade, along with the keys of your choice.

Both [testing site keys and secret keys](https://developers.cloudflare.com/turnstile/troubleshooting/testing/) can be picked at `Laragear\Turnstile\Enums\SiteKey` and `Laragear\Turnstile\Enums\SecretKey`, respectively.

```php
use Laragear\Turnstile\Enums\SiteKey;
use Laragear\Turnstile\Enums\SecretKey;
use Laragear\Turnstile\Facades\Turnstile;

Turnstile::useTestingSiteKey(SiteKey::ForceInteraction);
Turnstile::useTestingSecretKey(SecretKey::Fails);
```

For the case of the [widget](#widget), you can change the site key using the `site-key` attribute with the enum case name as value, in either `kebab-case`, `snake_case`, `camelCase` or `StudlyCaps` (these are normalized for you).

```blade
<x-turnstile::widget site-key="force-interaction" />
```

## Laravel Octane compatibility

- There are no singletons using a stale application instance.
- There are no singletons using a stale config instance.
- There are no singletons using a stale request instance.
- There are no static properties written during a request.

There should be no problems using this package with Laravel Octane as intended.

## HTTP/2 or HTTP/3 and cURL

To use HTTP/3, [ensure you're using PHP 8.2 or later](https://php.watch/articles/php-curl-http3). cURL version  [7.66](https://curl.se/changes.html#7_66_0) supports HTTP/3, and latest PHP 8.2 uses version 7.85.

For more information about checking if your platform can make HTTP/3 requests, check this [PHP Watch article](https://php.watch/articles/php-curl-http3).

This library uses HTTP/1.1 by default to ensure backwards compatibility with PHP 8.2.

## Security

If you discover any security related issues, please [report it using the online form](https://github.com/Laragear/Turnstile/security).

# License

This specific package version is licensed under the terms of the [MIT License](LICENSE.md), at time of publishing.

[Laravel](https://laravel.com) is a Trademark of [Taylor Otwell](https://github.com/TaylorOtwell/). Copyright © 2011-2025 Laravel LLC.
