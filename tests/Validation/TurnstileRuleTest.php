<?php

namespace Tests\Validation;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Stringable;
use Laragear\Turnstile\Challenge;
use Laragear\Turnstile\Turnstile;
use Mockery\MockInterface;
use Tests\TestCase;

class TurnstileRuleTest extends TestCase
{
    protected function validator(array $data, array $rules, array $messages = [], array $attributes = []): Validator
    {
        return $this->app->make(Factory::class)->make($data, $rules, $messages, $attributes);
    }

    public function test_passes_validation_if_turnstile_disabled(): void
    {
        $this->app->make('config')->set('turnstile.env', false);

        $validator = $this->validator(
            [Turnstile::KEY => ''],
            [Turnstile::KEY => 'turnstile'],
        );

        static::assertTrue($validator->passes());
    }

    public function test_passes_validation_with_valid_token(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getChallenge')->with('test_key', '127.0.0.1')->andReturn(
                new Challenge(true, 'localhost', '', '', [], [], new Carbon())
            );
        });

        $validator = $this->validator(
            [Turnstile::KEY => 'test_key'],
            [Turnstile::KEY => 'turnstile'],
        );

        static::assertTrue($validator->passes());
    }

    public function test_fails_if_token_is_empty(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getChallenge')->never();
        });

        $validator = $this->validator(
            [Turnstile::KEY => ''],
            [Turnstile::KEY => 'turnstile:auth'],
        );

        static::assertFalse($validator->passes());
    }

    public function test_fails_if_token_is_not_string_but_empty(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getChallenge')->never();
        });

        $validator = $this->validator(
            [Turnstile::KEY => []],
            [Turnstile::KEY => 'turnstile:auth'],
        );

        static::assertFalse($validator->passes());
    }

    public function test_fails_if_token_not_string(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getChallenge')->never();
        });

        $validator = $this->validator(
            [Turnstile::KEY => (object)[] ],
            [Turnstile::KEY => 'turnstile:auth'],
        );

        static::assertFalse($validator->passes());
    }

    public function test_passes_with_stringable(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getChallenge')->with('test_key', '127.0.0.1')->andReturn(
                new Challenge(true, 'localhost', '', '', [], [], new Carbon())
            );
        });

        $validator = $this->validator(
            [Turnstile::KEY => new Stringable('test_key')],
            [Turnstile::KEY => 'turnstile'],
        );

        static::assertTrue($validator->passes());
    }

    public function test_fails_if_challenge_fails(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getChallenge')->with('test_key', '127.0.0.1')->andReturn(
                new Challenge(false, 'localhost', '', '', [], [], new Carbon())
            );
        });

        $validator = $this->validator(
            [Turnstile::KEY => 'test_key'],
            [Turnstile::KEY => 'turnstile'],
        );

        static::assertFalse($validator->passes());
    }

    public function test_passes_validation_if_user_authenticated(): void
    {
        $this->be(new User());

        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
        });

        $validator = $this->validator(
            [Turnstile::KEY => 'test_key'],
            [Turnstile::KEY => 'turnstile:auth'],
        );

        static::assertTrue($validator->passes());
    }

    public function test_passes_validation_if_user_is_guest_and_challenge_successful(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getChallenge')->with('test_key', '127.0.0.1')->andReturn(
                new Challenge(true, 'localhost', '', '', [], [], new Carbon())
            );
        });

        $validator = $this->validator(
            [Turnstile::KEY => 'test_key'],
            [Turnstile::KEY => 'turnstile:auth'],
        );

        static::assertTrue($validator->passes());
    }

    public function test_passes_validation_with_custom_auth_guard(): void
    {
        $this->be(new User(), 'web');

        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getChallenge')->never();
        });

        $validator = $this->validator(
            [Turnstile::KEY => 'test_key'],
            [Turnstile::KEY => 'turnstile:auth=web'],
        );

        static::assertTrue($validator->passes());
    }

    public function test_passes_validation_with_guest_and_custom_auth_guard_and_challenge_successful(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getChallenge')->with('test_key', '127.0.0.1')->andReturn(
                new Challenge(true, 'localhost', '', '', [], [], new Carbon())
            );
        });

        $validator = $this->validator(
            [Turnstile::KEY => 'test_key'],
            [Turnstile::KEY => 'turnstile:auth=web'],
        );

        static::assertTrue($validator->passes());
    }
}
