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
use function json_encode;

class TurnstileRequestTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->post('test', static function(TurnstileRequest $request): bool {
            return $request->challenge()->success;
        });
    }

    public function test_request_passes(): void
    {
        $this->expectNotToPerformAssertions();

        TurnstileRequest::create(
            uri: '/', method: 'POST', parameters: [Turnstile::KEY => 'test_key']
        )->setContainer($this->app)->validateResolved();
    }

    public function test_request_json_passes(): void
    {
        $this->expectNotToPerformAssertions();

        TurnstileRequest::create(uri: '/', method: 'POST', server: [
                'CONTENT_TYPE' => 'application/json',
        ], content: json_encode([Turnstile::KEY => 'test_key']))
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
            ['']
        ];
    }

    #[DataProvider('provideInvalidValues')]
    public function test_request_throws_validation_error_if_invalid_scalars(mixed $value): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The Cloudflare Turnstile challenge is invalid, absent, or has failed.');

        TurnstileRequest::create(uri: '/', method: 'POST', parameters: [Turnstile::KEY => $value])
            ->setContainer($this->app)
            ->setRedirector($this->app->make('redirect'))
            ->validateResolved();
    }

    public function test_request_throws_validation_error_on_failed_challenge(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('rules')->andReturn([Turnstile::KEY => 'turnstile']);
            $mock->expects('getChallenge')->andReturn( new Challenge(
                false, '', '', '', [], [], new Carbon()
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
            $mock->expects('rules')->andReturn([Turnstile::KEY => 'turnstile']);
            $mock->expects('getChallenge')->andReturn( new Challenge(
                false, '', '', '', [], [], new Carbon()
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
            uri: '/', method: 'POST', parameters: [Turnstile::KEY => 'test_key']
        )->setContainer($this->app);

        $request->validateResolved();

        $this->app->instance(
            Challenge::class, new Challenge(true, '', '', '', ['foo' => ['bar' => 'baz']], [], new Carbon())
        );

        static::assertSame('baz', $request->metadata('foo.bar'));
    }

    public function test_action(): void
    {
        $request = TurnstileRequest::create(
            uri: '/', method: 'POST', parameters: [Turnstile::KEY => 'test_key']
        )->setContainer($this->app);

        $request->validateResolved();

        $this->app->instance(
            Challenge::class, new Challenge(true, '', 'test_action', '', [], [], new Carbon())
        );

        static::assertTrue($request->isAction('test_action'));
        static::assertFalse($request->isNotAction('test_action'));
    }

    public function test_customer_data(): void
    {
        $request = TurnstileRequest::create(
            uri: '/', method: 'POST', parameters: [Turnstile::KEY => 'test_key']
        )->setContainer($this->app);

        $request->validateResolved();

        $this->app->instance(
            Challenge::class, new Challenge(true, '', '', 'test_cdata', [], [], new Carbon())
        );

        static::assertTrue($request->isCustomerData('test_cdata'));
        static::assertFalse($request->isNotCustomerData('test_cdata'));
    }
}
