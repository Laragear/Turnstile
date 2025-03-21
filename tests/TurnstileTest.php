<?php

namespace Tests;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Laragear\Turnstile\Challenge;
use Laragear\Turnstile\Enums\SecretKey;
use Laragear\Turnstile\Enums\SiteKey;
use Laragear\Turnstile\Exceptions\InvalidChallengeException;
use Laragear\Turnstile\Turnstile;
use Laragear\Turnstile\Validation\TurnstileRule;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use function array_map;
use function now;

class TurnstileTest extends TestCase
{
    public function turnstile(): Turnstile
    {
        return $this->app->make(Turnstile::class);
    }

    protected function guzzleResponse(array $data = ['success' => true]): Response
    {
        return new Response(HttpFactory::response($data)->wait());
    }

    public static function provideNonProductionEnvironments(): array
    {
        return [
            ['testing'],
            ['staging'],
            ['non-production'],
            [false]
        ];
    }

    public function test_returns_key_from_config(): void
    {
        static::assertSame(Turnstile::KEY, $this->turnstile()->key());

        $this->app->make('config')->set('turnstile.key', 'default');

        static::assertSame('default', $this->turnstile()->key());
    }

    public function test_returns_rule_name(): void
    {
        static::assertSame(TurnstileRule::NAME, $this->turnstile()->rule());
    }

    public function test_returns_default_rule_array(): void
    {
        static::assertSame([Turnstile::KEY => TurnstileRule::NAME], $this->turnstile()->rules());
    }

    public function test_is_enabled_on_any_environment_except_false(): void
    {
        $this->app->make('config')->set('turnstile.env', 'test');

        static::assertTrue($this->turnstile()->isEnabled());
        static::assertFalse($this->turnstile()->isDisabled());
    }

    public function test_is_disabled_if_environment_is_false(): void
    {
        $this->app->make('config')->set('turnstile.env', false);

        static::assertFalse($this->turnstile()->isEnabled());
        static::assertTrue($this->turnstile()->isDisabled());
    }

    public function test_fallbacks_to_config_app_environment(): void
    {
        static::assertTrue($this->turnstile()->isEnabled());
        static::assertFalse($this->turnstile()->isDisabled());

        $this->app->instance('env', false);

        static::assertFalse($this->turnstile()->isEnabled());
        static::assertTrue($this->turnstile()->isDisabled());
    }

    public function test_retrieves_challenge_using_token(): void
    {
        $this->app->instance('env', 'not-production-nor-testing');

        $this->mock(HttpFactory::class, function (MockInterface $mock) {
            $mock
                ->expects('asJson->acceptJson->withOptions->post')
                ->with(
                    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                    ['secret' => SecretKey::Passing->value, 'response' => 'test_token'],
                )->andReturn($this->guzzleResponse());
        });

        $this->turnstile()->getChallenge('test_token');
    }

    #[DataProvider('provideNonProductionEnvironments')]
    public function test_retrieves_challenge_with_demo_secret_key_on_non_production_envs(string|false $env): void
    {
        $this->app->instance('env', $env);

        $this->mock(HttpFactory::class, function (MockInterface $mock) {
            $mock->expects('fake')->zeroOrMoreTimes();
            $mock
                ->expects('asJson->acceptJson->withOptions->post')
                ->with(
                    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                    ['secret' => SecretKey::Passing->value, 'response' => 'test_token'],
                )->andReturn($this->guzzleResponse());
        });

        $this->turnstile()->getChallenge('test_token');
    }

    public function test_retrieves_challenge_using_real_secret_key_on_production(): void
    {
        $this->app->instance('env', 'production');
        $this->app->make('config')->set('turnstile.secret_key', 'test_key');

        $this->mock(HttpFactory::class, function (MockInterface $mock) {
            $mock->expects('fake')->never();
            $mock
                ->expects('asJson->acceptJson->withOptions->post')
                ->with(
                    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                    ['secret' => 'test_key', 'response' => 'test_token'],
                )->andReturn($this->guzzleResponse());
        });

        $this->turnstile()->getChallenge('test_token');
    }

