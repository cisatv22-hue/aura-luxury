<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

final class StorefrontCache
{
    private const VERSION_KEY = 'aura:storefront-cache-version';

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $resolver
     * @return TValue
     */
    public static function remember(string $key, Closure $resolver): mixed
    {
        if (app()->environment('testing')) {
            return $resolver();
        }

        return Cache::remember(
            self::key($key),
            now()->addMinute(),
            $resolver,
        );
    }

    public static function flush(): void
    {
        if (! Cache::add(self::VERSION_KEY, 1, now()->addYears(1))) {
            Cache::increment(self::VERSION_KEY);
        }
    }

    private static function key(string $key): string
    {
        $version = (int) Cache::get(self::VERSION_KEY, 0);

        return "aura:storefront:{$version}:{$key}";
    }
}
