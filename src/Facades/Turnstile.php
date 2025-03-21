<?php

namespace Laragear\Turnstile\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Laragear\Turnstile\Challenge getChallenge(string $token, string $ip = '', string $idempotencyKey = '', array $options = [], bool $save = true)
 * @method static \Laragear\Turnstile\Challenge getChallengeFromRequest(\Illuminate\Http\Request|null $request = null, string $key = '', string $idempotencyKey = '', array $options = [], bool $store = true)
 * @method static \Laragear\Turnstile\Challenge challenge()
 * @method static string key()
 * @method static string rule()
 * @method static array rules()
 * @method static bool hasChallenge()
 * @method static bool missingChallenge()
 * @method static bool success()
 * @method static bool failed()
 *
 * @see \Laragear\Turnstile\Turnstile
 */
class Turnstile extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Laragear\Turnstile\Turnstile::class;
    }
}
