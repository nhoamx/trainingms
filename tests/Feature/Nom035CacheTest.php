<?php

namespace Tests\Feature;

use App\Jobs\WarmOrganizationReportCache;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Services\OrganizationReportCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Nom035CacheTest extends TestCase
{
    use DatabaseTransactions;

    protected OrganizationReportCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = app(OrganizationReportCacheService::class);
    }

    public function test_nom035_dashboard_uses_cache_on_first_load(): void
    {
        $organization = Organization::factory()->create();

        // Create NOM-035 evaluations
        PaperEvaluation::factory()->count(5)->create([
            'organization_id' => $organization->id,
            'evaluation_type' => 'referencia_iii',
            'processing_status' => 'completed',
        ]);

        // Create admin user with proper role
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = \App\Models\User::factory()->create();
        $user->assignRole($adminRole);

        // Clear cache
        Cache::flush();

        // First visit: should compute and cache
        $response = $this->actingAs($user)
            ->get(route('organization.dashboard.nom-035', $organization));

        $response->assertStatus(200);

        // Cache should now be populated
        $this->assertTrue(Cache::has($this->cacheService->getNom035DomainsCacheKey($organization->id)));
        $this->assertTrue(Cache::has($this->cacheService->getNom035CategoriesCacheKey($organization->id)));
        $this->assertTrue(Cache::has($this->cacheService->getNom035DimensionsCacheKey($organization->id)));
        $this->assertTrue(Cache::has($this->cacheService->getNom035QuestionsCacheKey($organization->id)));
        $this->assertTrue(Cache::has($this->cacheService->getNom035BlocksCacheKey($organization->id)));
        $this->assertTrue(Cache::has($this->cacheService->getNom035GlobalCacheKey($organization->id)));
    }

    public function test_nom035_cache_is_invalidated_when_evaluation_created(): void
    {
        $organization = Organization::factory()->create();

        // Create initial evaluation and populate cache
        PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'evaluation_type' => 'referencia_iii',
            'processing_status' => 'completed',
        ]);

        // Populate cache
        Cache::forever($this->cacheService->getNom035DomainsCacheKey($organization->id), ['cached' => true]);
        $this->assertTrue(Cache::has($this->cacheService->getNom035DomainsCacheKey($organization->id)));

        // Create new evaluation (should invalidate cache)
        PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'evaluation_type' => 'referencia_iii',
            'processing_status' => 'completed',
        ]);

        // Cache should be invalidated
        $this->assertFalse(Cache::has($this->cacheService->getNom035DomainsCacheKey($organization->id)));
        $this->assertFalse(Cache::has($this->cacheService->getNom035CategoriesCacheKey($organization->id)));
        $this->assertFalse(Cache::has($this->cacheService->getNom035DimensionsCacheKey($organization->id)));
        $this->assertFalse(Cache::has($this->cacheService->getNom035QuestionsCacheKey($organization->id)));
        $this->assertFalse(Cache::has($this->cacheService->getNom035BlocksCacheKey($organization->id)));
        $this->assertFalse(Cache::has($this->cacheService->getNom035GlobalCacheKey($organization->id)));
    }

    public function test_warming_job_warms_nom035_cache(): void
    {
        $organization = Organization::factory()->create();

        // Create NOM-035 evaluations
        PaperEvaluation::factory()->count(10)->create([
            'organization_id' => $organization->id,
            'evaluation_type' => 'referencia_iii',
            'processing_status' => 'completed',
        ]);

        // Clear cache
        Cache::flush();

        // Execute warming job
        $job = new WarmOrganizationReportCache($organization);
        $job->handle(
            app(OrganizationReportCacheService::class),
            app(\App\Services\LikertScoreService::class),
            app(\App\Services\PaperEvaluationScoreService::class),
            app(\App\Services\Nom035DomainCalculationService::class)
        );

        // All NOM-035 caches should be populated
        $this->assertTrue(Cache::has($this->cacheService->getNom035DomainsCacheKey($organization->id)));
        $this->assertTrue(Cache::has($this->cacheService->getNom035CategoriesCacheKey($organization->id)));
        $this->assertTrue(Cache::has($this->cacheService->getNom035DimensionsCacheKey($organization->id)));
        $this->assertTrue(Cache::has($this->cacheService->getNom035QuestionsCacheKey($organization->id)));
        $this->assertTrue(Cache::has($this->cacheService->getNom035BlocksCacheKey($organization->id)));
        $this->assertTrue(Cache::has($this->cacheService->getNom035GlobalCacheKey($organization->id)));
    }

    public function test_nom035_dashboard_performance_improvement_with_cache(): void
    {
        $organization = Organization::factory()->create();

        // Create many evaluations to make computation expensive
        PaperEvaluation::factory()->count(50)->create([
            'organization_id' => $organization->id,
            'evaluation_type' => 'referencia_iii',
            'processing_status' => 'completed',
        ]);

        // Create admin user with proper role
        $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = \App\Models\User::factory()->create();
        $user->assignRole($adminRole);

        // Clear cache
        Cache::flush();

        // First load: compute everything (slower)
        $startTime = microtime(true);
        $this->actingAs($user)->get(route('organization.dashboard.nom-035', $organization));
        $firstLoadTime = microtime(true) - $startTime;

        // Second load: use cache (faster)
        $startTime = microtime(true);
        $this->actingAs($user)->get(route('organization.dashboard.nom-035', $organization));
        $secondLoadTime = microtime(true) - $startTime;

        // Second load should be significantly faster
        $this->assertLessThan($firstLoadTime, $secondLoadTime);
        $improvement = (($firstLoadTime - $secondLoadTime) / $firstLoadTime) * 100;
        $this->assertGreaterThan(20, $improvement, 'Cache should improve load time by at least 20%');
    }
}
