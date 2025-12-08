<?php

namespace App\Services;

use App\Jobs\WarmOrganizationReportCache;
use App\Models\Organization;
use Illuminate\Support\Facades\Cache;

/**
 * Centralized service for managing organization report cache.
 *
 * Handles cache key generation, retrieval, storage, and invalidation
 * for expensive report computations (Likert reports, evaluation lists).
 *
 * TODO: When migrating to Redis in production, consider using Cache::tags()
 * for more efficient grouped invalidation. Database driver does not support tags.
 */
class OrganizationReportCacheService
{
    /**
     * Cache key prefix for organization reports
     */
    private const PREFIX = 'org_report';

    /**
     * Get the cache key for Likert report data
     */
    public function getLikertReportCacheKey(int|string $organizationId): string
    {
        return self::PREFIX."_likert_{$organizationId}";
    }

    /**
     * Get the cache key for list results data
     */
    public function getListResultsCacheKey(int|string $organizationId): string
    {
        return self::PREFIX."_list_{$organizationId}";
    }

    /**
     * Get the cache key for missing folios calculation
     */
    public function getMissingFoliosCacheKey(int|string $organizationId): string
    {
        return self::PREFIX."_missing_folios_{$organizationId}";
    }

    /**
     * Forget all cached report data for an organization and dispatch cache warming job
     */
    public function forgetOrganizationCaches(int|string $organizationId, bool $warmCache = true): void
    {
        Cache::forget($this->getLikertReportCacheKey($organizationId));
        Cache::forget($this->getListResultsCacheKey($organizationId));
        Cache::forget($this->getMissingFoliosCacheKey($organizationId));

        // Dispatch job to warm cache in the background
        if ($warmCache) {
            $organization = Organization::find($organizationId);
            if ($organization) {
                WarmOrganizationReportCache::dispatch($organization)->delay(now()->addSeconds(5));
            }
        }
    }

    /**
     * Check if Likert report cache exists for an organization
     */
    public function hasLikertReportCache(int|string $organizationId): bool
    {
        return Cache::has($this->getLikertReportCacheKey($organizationId));
    }

    /**
     * Check if list results cache exists for an organization
     */
    public function hasListResultsCache(int|string $organizationId): bool
    {
        return Cache::has($this->getListResultsCacheKey($organizationId));
    }

    /**
     * Get cached Likert report data or compute and cache it
     *
     * @param  callable  $callback  The callback to compute the data if not cached
     */
    public function rememberLikertReport(int|string $organizationId, callable $callback): array
    {
        return Cache::rememberForever(
            $this->getLikertReportCacheKey($organizationId),
            $callback
        );
    }

    /**
     * Get cached list results data or compute and cache it
     *
     * @param  callable  $callback  The callback to compute the data if not cached
     */
    public function rememberListResults(int|string $organizationId, callable $callback): array
    {
        return Cache::rememberForever(
            $this->getListResultsCacheKey($organizationId),
            $callback
        );
    }

    /**
     * Get cached missing folios data or compute and cache it
     *
     * @param  callable  $callback  The callback to compute the data if not cached
     */
    public function rememberMissingFolios(int|string $organizationId, callable $callback): array
    {
        return Cache::rememberForever(
            $this->getMissingFoliosCacheKey($organizationId),
            $callback
        );
    }
}
