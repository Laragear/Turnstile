<?php

namespace Tests\Http\Requests;

use Illuminate\Support\Carbon;
use Laragear\Turnstile\Challenge;
use Laragear\Turnstile\Http\Requests\TurnstileRequest;
use Laragear\Turnstile\Turnstile;
use Mockery\MockInterface;
use Tests\TestCase;

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
        $this->post('test', [Turnstile::KEY => 'test_key'])->assertStatus(200)->assertSee('1');
    }

    public function test_request_json_passes(): void
    {
        $this->postJson('test', [Turnstile::KEY => 'test_key'])->assertStatus(200)->assertSee('1');
    }

    public function test_request_throws_validation_error_on_failed_challenge(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->twice()->andReturnFalse();
            $mock->expects('rules')->twice()->andReturn([Turnstile::KEY => 'turnstile']);
            $mock->expects('getChallenge')->twice()->andReturn( new Challenge(
                false, '', '', '', [], [], new Carbon()
            ));
        });

        $this->post('test', [Turnstile::KEY => 'fail'])
            ->assertSessionHasErrors([
                Turnstile::KEY => 'The Cloudflare Turnstile challenge is invalid, absent, or has failed.'
            ])
            ->assertRedirect('/');

        $this->postJson('test', [Turnstile::KEY => 'fail'])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                Turnstile::KEY => 'The Cloudflare Turnstile challenge is invalid, absent, or has failed.'
            ]);
    }
}