    public function test_retrieves_challenge_using_token_and_ip(): void
    {
        $this->app->instance('env', 'not-production-nor-testing');

        $this->mock(HttpFactory::class, function (MockInterface $mock) {
            $mock
                ->expects('asJson->acceptJson->withOptions->post')
                ->with(
                    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                    ['secret' => SecretKey::Passing->value, 'response' => 'test_token', 'remoteip' => '127.0.0.1'],
                )->andReturn($this->guzzleResponse());
        });

        $this->turnstile()->getChallenge('test_token', '127.0.0.1');
    }

    public function test_retrieves_challenge_using_token_and_idempotency_key(): void
    {
        $this->app->instance('env', 'not-production-nor-testing');

        $this->mock(HttpFactory::class, function (MockInterface $mock) {
            $mock
                ->expects('asJson->acceptJson->withOptions->post')
                ->with(
                    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                    ['secret' => SecretKey::Passing->value, 'response' => 'test_token', 'idempotency_key' => 'test_key'],
                )->andReturn($this->guzzleResponse());
        });

        $this->turnstile()->getChallenge('test_token', '', 'test_key');
    }

    public function test_fills_challenge_from_http_response(): void
    {
        $this->app->instance('env', 'not-production-nor-testing');

        $this->mock(HttpFactory::class, function (MockInterface $mock) {
            $mock
                ->expects('asJson->acceptJson->withOptions->post')
                ->with(
                    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                    ['secret' => SecretKey::Passing->value, 'response' => 'test_token'],
                )->andReturn($this->guzzleResponse([
                    'success' => false,
                    'action' => 'test_action',
                    'errors' => ['test_error'],
                    'metadata' => ['foo' => 'bar'],
                    'cdata' => 'test_cdata',
                    'hostname' => 'whois',
                ]));
        });

        $challenge = $this->turnstile()->getChallenge('test_token');

        static::assertFalse($challenge->successful);
        static::assertSame('test_action', $challenge->action);
        static::assertSame(['test_error'], $challenge->errors);
        static::assertSame(['foo' => 'bar'], $challenge->metadata);
        static::assertSame('test_cdata', $challenge->customerData);
        static::assertSame('test_cdata', $challenge->cData);
    }

    public function test_throws_on_server_error(): void
    {
        $this->app->instance('env', 'not-production-nor-testing');

        $this->mock(HttpFactory::class, function (MockInterface $mock) {
            $mock
                ->expects('asJson->acceptJson->withOptions->post')
                ->with(
                    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                    ['secret' => SecretKey::Passing->value, 'response' => 'test_token'],
                )->andReturn($this->guzzleResponse([
                    'success' => false,
                    'errors' => ['internal-error'],
                ]));
        });

        $this->expectException(InvalidChallengeException::class);
        $this->expectExceptionMessage('The challenge is invalid: internal-error.');

        $this->turnstile()->getChallenge('test_token');
    }

    public static function provideClientErrors(): array
    {
        return [
            ['bad-request'],
            ['timeout-or-duplicated']
        ];
    }

    #[DataProvider('provideClientErrors')]
    public function test_throws_on_backend_error(string $error): void
    {
        $this->app->instance('env', 'not-production-nor-testing');

        $this->mock(HttpFactory::class, function (MockInterface $mock) use ($error) {
            $mock
                ->expects('asJson->acceptJson->withOptions->post')
                ->with(
                    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                    ['secret' => SecretKey::Passing->value, 'response' => 'test_token'],
                )->andReturn($this->guzzleResponse([
                    'success' => false,
                    'errors' => [$error],
                ]));
        });

        $this->expectException(InvalidChallengeException::class);
        $this->expectExceptionMessage("The challenge is invalid: $error.");

        $this->turnstile()->getChallenge('test_token');
    }

