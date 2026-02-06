<?php

namespace App\Services;

use App\Jobs\WarmOrganizationReportCache;
use App\Models\Organization;
use App\Support\BatchModeContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Centralized service for managing organization report cache.
 *
 * Handles cache key generation, retrieval, storage, and invalidation
 * for expensive report computations (Likert reports, evaluation lists).
 *
 * Features:
 * - Batch mode detection (prevents 1000s of warming jobs during imports)
 * - Debouncing (only 1 warming job per organization within time window)
 * - Immediate invalidation (data is fresh, computed on next access)
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
     * Debounce window in seconds (prevent duplicate warming jobs)
     */
    private const DEBOUNCE_WINDOW = 30;

    /**
     * Delay before dispatching warming job (gives time for batch operations)
     */
    private const WARMING_DELAY = 10;

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
     * Get the cache key for tracking warming job dispatches (debouncing)
     */
    private function getWarmingLockCacheKey(int|string $organizationId): string
    {
        return self::PREFIX."_warming_lock_{$organizationId}";
    }

    /**
     * Forget all cached report data for an organization
     *
     * Intelligently handles warming:
     * - In batch mode: No warming (caller will warm manually after batch)
     * - With debouncing: Only 1 warming job per organization within window
     * - Immediate invalidation: Fresh data on next access
     *
     * @param  bool  $warmCache  Whether to dispatch warming job
     * @param  bool  $forceSyncWarm  Force immediate synchronous warming (use sparingly)
     */
    public function forgetOrganizationCaches(
        int|string $organizationId,
        bool $warmCache = true,
        bool $forceSyncWarm = false
    ): void {
        // Immediately invalidate all caches
        Cache::forget($this->getLikertReportCacheKey($organizationId));
        Cache::forget($this->getListResultsCacheKey($organizationId));
        Cache::forget($this->getMissingFoliosCacheKey($organizationId));

        // Skip warming if not requested
        if (! $warmCache) {
            return;
        }

        // Skip warming if we're in batch mode (import will warm at the end)
        if (BatchModeContext::isEnabledForOrganization((string) $organizationId)) {
            Log::debug("Skipping cache warming for org {$organizationId} (batch mode active)");

            return;
        }

        // Check if warming job already dispatched recently (debouncing)
        $lockKey = $this->getWarmingLockCacheKey($organizationId);
        if (! $forceSyncWarm && Cache::has($lockKey)) {
            Log::debug("Skipping cache warming for org {$organizationId} (debounced)");

            return;
        }

        // Set debounce lock
        Cache::put($lockKey, true, self::DEBOUNCE_WINDOW);

        // Dispatch warming job
        $organization = Organization::find($organizationId);
        if ($organization) {
            $delay = $forceSyncWarm ? now() : now()->addSeconds(self::WARMING_DELAY);

            Log::info("Dispatching cache warming for org {$organizationId}", [
                'delay_seconds' => $forceSyncWarm ? 0 : self::WARMING_DELAY,
                'batch_mode' => false,
            ]);

            WarmOrganizationReportCache::dispatch($organization)->delay($delay);
        }
    }

    /**
     * Force immediate cache invalidation and warming
     * Use this when you NEED fresh data right now (e.g., after critical import)
     */
    public function forceRefresh(int|string $organizationId): void
    {
        $this->forgetOrganizationCaches($organizationId, warmCache: true, forceSyncWarm: true);
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
