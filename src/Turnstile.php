<?php

namespace Laragear\Turnstile;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\ResponseSequence;
use Illuminate\Http\Request;
use Illuminate\Support\DateFactory;
use Laragear\Turnstile\Enums\SecretKey;
use Laragear\Turnstile\Enums\SiteKey;
use Laragear\Turnstile\Exceptions\InvalidChallengeException;
use function array_filter;
use function array_merge;
use function gethostname;
use function in_array;
use function json_encode;
use function parse_url;
use function strtolower;
use const PHP_URL_HOST;

class Turnstile
{
    /**
     * The Cloudflare Connecting IP present in the Header.
     *
     * @const string
     */
    public const HEADER = 'CF-Connecting-IP';

    /**
     * The proper name for the Cloudflare Turnstile Challenge attribute.
     *
     * @const string
     */
    public const ATTRIBUTE = 'Cloudflare Turnstile Challenge';

    /**
     * The default key where the Cloudflare Turnstile response is set.
     *
     * @const string
     */
    public const KEY = 'cf-turnstile-response';

    /**
     * The Cloudflare Turnstile Site Verify endpoint.
     *
     * @const string
     */
    protected const ENDPOINT = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * The format the date from the challenge should be parsed as.
     *
     * @const string
     */
    protected const DATETIME_FORMAT = 'Y-m-d\TH:i:s.vP';

    /**
     * Create a new Turnstile instance.
     */
    public function __construct(
        protected readonly Container $container,
        protected readonly Factory $http,
        protected readonly Repository $config,
        protected readonly DateFactory $date,
        protected Enums\SiteKey $testingSiteKey = SiteKey::VisiblePassing,
        protected Enums\SecretKey $testingSecretKey = SecretKey::Passing,
        protected array $fakedResponse = [],
        protected bool $shouldFake = false
    ) {
        //
    }

    /**
     * Use the given Testing Site Key for rendering challenges on the frontend.
     *
     * @return $this
     */
    public function useTestingSiteKey(SiteKey $key): static
    {
        $this->testingSiteKey = $key;

        return $this;
    }

    /**
     * USe the given Testing Secret Key for retrieving challenges from the backend.
     * @return $this
     */
    public function useTestingSecretKey(SecretKey $key): static
    {
        $this->testingSecretKey = $key;

        return $this;
    }

    /**
     * Returns the default key to use to check Cloudflare Turnstile responses.
     */
    public function key(): string
    {
        return $this->config->get('turnstile.key');
    }

    /**
     * Returns the default rule to check in a Validation object.
     */
    public function rule(): string
    {
        return Validation\TurnstileRule::NAME;
    }

    /**
     * Returns the array of rules to be added to a Validation rules array.
     *
     * @return array{string: string}
     */
    public function rules(): array
    {
        return [$this->key() => $this->rule()];
    }

    /**
     * Check if Turnstile should be enabled for all purposes.
     */
    public function isEnabled(): bool
    {
        return $this->currentEnvironment() !== false;
    }

    /**
     * Check if Turnstile should be disabled for all purposes.
     */
    public function isDisabled(): bool
    {
        return !$this->isEnabled();
    }

    /**
     * Returns the current environment for Turnstile.
     */
    protected function currentEnvironment(): string|false
    {
        return $this->config->get('turnstile.env') ?? $this->container->make('env');
    }

    /**
     * Retrieves a Turnstile challenge.
     *
     * @throws \Illuminate\Http\Client\ConnectionException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function getChallenge(
        string $token = '',
        string $ip = '',
        string $idempotencyKey = '',
        bool $save = true,
        array $options = [],
    ): Challenge {
        $challenge = $this->parseChallengeResponse(
            $this->getResponse($token, $ip, $idempotencyKey, $options)
        );

        // If there are server errors, or it's our fault, bail out.
        if ($challenge->isServerError() || $challenge->isBackendError()) {
            throw new InvalidChallengeException($challenge);
        }

        if ($save) {
            $this->container->instance(Challenge::class, $challenge);
        }

        return $challenge;
    }

    /**
     * Retrieves the Challenge response from Turnstile servers.
     *
     * @throws \Illuminate\Http\Client\ConnectionException
     * @throws \Illuminate\Http\Client\RequestException
     */
    protected function getResponse(string $token, string $ip, string $idempotencyKey, array $options): Response
    {
        if ($this->shouldFake()) {
            $this->http->fake($this->createFakeResponse());
        }

        return $this->http
            ->asJson()
            ->acceptJson()
            ->withOptions(array_merge($this->config->get('turnstile.client'), $options))
            ->post(static::ENDPOINT, array_filter([
                'secret' => $this->getSecretKey(),
                'response' => $token,
                'remoteip' => $ip,
                'idempotency_key' => $idempotencyKey,
            ]))
            ->throw();
    }

