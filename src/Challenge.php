<?php

namespace Laragear\Turnstile;

use Carbon\CarbonInterface;
use ErrorException;
use Illuminate\Support\Arr;
use Illuminate\Support\Stringable;
use InvalidArgumentException;
use function in_array;

/**
 * @property-read bool $success
 * @property-read bool $failed
 * @property-read bool $fail
 * @property-read string $cData
 * @property-read string $cdata
 * @property-read string $customer_data
 * @property-read string $c_data
 * @property-read \Carbon\CarbonInterface $solved_at
 */
readonly class Challenge
{
    /**
     * Create a new Turnstile Challenge Response.
     *
     * @param  string[]  $errors
     */
    public function __construct(
        public bool $successful,
        public string $hostname,
        public string $action,
        public string $customerData,
        public array $metadata,
        public array $errors,
        public CarbonInterface $solvedAt,
    ) {
        // ...
    }

    /**
     * Check if there is an error due to frontend manipulation (bad secret or token).
     */
    public function isFrontendError(): bool
    {
        return $this->hasError(
            'missing-input-secret',
            'invalid-input-secret',
            'missing-input-response',
            'invalid-input-response'
        );
    }

    /**
     * Check if there is an error due to backend manipulation (bad request, duplicated token).
     */
    public function isBackendError(): bool
    {
        return $this->hasError('bad-request', 'timeout-or-duplicated');
    }

    /**
     * Check if there is an error due to Turnstile (anything else).
     */
    public function isServerError(): bool
    {
        return $this->hasError('internal-error');
    }

    /**
     * Returns a metadata value using a key in `dot.notation`.
     */
    public function metadata(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->metadata, $key, $default);
    }

    /**
     * Check if the action is the same as the developer expects.
     */
    public function isAction(string $action): bool
    {
        return $this->action === $action;
    }

    /**
     * Check if the action is different from the developer expects.
     */
    public function isNotAction(string $action): bool
    {
        return ! $this->isAction($action);
    }

    /**
     * Returns the Customer Data as a Stringable instance.
     */
    public function strOfCustomerData(): Stringable
    {
        return new Stringable($this->customerData);
    }

    /**
     * Checks if the Customer Data is the same pattern as the developer expects.
     *
     * @param  string|iterable<string>  $customerData
     */
    public function isCustomerData(string|iterable $customerData): bool
    {
        return $this->strOfCustomerData()->is($customerData);
    }

    /**
     * Checks if the Customer Data is a different pattern as the developer expects.
     *
     * @param  string|iterable<string>  $customerData
     */
    public function isNotCustomerData(string|iterable $customerData): bool
    {
        return !$this->isCustomerData($customerData);
    }

    /**
     * Checks if the response has any of the given errors.
     */
    public function hasError(string ...$errors): bool
    {
        if (!$errors) {
            throw new InvalidArgumentException('The errors array must not be empty.');
        }

        foreach ($errors as $error) {
            if (in_array($error, $this->errors, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the response has none of the given errors.
     */
    public function missingError(string...$errors): bool
    {
        return !$this->hasError(...$errors);
    }

    /**
     * Dynamically retrieve the object properties.
     *
     * @throws \ErrorException
     */
    public function __get(string $name): CarbonInterface|string|bool
    {
        return match ($name) {
            'success' => $this->successful,
            'failed', 'fail' => !$this->successful,
            'cdata', 'cData', 'customer_data', 'c_data' => $this->customerData,
            'solved_at' => $this->solvedAt,
            default => throw new ErrorException('Undefined property: ' . static::class . '::$' . $name),
        };
    }
}