    public function test_saves_challenge_into_container(): void
    {
        $this->app->instance('env', 'not-production-nor-testing');

        static::assertFalse($this->app->bound(Challenge::class));

        $this->mock(HttpFactory::class, function (MockInterface $mock) {
            $mock
                ->expects('asJson->acceptJson->withOptions->post')
                ->with(
                    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                    ['secret' => SecretKey::Passing->value, 'response' => 'test_token'],
                )->andReturn($this->guzzleResponse());
        });

        $this->turnstile()->getChallenge('test_token');

        static::assertTrue($this->app->bound(Challenge::class));
    }

    public function test_saves_failed_challenge_into_container(): void
    {
        $this->app->instance('env', 'not-production-nor-testing');

        static::assertFalse($this->app->bound(Challenge::class));

        $this->mock(HttpFactory::class, function (MockInterface $mock) {
            $mock
                ->expects('asJson->acceptJson->withOptions->post')
                ->with(
                    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                    ['secret' => SecretKey::Passing->value, 'response' => 'test_token'],
                )->andReturn($this->guzzleResponse(['success' => false]));
        });

        $this->turnstile()->getChallenge('test_token');

        static::assertTrue($this->app->bound(Challenge::class));
    }

    public function test_get_challenge_from_current_request(): void
    {
        $this->app->instance('env', 'not-production-nor-testing');

        $this->app->instance(
            'request', Request::create($this->prepareUrlForRequest('/'), 'POST', [Turnstile::KEY => 'test_token'])
        );

        $this->mock(HttpFactory::class, function (MockInterface $mock) {
            $mock
                ->expects('asJson->acceptJson->withOptions->post')
                ->with(
                    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                    ['secret' => SecretKey::Passing->value, 'response' => 'test_token', 'remoteip' => '127.0.0.1'],
                )->andReturn($this->guzzleResponse());
        });

        $challenge = $this->turnstile()->getChallengeFromRequest();

        static::assertTrue($challenge->successful);
    }

    public function test_gets_challenge_from_request_instance(): void
    {
        $this->app->instance('env', 'not-production-nor-testing');

        $this->mock(HttpFactory::class, function (MockInterface $mock) {
            $mock
                ->expects('asJson->acceptJson->withOptions->post')
                ->with(
                    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                    ['secret' => SecretKey::Passing->value, 'response' => 'test_token', 'remoteip' => '127.0.0.1'],
                )->andReturn($this->guzzleResponse());
        });

        $challenge = $this->turnstile()->getChallengeFromRequest(
            Request::create($this->prepareUrlForRequest('/'), 'POST', [Turnstile::KEY => 'test_token'])
        );

        static::assertTrue($challenge->successful);
    }

    public function test_fakes_a_successful_challenge(): void
    {
        $this->turnstile()->fake();

        $challenge = $this->turnstile()->getChallenge('test', '127.0.0.1');

        static::assertTrue($challenge->successful);
        static::assertSame('localhost', $challenge->hostname);
    }

    public function test_fakes_a_challenge_with_custom_attributes(): void
    {
        $this->turnstile()->fake([
            'success' => false,
            'action' => 'test_action',
            'errors' => ['test_error'],
            'metadata' => ['foo' => 'bar'],
            'cdata' => 'test_cdata',
            'hostname' => 'whois',
        ]);

        $challenge = $this->turnstile()->getChallenge('test', '127.0.0.1');

        static::assertFalse($challenge->successful);
        static::assertSame('test_action', $challenge->action);
        static::assertSame(['test_error'], $challenge->errors);
        static::assertSame(['foo' => 'bar'], $challenge->metadata);
        static::assertSame('test_cdata', $challenge->customerData);
        static::assertSame('test_cdata', $challenge->cData);
    }

    public function test_doesnt_fakes_challenge_outside_testing(): void
    {
        $this->app->instance('env', 'not-production-nor-testing');

        $this->mock(HttpFactory::class, function (MockInterface $mock) {
            $mock->expects('fake')->never();
            $mock->expects('asJson->acceptJson->withOptions->post')
                ->andReturn(new Response(HttpFactory::response(['success' => false, 'cdata' => 'foo'])->wait()));
        });

        $challenge = $this->turnstile()->getChallenge('test', '127.0.0.1');

        static::assertFalse($challenge->successful);
        static::assertSame('localhost', $challenge->hostname);
        static::assertSame('foo', $challenge->customerData);
    }

