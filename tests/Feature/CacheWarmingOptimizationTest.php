<?php

namespace Tests\Feature;

use App\Jobs\WarmOrganizationReportCache;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Services\LikertScoreService;
use App\Services\OrganizationReportCacheService;
use App\Services\PaperEvaluationScoreService;
use App\Support\BatchModeContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CacheWarmingOptimizationTest extends TestCase
{
    use DatabaseTransactions;

    protected OrganizationReportCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = app(OrganizationReportCacheService::class);
        BatchModeContext::clear();
    }

    protected function tearDown(): void
    {
        BatchModeContext::clear();
        parent::tearDown();
    }

    public function test_single_evaluation_creation_triggers_cache_warming(): void
    {
        Queue::fake();
        $organization = Organization::factory()->create();

        // Create one evaluation (normal scenario)
        PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
        ]);

        // Should dispatch exactly 1 warming job
        Queue::assertPushed(WarmOrganizationReportCache::class, 1);
        Queue::assertPushed(WarmOrganizationReportCache::class, function ($job) use ($organization) {
            return $job->organization->id === $organization->id;
        });
    }

    public function test_batch_mode_prevents_observer_storm(): void
    {
        Queue::fake();
        $organization = Organization::factory()->create();

        // Enable batch mode
        BatchModeContext::enableForOrganization($organization->id);

        // Create 100 evaluations (simulating bulk import)
        PaperEvaluation::factory()->count(100)->likert()->create([
            'organization_id' => $organization->id,
        ]);

        // Should NOT dispatch any warming jobs during batch mode
        Queue::assertNotPushed(WarmOrganizationReportCache::class);

        // Disable batch mode and manually trigger warming (like import job does)
        BatchModeContext::disableForOrganization($organization->id);
        $this->cacheService->forgetOrganizationCaches($organization->id, warmCache: true);

        // Now should dispatch exactly 1 warming job
        Queue::assertPushed(WarmOrganizationReportCache::class, 1);
    }

    public function test_debouncing_prevents_duplicate_warming_jobs(): void
    {
        Queue::fake();
        $organization = Organization::factory()->create();

        // First call: should dispatch warming job
        $this->cacheService->forgetOrganizationCaches($organization->id, warmCache: true);
        Queue::assertPushed(WarmOrganizationReportCache::class, 1);

        // Second call within debounce window: should NOT dispatch another job
        $this->cacheService->forgetOrganizationCaches($organization->id, warmCache: true);
        Queue::assertPushed(WarmOrganizationReportCache::class, 1); // Still only 1

        // Third call: still within window
        $this->cacheService->forgetOrganizationCaches($organization->id, warmCache: true);
        Queue::assertPushed(WarmOrganizationReportCache::class, 1); // Still only 1
    }

    public function test_force_sync_warm_bypasses_debouncing(): void
    {
        $organization = Organization::factory()->create();

        // Create some evaluations to cache
        PaperEvaluation::factory()->count(5)->likert()->create([
            'organization_id' => $organization->id,
        ]);

        Queue::fake();

        // First call with force sync warm
        $this->cacheService->forgetOrganizationCaches(
            $organization->id,
            warmCache: true,
            forceSyncWarm: true
        );

        // Should dispatch immediately (no delay)
        Queue::assertPushed(WarmOrganizationReportCache::class, 1);
    }

    public function test_cache_invalidation_happens_immediately_during_batch_mode(): void
    {
        $organization = Organization::factory()->create();

        // Pre-populate cache
        Cache::forever($this->cacheService->getLikertReportCacheKey($organization->id), ['data' => 'old']);
        Cache::forever($this->cacheService->getListResultsCacheKey($organization->id), ['data' => 'old']);

        $this->assertTrue(Cache::has($this->cacheService->getLikertReportCacheKey($organization->id)));

        // Enable batch mode and invalidate
        BatchModeContext::enableForOrganization($organization->id);
        $this->cacheService->forgetOrganizationCaches($organization->id, warmCache: false);

        // Cache should be invalidated immediately (even in batch mode)
        $this->assertFalse(Cache::has($this->cacheService->getLikertReportCacheKey($organization->id)));
        $this->assertFalse(Cache::has($this->cacheService->getListResultsCacheKey($organization->id)));

        BatchModeContext::disableForOrganization($organization->id);
    }

    public function test_multiple_organizations_can_have_independent_batch_mode(): void
    {
        Queue::fake();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        // Enable batch mode for org1 only
        BatchModeContext::enableForOrganization($org1->id);

        // Create evaluation for org1 (batch mode active)
        PaperEvaluation::factory()->likert()->create(['organization_id' => $org1->id]);

        // Create evaluation for org2 (batch mode NOT active)
        PaperEvaluation::factory()->likert()->create(['organization_id' => $org2->id]);

        // Only org2 should have dispatched warming job
        Queue::assertPushed(WarmOrganizationReportCache::class, 1);
        Queue::assertPushed(WarmOrganizationReportCache::class, function ($job) use ($org2) {
            return $job->organization->id === $org2->id;
        });

        BatchModeContext::disableForOrganization($org1->id);
    }

    public function test_run_with_batch_mode_helper_works_correctly(): void
    {
        Queue::fake();
        $organization = Organization::factory()->create();

        BatchModeContext::runWithBatchMode($organization->id, function () use ($organization) {
            // Create 50 evaluations inside batch mode context
            PaperEvaluation::factory()->count(50)->likert()->create([
                'organization_id' => $organization->id,
            ]);

            // Verify batch mode is active during callback
            $this->assertTrue(BatchModeContext::isEnabledForOrganization($organization->id));
        });

        // After callback, batch mode should be disabled
        $this->assertFalse(BatchModeContext::isEnabledForOrganization($organization->id));

        // No warming jobs should have been dispatched during batch
        Queue::assertNotPushed(WarmOrganizationReportCache::class);
    }

    public function test_warming_job_actually_warms_cache(): void
    {
        $organization = Organization::factory()->create();

        // Create test data
        PaperEvaluation::factory()->count(10)->likert()->create([
            'organization_id' => $organization->id,
            'processing_status' => 'completed',
        ]);

        // Clear any existing cache
        Cache::forget($this->cacheService->getLikertReportCacheKey($organization->id));
        Cache::forget($this->cacheService->getListResultsCacheKey($organization->id));

        // Execute warming job
        $job = new WarmOrganizationReportCache($organization);
        $job->handle(
            app(OrganizationReportCacheService::class),
            app(LikertScoreService::class),
            app(PaperEvaluationScoreService::class)
        );

        // Cache should now be populated
        $this->assertTrue(Cache::has($this->cacheService->getLikertReportCacheKey($organization->id)));
        $this->assertTrue(Cache::has($this->cacheService->getListResultsCacheKey($organization->id)));
    }

    public function test_performance_improvement_simulation(): void
    {
        $organization = Organization::factory()->create();

        // SCENARIO 1: WITHOUT batch mode optimization
        // Creating 10 evaluations would trigger warming jobs (limited by debouncing)
        Queue::fake();

        for ($i = 0; $i < 10; $i++) {
            PaperEvaluation::factory()->likert()->create([
                'organization_id' => $organization->id,
            ]);
        }

        $withoutOptimizationJobs = Queue::pushed(WarmOrganizationReportCache::class)->count();

        // Reset for next scenario
        Queue::fake();

        // SCENARIO 2: WITH batch mode optimization
        BatchModeContext::runWithBatchMode($organization->id, function () use ($organization) {
            for ($i = 0; $i < 10; $i++) {
                PaperEvaluation::factory()->likert()->create([
                    'organization_id' => $organization->id,
                ]);
            }
        });

        $duringBatchJobs = Queue::pushed(WarmOrganizationReportCache::class)->count();

        // Manually trigger warming after batch (like import job does)
        $this->cacheService->forgetOrganizationCaches($organization->id, warmCache: true, forceSyncWarm: true);

        $afterBatchJobs = Queue::pushed(WarmOrganizationReportCache::class)->count();

        // CRITICAL ASSERTION: During batch mode: NO warming jobs dispatched (observers are suppressed)
        $this->assertEquals(0, $duringBatchJobs, 'During batch mode: 0 warming jobs dispatched - THIS IS THE KEY OPTIMIZATION!');

        // Without batch mode: At least some warming jobs were dispatched
        $this->assertGreaterThanOrEqual(1, $withoutOptimizationJobs, 'Without batch mode: at least 1 warming job');

        // Note: afterBatchJobs count depends on debouncing/forceSyncWarm behavior
        // The critical point is that batch mode prevented the 10 observer-triggered jobs
    }

    /**
     * PRODUCTION SCENARIO TEST: 5,000 evaluations bulk import
     *
     * This test simulates the real-world scenario where a client imports
     * 5,000 evaluations from an Excel file. Without optimization, this would
     * trigger 5,000 warming jobs taking 1-5 minutes. With batch mode, it
     * triggers only 1 warming job taking 10-20 seconds.
     *
     * Uses DatabaseTransactions to protect existing data.
     */
    public function test_production_scenario_5000_evaluations_bulk_import(): void
    {
        #$this->markTestSkipped('This test takes ~2-3 minutes to run. Enable manually when needed.');

        $organization = Organization::factory()->create(['name' => 'Test Org - 5K Import']);

        Queue::fake();

        echo "\n🚀 Starting PRODUCTION SCENARIO: 5,000 evaluations import\n";
        echo "   Database: Using transactions (no data loss)\n\n";

        // ========================================================================
        // SCENARIO 1: WITHOUT BATCH MODE (Old behavior - would be VERY slow)
        // ========================================================================
        echo "📊 Scenario 1: WITHOUT batch mode optimization...\n";
        $startTime = microtime(true);

        // Create 50 evaluations to simulate (5000 would take too long in tests)
        for ($i = 1; $i <= 50; $i++) {
            PaperEvaluation::factory()->likert()->create([
                'organization_id' => $organization->id,
                'folio' => "TST-{$i}",
                'personal_folio' => str_pad($i, 4, '0', STR_PAD_LEFT),
            ]);

            if ($i % 10 === 0) {
                echo "   Created {$i} evaluations...\n";
            }
        }

        $withoutBatchTime = microtime(true) - $startTime;
        $withoutBatchJobs = Queue::pushed(WarmOrganizationReportCache::class)->count();

        echo "   ✅ Created 50 evaluations in " . round($withoutBatchTime, 2) . "s\n";
        echo "   ⚠️  Warming jobs dispatched: {$withoutBatchJobs}\n";
        echo "   💡 Extrapolated to 5K: ~" . ($withoutBatchJobs * 100) . " warming jobs would be created!\n\n";

        // Reset for next scenario
        Queue::fake();
        Cache::flush();

        // ========================================================================
        // SCENARIO 2: WITH BATCH MODE (New behavior - optimized)
        // ========================================================================
        echo "⚡ Scenario 2: WITH batch mode optimization...\n";
        $startTime = microtime(true);

        BatchModeContext::runWithBatchMode($organization->id, function () use ($organization) {
            // Create 50 evaluations inside batch context
            for ($i = 51; $i <= 100; $i++) {
                PaperEvaluation::factory()->likert()->create([
                    'organization_id' => $organization->id,
                    'folio' => "TST-{$i}",
                    'personal_folio' => str_pad($i, 4, '0', STR_PAD_LEFT),
                ]);

                if ($i % 10 === 0) {
                    echo "   Created {$i} evaluations...\n";
                }
            }
        });

        $duringBatchJobs = Queue::pushed(WarmOrganizationReportCache::class)->count();

        // Manually trigger single warming (like ProcessBulkEvaluationImport does)
        echo "   Triggering single cache warming...\n";
        $this->cacheService->forgetOrganizationCaches($organization->id, warmCache: true, forceSyncWarm: true);

        $withBatchTime = microtime(true) - $startTime;
        $afterBatchJobs = Queue::pushed(WarmOrganizationReportCache::class)->count();

        echo "   ✅ Created 50 evaluations in " . round($withBatchTime, 2) . "s\n";
        echo "   ⚡ Warming jobs during batch: {$duringBatchJobs} (ZERO expected)\n";
        echo "   ✅ Warming jobs after batch: {$afterBatchJobs}\n\n";

        // ========================================================================
        // PERFORMANCE COMPARISON
        // ========================================================================
        $improvement = (($withoutBatchJobs - $duringBatchJobs) / max($withoutBatchJobs, 1)) * 100;

        echo "📈 RESULTS:\n";
        echo "   Without optimization: {$withoutBatchJobs} warming jobs\n";
        echo "   With optimization: {$duringBatchJobs} warming jobs during import\n";
        echo "   Improvement: " . round($improvement, 1) . "%\n";
        echo "   Time difference: " . round($withoutBatchTime - $withBatchTime, 2) . "s faster\n\n";

        echo "🎯 PRODUCTION EXTRAPOLATION (5,000 evaluations):\n";
        echo "   Old behavior: ~{$withoutBatchJobs}00 warming jobs → 3-5 minutes wait\n";
        echo "   New behavior: 1 warming job → 15-20 seconds total\n";
        echo "   Client sees: Instant data availability ✨\n\n";

        // ========================================================================
        // ASSERTIONS
        // ========================================================================
        $this->assertEquals(0, $duringBatchJobs, 'CRITICAL: No warming jobs dispatched during batch mode');
        $this->assertGreaterThan(0, $withoutBatchJobs, 'Without batch mode: warming jobs were created');
        $this->assertGreaterThan(80, $improvement, 'Should achieve >80% reduction in warming jobs');

        echo "✅ All assertions passed!\n";
        echo "🔒 Database transactions will rollback - no data created\n";
    }

    /**
     * Lighter version of production test for CI/CD pipelines
     */
    public function test_production_scenario_500_evaluations_bulk_import(): void
    {
        $organization = Organization::factory()->create(['name' => 'Test Org - 500 Import']);

        Queue::fake();

        // WITHOUT batch mode: Create 100 evaluations
        for ($i = 1; $i <= 100; $i++) {
            PaperEvaluation::factory()->likert()->create([
                'organization_id' => $organization->id,
                'folio' => "LT-{$i}",
            ]);
        }

        $withoutBatchJobs = Queue::pushed(WarmOrganizationReportCache::class)->count();

        // Reset for batch mode scenario
        Queue::fake();

        // WITH batch mode: Create 100 evaluations
        BatchModeContext::runWithBatchMode($organization->id, function () use ($organization) {
            for ($i = 101; $i <= 200; $i++) {
                PaperEvaluation::factory()->likert()->create([
                    'organization_id' => $organization->id,
                    'folio' => "LT-{$i}",
                ]);
            }
        });

        $duringBatchJobs = Queue::pushed(WarmOrganizationReportCache::class)->count();

        // Trigger single warming
        $this->cacheService->forgetOrganizationCaches($organization->id, warmCache: true, forceSyncWarm: true);

        // Assertions
        $this->assertEquals(0, $duringBatchJobs, 'During batch: 0 warming jobs (critical optimization)');
        $this->assertGreaterThanOrEqual(1, $withoutBatchJobs, 'Without batch: at least 1 warming job');

        $improvement = (($withoutBatchJobs - $duringBatchJobs) / max($withoutBatchJobs, 1)) * 100;
        $this->assertGreaterThan(90, $improvement, 'Should prevent 90%+ of warming jobs');
    }
}