    /**
     * Parses the incoming Cloudflare Turnstile Siteverify HTTP Response into a Challenge instance.
     */
    protected function parseChallengeResponse(Response $response): Challenge
    {
        return new Challenge(
            $response->json('success', false),
            $response->json('hostname', 'localhost'),
            $response->json('action', ''),
            $response->json('cdata', ''),
            $response->json('metadata', []),
            $response->json('errors', []),
            $this->date->createFromFormat(
                static::DATETIME_FORMAT,
                $response->json('challenge_ts', $this->date->now()->format(static::DATETIME_FORMAT))
            ),
        );
    }

    /**
     * Returns the Challenge using the defaults and with the current Request instance.
     *
     * @throws \Illuminate\Http\Client\ConnectionException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getChallengeFromRequest(
        ?Request $request = null,
        string $key = '',
        string $idempotencyKey = '',
        array $options = [],
        bool $save = true,
    ): Challenge {
        $request ??= $this->container->get('request');

        return $this->getChallenge(
            $request->input($key ?: $this->key()), $request->ip(), $idempotencyKey, $save, $options,
        );
    }

    /**
     * Fakes the challenge to be retrieved from Cloudflare Turnstile servers.
     *
     * @param  array{success?: bool, hostname?: string, action?: string, cdata?: string, metadata?: array, errors?: array}  $response
     * @return $this
     */
    public function fake(array $response = ['success' => true]): static
    {
        $this->shouldFake = true;
        $this->fakedResponse = $response;

        return $this;
    }

    /**
     * Check if the response should be faked.
     */
    protected function shouldFake(): bool
    {
        return $this->shouldFake || in_array($this->currentEnvironment(), ['testing', false]);
    }

    /**
     * Returns the Site Key for Cloudflare Turnstile.
     */
    public function getSiteKey(): string
    {
        $key = $this->config->get('turnstile.site_key');

        if ($this->currentEnvironment() !== 'production') {
            $key = match (strtolower($key)) {
                '' => $this->testingSiteKey->value,
                strtolower(SiteKey::VisiblePassing->name) => SiteKey::VisiblePassing->value,
                strtolower(SiteKey::VisibleBlocks->name) => SiteKey::VisibleBlocks->value,
                strtolower(SiteKey::InvisiblePassing->name) => SiteKey::InvisiblePassing->value,
                strtolower(SiteKey::InvisibleBlocks->name) => SiteKey::InvisibleBlocks->value,
                default => $key,
            };
        }

        return $key;
    }

    /**
     * Return the Secret Key for Cloudflare Turnstile.
     */
    protected function getSecretKey(): string
    {
        $key = $this->config->get('turnstile.secret_key');

        if ($this->currentEnvironment() !== 'production') {
            $key = match (strtolower($key)) {
                '' => $this->testingSecretKey->value,
                strtolower(SecretKey::Passing->name) => SecretKey::Passing->value,
                strtolower(SecretKey::Fails->name) => SecretKey::Fails->value,
                strtolower(SecretKey::Spent->name) => SecretKey::Spent->value,
                default => $key,
            };
        }

        return $key;
    }

    /**
     * Retrieves an already stored Turnstile Challenge.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function challenge(): Challenge
    {
        return $this->hasChallenge()
            ? $this->container->make(Challenge::class)
            : throw new BindingResolutionException('The Turnstile Challenge has not been stored in the container.');
    }

    /**
     * Check if the Turnstile Challenge has been saved into the Container.
     */
    public function hasChallenge(): bool
    {
        return $this->container->bound(Challenge::class);
    }

    /**
     * Check if the Turnstile Challenge is not saved inside the Container.
     */
    public function missingChallenge(): bool
    {
        return !$this->hasChallenge();
    }

    /**
     * Removes the Challenge from the Container.
     */
    public function flushChallenge(): void
    {
        unset($this->container[Challenge::class]); // @phpstan-ignore-line
    }

    /**
     * Fake a response to be received as challenge response.
     *
     * @return \Illuminate\Http\Client\ResponseSequence[]
     */
    protected function createFakeResponse(): array
    {
        $date = $this->date->now()->subSecond()->format(static::DATETIME_FORMAT);
        $hostname = parse_url($this->config->get('app.url'), PHP_URL_HOST) ?: gethostname();

        // The first challenge should be faked. The next ones should be duplicate error.
        return [
            'challenges.cloudflare.com/turnstile/v0/siteverify' => (new ResponseSequence([]))
                ->push(
                    array_merge([
                        'success' => true,
                        'hostname' => $hostname,
                        'challenge_ts' => $date,
                    ], $this->fakedResponse),
                )
                ->whenEmpty(
                    Create::promiseFor(
                        new Psr7Response(200, ['Content-Type' => 'application/json'], json_encode(array_merge([
                            'hostname' => $hostname,
                            'action' => '',
                            'cdata' => '',
                            'metadata' => [],
                        ], $this->fakedResponse, [
                            'success' => false,
                            'errors' => ['timeout-or-duplicate'],
                            'challenge_ts' => $date,
                        ]))),
                    ),
                ),
        ];
    }
}
