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
     * Get the cache key for NOM-035 domain statistics
     */
    public function getNom035DomainsCacheKey(int|string $organizationId): string
    {
        return self::PREFIX."_nom035_domains_{$organizationId}";
    }

    /**
     * Get the cache key for NOM-035 category statistics
     */
    public function getNom035CategoriesCacheKey(int|string $organizationId): string
    {
        return self::PREFIX."_nom035_categories_{$organizationId}";
    }

    /**
     * Get the cache key for NOM-035 dimension statistics
     */
    public function getNom035DimensionsCacheKey(int|string $organizationId): string
    {
        return self::PREFIX."_nom035_dimensions_{$organizationId}";
    }

    /**
     * Get the cache key for NOM-035 question statistics
     */
    public function getNom035QuestionsCacheKey(int|string $organizationId): string
    {
        return self::PREFIX."_nom035_questions_{$organizationId}";
    }

    /**
     * Get the cache key for NOM-035 block statistics
     */
    public function getNom035BlocksCacheKey(int|string $organizationId): string
    {
        return self::PREFIX."_nom035_blocks_{$organizationId}";
    }

    /**
     * Get the cache key for NOM-035 global statistics
     */
    public function getNom035GlobalCacheKey(int|string $organizationId): string
    {
        return self::PREFIX."_nom035_global_{$organizationId}";
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

        // Invalidate NOM-035 caches
        Cache::forget($this->getNom035DomainsCacheKey($organizationId));
        Cache::forget($this->getNom035CategoriesCacheKey($organizationId));
        Cache::forget($this->getNom035DimensionsCacheKey($organizationId));
        Cache::forget($this->getNom035QuestionsCacheKey($organizationId));
        Cache::forget($this->getNom035BlocksCacheKey($organizationId));
        Cache::forget($this->getNom035GlobalCacheKey($organizationId));

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

    // ──────────────────────────────────────────────────────────────
    // Work Center Cache Keys
    // ──────────────────────────────────────────────────────────────

    /**
     * Cache key prefix for work center reports
     */
    private const WC_PREFIX = 'wc_report';

    /**
     * Get the cache key for WorkCenter NOM-035 domain statistics
     */
    public function getWcNom035DomainsCacheKey(int|string $workCenterId, ?string $source = null): string
    {
        $sourceSegment = in_array($source, ['online', 'paper'], true) ? $source : 'all';

        return self::WC_PREFIX."_nom035_domains_{$workCenterId}_{$sourceSegment}";
    }

    /**
     * Get the cache key for WorkCenter NOM-035 category statistics
     */
    public function getWcNom035CategoriesCacheKey(int|string $workCenterId, ?string $source = null): string
    {
        $sourceSegment = in_array($source, ['online', 'paper'], true) ? $source : 'all';

        return self::WC_PREFIX."_nom035_categories_{$workCenterId}_{$sourceSegment}";
    }

    /**
     * Get the cache key for WorkCenter NOM-035 dimension statistics
     */
    public function getWcNom035DimensionsCacheKey(int|string $workCenterId, ?string $source = null): string
    {
        $sourceSegment = in_array($source, ['online', 'paper'], true) ? $source : 'all';

        return self::WC_PREFIX."_nom035_dimensions_{$workCenterId}_{$sourceSegment}";
    }

    /**
     * Get the cache key for WorkCenter NOM-035 question statistics
     */
    public function getWcNom035QuestionsCacheKey(int|string $workCenterId, ?string $source = null): string
    {
        $sourceSegment = in_array($source, ['online', 'paper'], true) ? $source : 'all';

        return self::WC_PREFIX."_nom035_questions_{$workCenterId}_{$sourceSegment}";
    }

    /**
     * Get the cache key for WorkCenter NOM-035 block statistics
     */
    public function getWcNom035BlocksCacheKey(int|string $workCenterId, ?string $source = null): string
    {
        $sourceSegment = in_array($source, ['online', 'paper'], true) ? $source : 'all';

        return self::WC_PREFIX."_nom035_blocks_{$workCenterId}_{$sourceSegment}";
    }

    /**
     * Get the cache key for WorkCenter NOM-035 global statistics
     */
    public function getWcNom035GlobalCacheKey(int|string $workCenterId, ?string $source = null): string
    {
        $sourceSegment = in_array($source, ['online', 'paper'], true) ? $source : 'all';

        return self::WC_PREFIX."_nom035_global_{$workCenterId}_{$sourceSegment}";
    }

    /**
     * Get the cache key for WorkCenter NOM-035 violence labor statistics
     */
    public function getWcNom035ViolenceCacheKey(int|string $workCenterId, ?string $source = null): string
    {
        $sourceSegment = in_array($source, ['online', 'paper'], true) ? $source : 'all';

        return self::WC_PREFIX."_nom035_violence_{$workCenterId}_{$sourceSegment}";
    }

    /**
     * Get the cache key for WorkCenter NOM-035 general report table
     */
    public function getWcNom035GeneralReportCacheKey(int|string $workCenterId, ?string $source = null): string
    {
        $sourceSegment = in_array($source, ['online', 'paper'], true) ? $source : 'all';

        return self::WC_PREFIX."_nom035_general_report_{$workCenterId}_{$sourceSegment}";
    }

    /**
     * Get the cache key for WorkCenter NOM-035 Referencia I (ATS) statistics
     */
    public function getWcNom035RefIStatsCacheKey(int|string $workCenterId, ?string $source = null): string
    {
        $sourceSegment = in_array($source, ['online', 'paper'], true) ? $source : 'all';

        return self::WC_PREFIX."_nom035_ref_i_stats_{$workCenterId}_{$sourceSegment}";
    }

    /**
     * Forget all cached report data for a work center
     */
    public function forgetWorkCenterCaches(int|string $workCenterId): void
    {
        Cache::forget($this->getWcNom035DomainsCacheKey($workCenterId));
        Cache::forget($this->getWcNom035DomainsCacheKey($workCenterId, 'online'));
        Cache::forget($this->getWcNom035DomainsCacheKey($workCenterId, 'paper'));
        Cache::forget($this->getWcNom035CategoriesCacheKey($workCenterId));
        Cache::forget($this->getWcNom035CategoriesCacheKey($workCenterId, 'online'));
        Cache::forget($this->getWcNom035CategoriesCacheKey($workCenterId, 'paper'));
        Cache::forget($this->getWcNom035DimensionsCacheKey($workCenterId));
        Cache::forget($this->getWcNom035DimensionsCacheKey($workCenterId, 'online'));
        Cache::forget($this->getWcNom035DimensionsCacheKey($workCenterId, 'paper'));
        Cache::forget($this->getWcNom035QuestionsCacheKey($workCenterId));
        Cache::forget($this->getWcNom035QuestionsCacheKey($workCenterId, 'online'));
        Cache::forget($this->getWcNom035QuestionsCacheKey($workCenterId, 'paper'));
        Cache::forget($this->getWcNom035BlocksCacheKey($workCenterId));
        Cache::forget($this->getWcNom035BlocksCacheKey($workCenterId, 'online'));
        Cache::forget($this->getWcNom035BlocksCacheKey($workCenterId, 'paper'));
        Cache::forget($this->getWcNom035GlobalCacheKey($workCenterId));
        Cache::forget($this->getWcNom035GlobalCacheKey($workCenterId, 'online'));
        Cache::forget($this->getWcNom035GlobalCacheKey($workCenterId, 'paper'));
        Cache::forget($this->getWcNom035ViolenceCacheKey($workCenterId));
        Cache::forget($this->getWcNom035ViolenceCacheKey($workCenterId, 'online'));
        Cache::forget($this->getWcNom035ViolenceCacheKey($workCenterId, 'paper'));
        Cache::forget($this->getWcNom035GeneralReportCacheKey($workCenterId));
        Cache::forget($this->getWcNom035GeneralReportCacheKey($workCenterId, 'online'));
        Cache::forget($this->getWcNom035GeneralReportCacheKey($workCenterId, 'paper'));
        Cache::forget($this->getWcNom035RefIStatsCacheKey($workCenterId));
        Cache::forget($this->getWcNom035RefIStatsCacheKey($workCenterId, 'online'));
        Cache::forget($this->getWcNom035RefIStatsCacheKey($workCenterId, 'paper'));
    }
}
