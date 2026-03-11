<?php

namespace Tests\Livewire;

use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\FormsServiceProvider;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\SchemasServiceProvider;
use Laragear\Turnstile\Challenge;
use Laragear\Turnstile\Livewire\InteractsWithTurnstile;
use Laragear\Turnstile\Turnstile;
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

        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            FilamentServiceProvider::class,
            SchemasServiceProvider::class,
            FormsServiceProvider::class,
            ActionsServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('turnstile.env', 'production');
    }

    public function test_does_not_checks_for_turnstile_when_disabled(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnTrue();
        });

        Livewire::test(TurnstileTestPage::class)
            ->fillForm(['test-input' => 'test'])
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_checks_for_turnstile_on_form_submission_and_errors_when_absent(): void
    {
        Livewire::test(TurnstileTestPage::class)
            ->fillForm(['test-input' => 'test'])
            ->call('save')
            ->assertHasErrors([
                Turnstile::KEY => __('turnstile::validation.invalid'),
            ]);

        Notification::assertNotified(
            Notification::make('turnstile-challenge')
                ->title(__('turnstile::notification.failed.title'))
                ->body(__('turnstile::notification.failed.body'))
                ->danger(),
        );
    }

    public function test_checks_for_turnstile(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('key')->andReturn(Turnstile::KEY);
            $mock->expects('getChallenge')->with('test-token')->andReturn(
                new Challenge(true, 'localhost', '', '', [], [], now()),
            );
        });

        Livewire::test(TurnstileTestPage::class)
            ->fillForm(['test-input' => 'test', Turnstile::KEY => 'test-token'])
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_checks_for_turnstile_custom_key(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('key')->andReturn('test-key');
            $mock->expects('getChallenge')->with('test-token')->andReturn(
                new Challenge(true, 'localhost', '', '', [], [], now()),
            );
        });

        Livewire::test(TurnstileTestPage::class)
            ->fillForm(['test-input' => 'test', 'test-key' => 'test-token'])
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_handles_invalid_challenge_exception(): void
    {
        $exception = new RuntimeException('test exception');

        $this->mock(Turnstile::class, function (MockInterface $mock) use ($exception) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('key')->andReturn(Turnstile::KEY);
            $mock->expects('getChallenge')->with('test-token')->andThrow($exception);
        });

        $test = Livewire::test(TurnstileTestPage::class)
            ->fillForm(['test-input' => 'test', Turnstile::KEY => 'test-token']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($exception->getMessage());

        $test->call('save');
    }

    public function test_failed_challenge_notifies_user_and_throws_error(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('key')->andReturn(Turnstile::KEY);
            $mock->expects('getChallenge')->with('test-token')->andReturn(
                new Challenge(false, 'localhost', '', '', [], [], now()),
            );
        });

        Livewire::test(TurnstileTestPage::class)
            ->fillForm(['test-input' => 'test', Turnstile::KEY => 'test-token'])
            ->call('save')
            ->assertHasErrors([
                Turnstile::KEY => __('turnstile::validation.invalid'),
            ]);

        Notification::assertNotified(
            Notification::make('turnstile-challenge')
                ->title(__('turnstile::notification.failed.title'))
                ->body(__('turnstile::notification.failed.body'))
                ->danger(),
        );
    }

    public function test_does_not_triggers_on_precognitive_request(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->never();
            $mock->expects('key')->never();
            $mock->expects('getChallenge')->never();
        });

        Livewire::test(TurnstileTestPagePrecognitive::class)
            ->withHeaders(['Precognition' => 'true', 'Precognition-Validate-Only' => Turnstile::KEY])
            ->fillForm(['numeric' => 'notanumber'])
            ->assertSet('data.numeric', 'notanumber');
    }

    public function test_skips_turnstile_when_user_is_authenticated(): void
    {
        $this->mock(Turnstile::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->never();
            $mock->expects('key')->never();
            $mock->expects('getChallenge')->never();
        });

        TurnstileTestPage::$skipWithAuth = true;

        Livewire::test(TurnstileTestPage::class)
            ->fillForm(['test-input' => 'test', Turnstile::KEY => 'test-token'])
            ->call('save')
            ->assertHasNoErrors();
    }
}

class TurnstileTestPage extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithTurnstile;

    public static bool $skipWithAuth = false;

    public array $data = [];

    protected function skipTurnstileValidation(): bool
    {
        return static::$skipWithAuth;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('test-input')->required()
        ])
            ->statePath('data');
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function save(): void
    {
        $this->form->getState();
    }

    protected string $view = 'filament-panels::pages.simple';
}

class TurnstileTestPagePrecognitive extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithTurnstile;

    public array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('numeric')->numeric()->live()->required()
        ])->statePath('data');
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function save(): void
    {
        $this->form->getState();
    }

    protected string $view = 'filament-panels::pages.simple';
}
