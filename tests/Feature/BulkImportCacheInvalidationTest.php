<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Services\OrganizationReportCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BulkImportCacheInvalidationTest extends TestCase
{
    use DatabaseTransactions;

    protected Organization $organization;

    protected OrganizationReportCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->cacheService = app(OrganizationReportCacheService::class);
    }

    public function test_bulk_evaluation_import_invalidates_organization_cache(): void
    {
        // Create some evaluations first
        PaperEvaluation::factory()
            ->for($this->organization)
            ->create(['evaluation_type' => 'referencia_iii', 'processing_status' => 'completed']);

        // Prime the cache by calling rememberListResults
        $cacheKey = $this->cacheService->getListResultsCacheKey($this->organization->id);
        Cache::rememberForever($cacheKey, function () {
            return ['evaluationGroups' => [], 'summary' => ['total' => 0]];
        });

        // Verify cache is set
        $this->assertTrue(Cache::has($cacheKey));

        // Invalidate cache (as ProcessBulkEvaluationImport does)
        $this->cacheService->forgetOrganizationCaches($this->organization->id, false);

        // Verify cache is cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_bulk_import_invalidates_missing_folios_cache(): void
    {
        // Prime the missing folios cache
        $cacheKey = $this->cacheService->getMissingFoliosCacheKey($this->organization->id);
        Cache::rememberForever($cacheKey, function () {
            return ['missing' => []];
        });

        // Verify cache is set
        $this->assertTrue(Cache::has($cacheKey));

        // Invalidate cache
        $this->cacheService->forgetOrganizationCaches($this->organization->id, false);

        // Verify cache is cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_bulk_import_invalidates_likert_report_cache(): void
    {
        // Prime the Likert report cache
        $cacheKey = $this->cacheService->getLikertReportCacheKey($this->organization->id);
        Cache::rememberForever($cacheKey, function () {
            return ['evaluations' => [], 'demographics' => []];
        });

        // Verify cache is set
        $this->assertTrue(Cache::has($cacheKey));

        // Invalidate cache
        $this->cacheService->forgetOrganizationCaches($this->organization->id, false);

        // Verify cache is cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_bulk_import_invalidates_all_organization_caches(): void
    {
        // Prime all three caches
        $listResultsKey = $this->cacheService->getListResultsCacheKey($this->organization->id);
        $missingFoliosKey = $this->cacheService->getMissingFoliosCacheKey($this->organization->id);
        $likertReportKey = $this->cacheService->getLikertReportCacheKey($this->organization->id);

        Cache::rememberForever($listResultsKey, function () {
            return [];
        });
        Cache::rememberForever($missingFoliosKey, function () {
            return [];
        });
        Cache::rememberForever($likertReportKey, function () {
            return [];
        });

        // Verify all caches are set
        $this->assertTrue(Cache::has($listResultsKey));
        $this->assertTrue(Cache::has($missingFoliosKey));
        $this->assertTrue(Cache::has($likertReportKey));

        // Invalidate all caches
        $this->cacheService->forgetOrganizationCaches($this->organization->id, false);

        // Verify all caches are cleared
        $this->assertFalse(Cache::has($listResultsKey));
        $this->assertFalse(Cache::has($missingFoliosKey));
        $this->assertFalse(Cache::has($likertReportKey));
    }
}
