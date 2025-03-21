<?php

namespace Tests;

use ErrorException;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Laragear\Turnstile\Challenge;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use function array_merge;

class ChallengeTest extends PHPUnitTestCase
{
    protected function challenge(
        bool $successful = true,
        string $hostname = '',
        string $action = '',
        string $customerData = '',
        array $metadata = [],
        array $errors = [],
        Carbon $solvedAt = new Carbon(),
    ): Challenge {
        return new Challenge($successful, $hostname, $action, $customerData, $metadata, $errors, $solvedAt);
    }

    public static function provideFrontendErrors(): array
    {
        return [
            ['missing-input-secret'],
            ['invalid-input-secret'],
            ['missing-input-response'],
            ['invalid-input-response'],
        ];
    }

    public static function provideBackendErrors(): array
    {
        return [
            ['bad-request'],
            ['timeout-or-duplicated'],
        ];
    }

    public static function provideFrontendAndBackendErrors(): array
    {
        return array_merge(self::provideFrontendErrors(), self::provideBackendErrors());
    }

    #[DataProvider('provideFrontendErrors')]
    public function test_is_frontend_error(string $error): void
    {
        static::assertTrue($this->challenge(errors: [$error])->isFrontendError());
    }

    #[DataProvider('provideBackendErrors')]
    public function test_is_not_frontend_error(string $error): void
    {
        static::assertFalse($this->challenge(errors: [$error])->isFrontendError());
    }

    public function test_is_not_frontend_error_when_empty(): void
    {
        static::assertFalse($this->challenge()->isFrontendError());
    }

    #[DataProvider('provideBackendErrors')]
    public function test_is_backend_error(string $error): void
    {
        static::assertTrue($this->challenge(errors: [$error])->isBackendError());
    }

    #[DataProvider('provideFrontendErrors')]
    public function test_is_not_backend_error(string $error): void
    {
        static::assertFalse($this->challenge(errors: [$error])->isBackendError());
    }

    public function test_is_not_backend_error_when_empty(): void
    {
        static::assertFalse($this->challenge()->isBackendError());
    }

    public function test_is_server_error(): void
    {
        static::assertTrue($this->challenge(errors: ['internal-error'])->isServerError());
    }

    #[DataProvider('provideFrontendAndBackendErrors')]
    public function test_is_not_server_error(string $error): void
    {
        static::assertFalse($this->challenge(errors: [$error])->isServerError());
    }

    public function test_is_all_errors(): void
    {
        $errors = array_merge(['internal-error'], ...static::provideFrontendAndBackendErrors());

        $challenge = $this->challenge(errors: $errors);

        static::assertTrue($challenge->isFrontendError());
        static::assertTrue($challenge->isBackendError());
        static::assertTrue($challenge->isServerError());
    }

    public function test_retrieves_metadata_using_dot_notation(): void
    {
        $challenge = $this->challenge(metadata: ['foo' => ['bar' => 'baz']]);

        static::assertSame('baz', $challenge->metadata('foo.bar'));
        static::assertSame('invalid', $challenge->metadata('foo.baz', 'invalid'));
        static::assertSame('function', $challenge->metadata('foo.baz', fn () => 'function'));
    }

    public function test_is_action(): void
    {
        $challenge = $this->challenge(action: 'test');

        static::assertTrue($challenge->isAction('test'));
        static::assertFalse($challenge->isNotAction('test'));

        static::assertFalse($challenge->isAction('not-test'));
        static::assertTrue($challenge->isNotAction('not-test'));
    }

    public function test_is_customer_data(): void
    {
        $challenge = $this->challenge(customerData: 'test-customer-data');

        static::assertTrue($challenge->isCustomerData('test-customer-data'));
        static::assertTrue($challenge->isCustomerData('*-customer-data'));
        static::assertTrue($challenge->isCustomerData('test-customer-*'));
        static::assertTrue($challenge->isCustomerData(['*-customer-data', 'test-customer-*']));
        static::assertFalse($challenge->isCustomerData('customer-data'));

        static::assertFalse($challenge->isNotCustomerData('test-customer-data'));
        static::assertFalse($challenge->isNotCustomerData('*-customer-data'));
        static::assertFalse($challenge->isNotCustomerData('test-customer-*'));
        static::assertFalse($challenge->isNotCustomerData(['*-customer-data', 'test-customer-*']));
        static::assertTrue($challenge->isNotCustomerData('customer-data'));

        $challenge = $this->challenge();

        static::assertFalse($challenge->isCustomerData('test-customer-data'));
        static::assertTrue($challenge->isNotCustomerData('test-customer-data'));
    }

    public function test_error_presence(): void
    {
        $challenge = $this->challenge(errors: ['test-error']);

        static::assertFalse($challenge->hasError('invalid-error'));
        static::assertTrue($challenge->hasError('test-error'));
        static::assertTrue($challenge->hasError('invalid-error', 'test-error'));

        static::assertTrue($challenge->missingError('invalid-error'));
        static::assertFalse($challenge->missingError('test-error'));
        static::assertFalse($challenge->missingError('invalid-error', 'test-error'));
    }

    public function test_has_error_throws_when_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The errors array must not be empty');

        static::assertFalse($this->challenge()->hasError());
    }

    public function test_missing_error_throws_when_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The errors array must not be empty');

        static::assertFalse($this->challenge()->missingError());
    }

    public function test_success_alias(): void
    {
        static::assertTrue($this->challenge()->success);
    }

    public function test_failure_alias(): void
    {
        $challenge = $this->challenge();

        static::assertFalse($challenge->fail);
        static::assertFalse($challenge->failed);

        $challenge = $this->challenge(successful: false);

        static::assertTrue($challenge->fail);
        static::assertTrue($challenge->failed);
    }

    public function test_cdata_alias(): void
    {
        $challenge = $this->challenge(customerData: 'test-cdata');

        static::assertSame('test-cdata', $challenge->cData);
        static::assertSame('test-cdata', $challenge->cdata);
        static::assertSame('test-cdata', $challenge->c_data);
        static::assertSame('test-cdata', $challenge->customer_data);
    }

    public function test_solved_at_alias(): void
    {
        $challenge = $this->challenge(solvedAt: $carbon = new Carbon());

        static::assertSame($carbon, $challenge->solved_at);
    }

    public function test_invalid_property_throws(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Undefined property: Laragear\Turnstile\Challenge::$foo');

        $this->challenge()->foo;
    }
}
