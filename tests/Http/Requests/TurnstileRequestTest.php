<?php

namespace Tests\Http\Requests;

use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Laragear\Turnstile\Challenge;
use Laragear\Turnstile\Http\Requests\TurnstileRequest;
use Laragear\Turnstile\Turnstile;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Throwable;
use function json_encode;
use function method_exists;
use function json_encode;

class TurnstileRequestTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->post('test', static function (TurnstileRequest $request): bool {
            return $request->challenge()->success;
        });
    }

    public function test_request_passes(): void
    {
        $this->expectNotToPerformAssertions();

        TurnstileRequest::create('/', 'POST', [Turnstile::KEY => 'test_key'])
            ->setContainer($this->app)
            ->validateResolved();
    }

    public function test_request_json_passes(): void
    {
        $this->expectNotToPerformAssertions();

        TurnstileRequest::create(
            uri: '/',
            method: 'POST',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([Turnstile::KEY => 'test_key'])
        )
            ->setContainer($this->app)
            ->setRedirector($this->app->make('redirect'))
            ->validateResolved();
    }

    public static function provideInvalidValues(): array
    {
        return [
            [null],
            [true],
            [false],
            [1],
            [1.0],
            [[]],
            [''],
        ];
    }

    #[DataProvider('provideInvalidValues')]
    public function test_request_throws_validation_error_if_invalid_scalars(mixed $value): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The Cloudflare Turnstile challenge is invalid, absent, or has failed.');

        TurnstileRequest::create('/', 'POST', [Turnstile::KEY => $value])
            ->setContainer($this->app)
            ->setRedirector($this->app->make('redirect'))
            ->validateResolved();
    }

    public function test_request_throws_validation_error_on_failed_challenge(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('key')->andReturn(Turnstile::KEY);
            $mock->expects('rules')->andReturn([Turnstile::KEY => 'turnstile']);
            $mock->expects('getChallenge')->andReturn(new Challenge(
                false, '', '', '', [], [], new Carbon(),
            ));
        });

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The Cloudflare Turnstile challenge is invalid, absent, or has failed.');

        TurnstileRequest::create(uri: '/', method: 'POST', parameters: [Turnstile::KEY => 'invalid'])
            ->setContainer($this->app)
            ->setRedirector($this->app->make('redirect'))
            ->validateResolved();
    }

    public function test_request_json_throws_validation_error_on_failed_challenge(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('key')->andReturn(Turnstile::KEY);
            $mock->expects('rules')->andReturn([Turnstile::KEY => 'turnstile']);
            $mock->expects('getChallenge')->andReturn(new Challenge(
                false, '', '', '', [], [], new Carbon(),
            ));
        });

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The Cloudflare Turnstile challenge is invalid, absent, or has failed.');

        TurnstileRequest::create(uri: '/', method: 'POST', server: [
            'CONTENT_TYPE' => 'application/json',
        ], content: json_encode([Turnstile::KEY => 'invalid']))
            ->setContainer($this->app)
            ->setRedirector($this->app->make('redirect'))
            ->validateResolved();
    }

    public function test_metadata(): void
    {
        $request = TurnstileRequest::create(
            uri: '/', method: 'POST', parameters: [Turnstile::KEY => 'test_key'],
        )->setContainer($this->app);

        $request->validateResolved();

        $this->app->instance(
            Challenge::class, new Challenge(true, '', '', '', ['foo' => ['bar' => 'baz']], [], new Carbon()),
        );

        static::assertSame('baz', $request->metadata('foo.bar'));
    }

    public function test_action(): void
    {
        $request = TurnstileRequest::create(
            uri: '/', method: 'POST', parameters: [Turnstile::KEY => 'test_key'],
        )->setContainer($this->app);

        $request->validateResolved();

        $this->app->instance(
            Challenge::class, new Challenge(true, '', 'test_action', '', [], [], new Carbon()),
        );

        static::assertTrue($request->isAction('test_action'));
        static::assertFalse($request->isNotAction('test_action'));
    }

    public function test_customer_data(): void
    {
        $request = TurnstileRequest::create(
            uri: '/', method: 'POST', parameters: [Turnstile::KEY => 'test_key'],
        )->setContainer($this->app);

        $request->validateResolved();

        $this->app->instance(
            Challenge::class, new Challenge(true, '', '', 'test_cdata', [], [], new Carbon()),
        );

        static::assertTrue($request->isCustomerData('test_cdata'));
        static::assertFalse($request->isNotCustomerData('test_cdata'));
    }

    public function test_extending_form_request_validates_turnstile_before_rules(): void
    {
        $request = TestTurnstileRequest::create(
            uri: '/', method: 'POST', parameters: [Turnstile::KEY => 'test_key', 'test' => 'value'],
        )->setContainer($this->app);

        $request->validateResolved();

        static::assertSame(['test' => 'value'], $request->validated());
    }

    public function test_challenge_error_runs_before_rules(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('key')->andReturn(Turnstile::KEY);
            $mock->expects('rules')->andReturn([Turnstile::KEY => 'turnstile']);
            $mock->expects('getChallenge')->andReturn(new Challenge(
                false, '', '', '', [], [], new Carbon(),
            ));
        });

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The Cloudflare Turnstile challenge is invalid, absent, or has failed.');

        TurnstileRequest::create('/', 'POST', [Turnstile::KEY => 'invalid'])
            ->setContainer($this->app)
            ->setRedirector($this->app->make('redirect'))
            ->validateResolved();
    }

    public function test_request_json_throws_validation_error_on_failed_challenge(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('key')->andReturn(Turnstile::KEY);
            $mock->expects('rules')->andReturn([Turnstile::KEY => 'turnstile']);
            $mock->expects('getChallenge')->andReturn(new Challenge(
                false, '', '', '', [], [], new Carbon(),
            ));
        });

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The Cloudflare Turnstile challenge is invalid, absent, or has failed.');

        TurnstileRequest::create(
            uri: '/',
            method: 'POST',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([Turnstile::KEY => 'invalid'])
        )
            ->setContainer($this->app)
            ->setRedirector($this->app->make('redirect'))
            ->validateResolved();
    }

    public function test_metadata(): void
    {
        $request = TurnstileRequest::create('/', 'POST', [Turnstile::KEY => 'test_key'])->setContainer($this->app);

        $request->validateResolved();

        $this->app->instance(
            Challenge::class, new Challenge(true, '', '', '', ['foo' => ['bar' => 'baz']], [], new Carbon()),
        );

        static::assertSame('baz', $request->metadata('foo.bar'));
    }

    public function test_action(): void
    {
        $request = TurnstileRequest::create('/', 'POST', [Turnstile::KEY => 'test_key'])->setContainer($this->app);

        $request->validateResolved();

        $this->app->instance(
            Challenge::class, new Challenge(true, '', 'test_action', '', [], [], new Carbon()),
        );

        static::assertTrue($request->isAction('test_action'));
        static::assertFalse($request->isNotAction('test_action'));
    }

    public function test_customer_data(): void
    {
        $request = TurnstileRequest::create('/', 'POST', [Turnstile::KEY => 'test_key'])->setContainer($this->app);

        $request->validateResolved();

        $this->app->instance(
            Challenge::class, new Challenge(true, '', '', 'test_cdata', [], [], new Carbon()),
        );

        static::assertTrue($request->isCustomerData('test_cdata'));
        static::assertFalse($request->isNotCustomerData('test_cdata'));
    }

    public function test_extending_form_request_validates_turnstile_before_rules(): void
    {
        $request = TestTurnstileRequest::create('/', 'POST', [Turnstile::KEY => 'test_key', 'test' => 'value'])
            ->setContainer($this->app);

        $request->validateResolved();

        static::assertSame(['test' => 'value'], $request->validated());
    }

    public function test_challenge_error_runs_before_rules(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('key')->andReturn(Turnstile::KEY);
            $mock->expects('rules')->andReturn([Turnstile::KEY => 'turnstile']);
            $mock->expects('getChallenge')->andReturn(new Challenge(
                false, '', '', '', [], [], new Carbon(),
            ));
        });

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The Cloudflare Turnstile challenge is invalid, absent, or has failed.');

        $request = TestTurnstileRequest::create('/', 'POST', [Turnstile::KEY => 'invalid'])
            ->setContainer($this->app)
            ->setRedirector($this->app->make('redirect'));

        try {
            $request->validateResolved();
        } catch (ValidationException $e) {
            static::assertFalse($request->rulesWasAsked);

            throw $e;
        }
    }

    public function test_uses_default_attributes_and_messages(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('key')->andReturn(Turnstile::KEY);
            $mock->expects('rules')->andReturn([Turnstile::KEY => 'turnstile']);
            $mock->expects('getChallenge')->andReturn(new Challenge(
                false, '', '', '', [], [], new Carbon(),
            ));
        });

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('test_message test_attribute');

        $request = TestMessagesAndAttributesTurnstileRequest::create('/', 'POST', [Turnstile::KEY => 'invalid'])
            ->setContainer($this->app)
            ->setRedirector($this->app->make('redirect'));

        try {
            $request->validateResolved();
        } catch (ValidationException $e) {
            static::assertSame('test_message test_attribute', $e->validator->getMessageBag()->get(Turnstile::KEY)[0]);

            throw $e;
        }
    }

    public function test_doesnt_validates_challenge_on_precognition(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('key')->never();
            $mock->expects('rules')->never();
            $mock->expects('isDisabled')->never();
            $mock->expects('getChallenge')->never();
        });

        $request = TurnstileRequest::create('/', 'POST', [Turnstile::KEY => 'test_token']);
        $request->headers->set('Precognition-Validate-Only', 'not-turnstile');
        $request->attributes->set('precognitive', true);

        $request
            ->setContainer($this->app)
            ->setRedirector($this->app->make('redirect'));

        try {
            $request->validateResolved();
        } catch (Throwable $e) {
            if (method_exists($e, 'getStatusCode') && method_exists($e, 'getHeaders')) {
                static::assertSame(204, $e->getStatusCode());
                static::assertArrayHasKey('Precognition-Success', $e->getHeaders());
                static::assertSame('true', $e->getHeaders()['Precognition-Success']);
            } else {
                throw $e;
            }
        }
    }

    public function test_checks_challenge_on_precognition(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('key')->andReturn(Turnstile::KEY);
            $mock->expects('rules')->andReturn([Turnstile::KEY => 'turnstile']);
            $mock->expects('getChallenge')->andReturn(new Challenge(
                true, '', '', '', [], [], new Carbon(),
            ));
        });

        $request = TestTurnstileRequest::create('/', 'POST', [Turnstile::KEY => 'test_token'])
            ->setContainer($this->app);

        $request->headers->set('Precognition-Validate-Only', 'not-turnstile');
        $request->attributes->set('precognitive', true);
        $request->skipWhenPrecognitive = false;

        try {
            $request->validateResolved();
        } catch (Throwable $e) {
            if (method_exists($e, 'getStatusCode') && method_exists($e, 'getHeaders')) {
                static::assertSame(204, $e->getStatusCode());
                static::assertArrayHasKey('Precognition-Success', $e->getHeaders());
                static::assertSame('true', $e->getHeaders()['Precognition-Success']);
            } else {
                throw $e;
            }
        }
    }

    public function test_uses_custom_key_and_rules(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('key')->never();
            $mock->expects('rules')->never();
            $mock->expects('getChallenge')->with('test_token', '127.0.0.1')->andReturn(new Challenge(
                false, '', '', '', [], [], new Carbon(),
            ));
        });

        $request = TestCustomKeyAndRulesTurnstileRequest::create('/', 'POST', ['test-key' => 'test_token'])
            ->setContainer($this->app);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The Cloudflare Turnstile challenge is invalid, absent, or has failed.');

        $request->validateResolved();
    }
}

class TestTurnstileRequest extends TurnstileRequest
{
    public bool $rulesWasAsked = false;

    public function rules(): array
    {
        $this->rulesWasAsked = true;

        return [
            'test' => 'required|string',
        ];
    }

    public bool $skipWhenPrecognitive = true;

    protected function skipChallengeWhenPrecognitive(): bool
    {
        return $this->skipWhenPrecognitive;
    }
}

class TestMessagesAndAttributesTurnstileRequest extends TurnstileRequest
{
    public function messages(): array
    {
        return [
            Turnstile::KEY => 'test_message :attribute',
        ];
    }

    public function attributes(): array
    {
        return [
            Turnstile::KEY => 'test_attribute',
        ];
    }
}

class TestCustomKeyAndRulesTurnstileRequest extends TurnstileRequest
{
    protected function getTurnstileKey(): string
    {
        return 'test-key';
    }

    protected function getTurnstileRules(): array|string
    {
        return [fn($attribute, $value, $fail) => $value === 'test_token' || $fail(), 'turnstile'];
    }
}