    public function test_fakes_challenge_if_disabled(): void
    {
        $this->app->make('config')->set('turnstile.env', false);

        $this->partialMock(HttpFactory::class, function (MockInterface $mock) {
            $mock->expects('fake')->once();
        });

        static::assertTrue($this->turnstile()->getChallenge('test', '127.0.0.1')->successful);
    }

    public function test_fakes_challenge_on_testing(): void
    {
        $this->app->instance('env', 'not-production-nor-testing');
        $this->app->make('config')->set('turnstile.env', 'testing');

        $this->partialMock(HttpFactory::class, function (MockInterface $mock) {
            $mock->expects('fake')->once();
        });

        static::assertTrue($this->turnstile()->getChallenge('test', '127.0.0.1')->successful);
    }

    public function test_second_faked_response_is_duplicated_error(): void
    {
        static::assertTrue($this->turnstile()->getChallenge('test', '127.0.0.1')->successful);

        $challenge = $this->turnstile()->getChallenge('test', '127.0.0.1');

        static::assertFalse($challenge->successful);
        static::assertSame(['timeout-or-duplicate'], $challenge->errors);
    }

    public function test_returns_the_site_key(): void
    {
        $this->app->make('config')->set('turnstile.site_key', 'test_key');

        static::assertSame('test_key', $this->turnstile()->getSiteKey());
    }

    #[DataProvider('provideNonProductionEnvironments')]
    public function test_returns_demo_site_key_on_non_production(string|false $environment): void
    {
        $this->app->instance('env', $environment);

        static::assertSame(SiteKey::VisiblePassing->value, $this->turnstile()->getSiteKey());
    }

    public function test_retrieves_challenge_from_container(): void
    {
        $challenge = $this->app->instance(Challenge::class, new Challenge('true', '', '', '', [], [], now()));

        static::assertSame($challenge, $this->turnstile()->challenge());
    }

    public function test_throws_if_challenge_not_in_container(): void
    {
        $this->expectException(BindingResolutionException::class);

        $this->turnstile()->challenge();
    }

    public function test_challenge_presence(): void
    {
        static::assertFalse($this->turnstile()->hasChallenge());
        static::assertTrue($this->turnstile()->missingChallenge());

        $this->app->instance(Challenge::class, new Challenge('true', '', '', '', [], [], now()));

        static::assertTrue($this->turnstile()->hasChallenge());
        static::assertFalse($this->turnstile()->missingChallenge());
    }

    public function test_flushes_challenge(): void
    {
        $this->app->instance(Challenge::class, new Challenge('true', '', '', '', [], [], now()));

        static::assertTrue($this->turnstile()->hasChallenge());

        $this->turnstile()->flushChallenge();

        static::assertFalse($this->turnstile()->hasChallenge());
    }

    public static function provideTestingSiteKeys(): array
    {
        return array_map(Arr::wrap(...), SiteKey::cases());
    }

    #[DataProvider('provideTestingSiteKeys')]
    public function test_uses_testing_site_key(SiteKey $key): void
    {
        $this->turnstile()->useTestingSiteKey($key);

        static::assertSame($key->value, $this->turnstile()->getSiteKey());
    }

    public static function provideTestingSecretKeys(): array
    {
        return array_map(Arr::wrap(...), SecretKey::cases());
    }

    #[DataProvider('provideTestingSecretKeys')]
    public function test_uses_testing_secret_key(SecretKey $key): void
    {
        $this->app->instance('env', 'not-production-nor-testing');

        $this->mock(HttpFactory::class, function (MockInterface $mock) use ($key): void {
            $mock
                ->expects('asJson->acceptJson->withOptions->post')
                ->with(
                    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                    ['secret' => $key->value, 'response' => 'test_token'],
                )->andReturn($this->guzzleResponse());
        });

        $this->turnstile()->useTestingSecretKey($key)->getChallenge('test_token');
    }
}
