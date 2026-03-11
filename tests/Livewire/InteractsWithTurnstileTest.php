<?php

namespace Tests\Livewire;

use Illuminate\Support\Str;
use Laragear\Turnstile\Challenge;
use Laragear\Turnstile\Livewire\InteractsWithTurnstile;
use Laragear\Turnstile\Turnstile;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class InteractsWithTurnstileTest extends TestCase
{
    protected function setUp(): void
    {
        TurnstileTestPage::$skipWithAuth = false;
        TurnstileTestPage::$validateAutomatically = true;

        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            LivewireServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('turnstile.env', 'production');
    }


    public function test_does_not_checks_for_turnstile_when_disabled(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnTrue();
        });

        Livewire::test(TurnstileTestPage::class)
            ->fill(['numeric' => '10'])
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_checks_for_turnstile_on_form_submission_and_errors_when_absent(): void
    {
        Livewire::test(TurnstileTestPage::class)
            ->fill(['numeric' => '10'])
            ->call('save')
            ->assertHasErrors([
                Turnstile::KEY => __('turnstile::validation.invalid'),
            ]);
    }

    public function test_checks_for_turnstile_successfully(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getChallenge')->with('test-token')->andReturn(
                new Challenge(true, 'localhost', '', '', [], [], now()),
            );
        });

        Livewire::test(TurnstileTestPage::class)
            ->fill(['numeric' => '10', Str::camel(Turnstile::KEY) => 'test-token'])
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_handles_invalid_challenge_exception(): void
    {
        $exception = new RuntimeException('test exception');

        $this->mock(Turnstile::class, function (MockInterface $mock) use ($exception) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getChallenge')->with('test-token')->andThrow($exception);
        });

        $test = Livewire::test(TurnstileTestPage::class)
            ->fill(['numeric' => '10', Str::camel(Turnstile::KEY) => 'test-token']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($exception->getMessage());

        $test->call('save');
    }

    public function test_failed_validation_does_not_validates_turnstile(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->never();
            $mock->expects('getChallenge')->never();
        });

        Livewire::test(TurnstileTestPage::class)
            ->fill(['numeric' => null, Str::camel(Turnstile::KEY) => 'test-token'])
            ->call('save')
            ->assertHasErrors('numeric');
    }

    public function test_failed_challenge_notifies_user_and_throws_error(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getChallenge')->with('test-token')->andReturn(
                new Challenge(false, 'localhost', '', '', [], [], now()),
            );
        });

        Livewire::test(TurnstileTestPage::class)
            ->fill(['numeric' => '10', Str::camel(Turnstile::KEY) => 'test-token'])
            ->call('save')
            ->assertHasErrors([
                Turnstile::KEY => __('turnstile::validation.invalid'),
            ]);
    }

    public function test_does_not_validates_turnstile_when_validating_single_field(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->never();
            $mock->expects('getChallenge')->never();
        });

        Livewire::test(TurnstileTestPage::class)
            ->fill(['numeric' => '10'])
            ->call('saveSingle', ['numeric']);
    }

    public function test_does_not_validates_turnstile_when_automatic_validation_false(): void
    {
        TurnstileTestPage::$validateAutomatically = false;

        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->never();
            $mock->expects('getChallenge')->never();
        });

        Livewire::test(TurnstileTestPage::class)
            ->fill(['numeric' => '10'])
            ->call('save');
    }

    public function test_skips_turnstile_when_skipped(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->never();
            $mock->expects('getChallenge')->never();
        });

        TurnstileTestPage::$skipWithAuth = true;

        Livewire::test(TurnstileTestPage::class)
            ->fill(['numeric' => '10'])
            ->call('save')
            ->assertHasNoErrors();
    }
}

class TurnstileTestPage extends Component
{
    use InteractsWithTurnstile;

    public static bool $skipWithAuth = false;
    public static bool $validateAutomatically = true;

    #[Rule(['required', 'numeric'])]
    public $numeric = '';

    public function mount(): void
    {
        $this->reset('numeric');
    }

    protected function validatesTurnstileAutomatically(): bool
    {
        return static::$validateAutomatically;
}

    protected function skipTurnstileValidation(): bool
    {
        return static::$skipWithAuth;
    }

    public function save(): void
    {
        // Standard Livewire validation
        $this->validate();
    }

    public function saveSingle(): void
    {
        $this->validateOnly('numeric');
    }

    public function render()
    {
        return '<div></div>';
    }
}
