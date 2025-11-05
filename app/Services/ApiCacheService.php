<?php declare (strict_types = 1);
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for caching API responses.
 */
class ApiCacheService
{
    /**
     * Fetch data from a callback with caching.
     *
     * @param string $cacheKey Unique cache key
     * @param callable $callback Callback that fetches data
     * @param int $successTtl TTL for successful responses in seconds
     * @param int $failureTtl TTL for failed responses in seconds
     * @return mixed
     */
    public function rememberApi(string $cacheKey, callable $callback, int $successTtl = 600, int $failureTtl = 60)
    {
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        return Cache::remember($cacheKey, $successTtl, function () use ($callback, $cacheKey, $failureTtl) {
            try {
                $result = $callback();
                if (empty($result)) {
                    Cache::put($cacheKey, null, $failureTtl);
                    return null;
                }

                return $result;

            } catch (\Throwable $e) {
                Log::error("API request failed", [
                    'cacheKey' => $cacheKey,
                    'error'    => $e->getMessage(),
                ]);

                Cache::put($cacheKey, null, $failureTtl);
                return null;
            }
        });
    }
}
