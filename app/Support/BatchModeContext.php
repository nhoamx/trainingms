<?php

namespace App\Support;

/**
 * Global context to track if we're in batch operation mode.
 *
 * During bulk imports/updates, observers fire for every single row.
 * This causes thousands of cache invalidations and warming jobs.
 *
 * Solution: Set batch mode ON during imports, observers skip warming,
 * and the import job dispatches ONE warming job at the end.
 */
class BatchModeContext
{
    /**
     * Store batch mode state per organization
     *
     * @var array<string, bool>
     */
    private static array $batchModeByOrganization = [];

    /**
     * Enable batch mode for an organization
     */
    public static function enableForOrganization(string $organizationId): void
    {
        self::$batchModeByOrganization[$organizationId] = true;
    }

    /**
     * Disable batch mode for an organization
     */
    public static function disableForOrganization(string $organizationId): void
    {
        unset(self::$batchModeByOrganization[$organizationId]);
    }

    /**
     * Check if batch mode is enabled for an organization
     */
    public static function isEnabledForOrganization(string $organizationId): bool
    {
        return self::$batchModeByOrganization[$organizationId] ?? false;
    }

    /**
     * Clear all batch mode state (useful for testing)
     */
    public static function clear(): void
    {
        self::$batchModeByOrganization = [];
    }

    /**
     * Execute a callback with batch mode enabled for an organization
     */
    public static function runWithBatchMode(string $organizationId, callable $callback): mixed
    {
        self::enableForOrganization($organizationId);

        try {
            return $callback();
        } finally {
            self::disableForOrganization($organizationId);
        }
    }
}
