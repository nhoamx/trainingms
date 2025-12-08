<?php

namespace Tests\Feature;

use App\Models\DemographicData;
use App\Models\EvaluationComment;
use App\Models\EvaluationCustomField;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use App\Services\OrganizationReportCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReportCacheTest extends TestCase
{
    use DatabaseTransactions;

    private OrganizationReportCacheService $cacheService;

    private Organization $organization;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheService = app(OrganizationReportCacheService::class);
        $this->organization = Organization::factory()->create();
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');

        // Prevent cache warming jobs from being dispatched during tests
        Queue::fake();
    }

    public function test_cache_keys_are_generated_correctly(): void
    {
        $orgId = $this->organization->id;

        $likertKey = $this->cacheService->getLikertReportCacheKey($orgId);
        $listKey = $this->cacheService->getListResultsCacheKey($orgId);
        $foliosKey = $this->cacheService->getMissingFoliosCacheKey($orgId);

        $this->assertStringContainsString((string) $orgId, $likertKey);
        $this->assertStringContainsString((string) $orgId, $listKey);
        $this->assertStringContainsString((string) $orgId, $foliosKey);
        $this->assertStringContainsString('likert', $likertKey);
        $this->assertStringContainsString('list', $listKey);
        $this->assertStringContainsString('missing_folios', $foliosKey);
    }

    public function test_likert_report_cache_remembers_data(): void
    {
        $testData = ['test' => 'data', 'evaluations' => []];

        $result = $this->cacheService->rememberLikertReport($this->organization->id, fn () => $testData);

        $this->assertEquals($testData, $result);

        // Verify it's cached
        $cacheKey = $this->cacheService->getLikertReportCacheKey($this->organization->id);
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertEquals($testData, Cache::get($cacheKey));
    }

    public function test_list_results_cache_remembers_data(): void
    {
        $testData = ['evaluationGroups' => [], 'summary' => []];

        $result = $this->cacheService->rememberListResults($this->organization->id, fn () => $testData);

        $this->assertEquals($testData, $result);

        // Verify it's cached
        $cacheKey = $this->cacheService->getListResultsCacheKey($this->organization->id);
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_forget_organization_caches_clears_all_caches(): void
    {
        // Set up some cache data
        Cache::forever($this->cacheService->getLikertReportCacheKey($this->organization->id), ['test']);
        Cache::forever($this->cacheService->getListResultsCacheKey($this->organization->id), ['test']);
        Cache::forever($this->cacheService->getMissingFoliosCacheKey($this->organization->id), ['test']);

        // Forget all caches (without warming)
        $this->cacheService->forgetOrganizationCaches($this->organization->id, false);

        // Verify all are cleared
        $this->assertFalse(Cache::has($this->cacheService->getLikertReportCacheKey($this->organization->id)));
        $this->assertFalse(Cache::has($this->cacheService->getListResultsCacheKey($this->organization->id)));
        $this->assertFalse(Cache::has($this->cacheService->getMissingFoliosCacheKey($this->organization->id)));
    }

    public function test_paper_evaluation_created_clears_cache(): void
    {
        // Set up cache
        Cache::forever($this->cacheService->getLikertReportCacheKey($this->organization->id), ['cached']);

        // Create a paper evaluation - observer should clear cache
        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'evaluation_type' => 'likert',
            'processing_status' => 'completed',
        ]);

        // Cache should be cleared
        $this->assertFalse(Cache::has($this->cacheService->getLikertReportCacheKey($this->organization->id)));
    }

    public function test_paper_evaluation_updated_clears_cache(): void
    {
        // Create evaluation first
        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'evaluation_type' => 'likert',
            'processing_status' => 'completed',
        ]);

        // Set up cache after creation (since creation also clears)
        Cache::forever($this->cacheService->getLikertReportCacheKey($this->organization->id), ['cached']);

        // Update the evaluation
        $evaluation->update(['evaluee_name' => 'Updated Name']);

        // Cache should be cleared
        $this->assertFalse(Cache::has($this->cacheService->getLikertReportCacheKey($this->organization->id)));
    }

    public function test_paper_evaluation_deleted_clears_cache(): void
    {
        // Create evaluation first
        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'evaluation_type' => 'likert',
            'processing_status' => 'completed',
        ]);

        // Set up cache after creation
        Cache::forever($this->cacheService->getLikertReportCacheKey($this->organization->id), ['cached']);

        // Delete the evaluation (soft delete)
        $evaluation->delete();

        // Cache should be cleared
        $this->assertFalse(Cache::has($this->cacheService->getLikertReportCacheKey($this->organization->id)));
    }

    public function test_demographic_data_changes_clear_cache(): void
    {
        // Create evaluation first
        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'evaluation_type' => 'likert',
            'processing_status' => 'completed',
        ]);

        // Set up cache after creation
        Cache::forever($this->cacheService->getLikertReportCacheKey($this->organization->id), ['cached']);

        // Create demographic data - should clear cache
        DemographicData::create([
            'paper_evaluation_id' => $evaluation->id,
            'gender' => 'M',
        ]);

        // Cache should be cleared
        $this->assertFalse(Cache::has($this->cacheService->getLikertReportCacheKey($this->organization->id)));
    }

    public function test_custom_field_changes_clear_cache(): void
    {
        // Create evaluation first
        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'evaluation_type' => 'likert',
            'processing_status' => 'completed',
        ]);

        // Set up cache after creation
        Cache::forever($this->cacheService->getLikertReportCacheKey($this->organization->id), ['cached']);

        // Create custom field - should clear cache
        EvaluationCustomField::create([
            'paper_evaluation_id' => $evaluation->id,
            'field_key' => 'test_field',
            'key_label' => 'Test Field',
            'value' => 'Test Value',
        ]);

        // Cache should be cleared
        $this->assertFalse(Cache::has($this->cacheService->getLikertReportCacheKey($this->organization->id)));
    }

    public function test_comment_changes_clear_cache(): void
    {
        // Create evaluation first
        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'evaluation_type' => 'likert',
            'processing_status' => 'completed',
        ]);

        // Set up cache after creation
        Cache::forever($this->cacheService->getLikertReportCacheKey($this->organization->id), ['cached']);

        // Create comment - should clear cache
        EvaluationComment::create([
            'paper_evaluation_id' => $evaluation->id,
            'factor' => 'Liderazgo',
            'comment' => 'Test comment',
        ]);

        // Cache should be cleared
        $this->assertFalse(Cache::has($this->cacheService->getLikertReportCacheKey($this->organization->id)));
    }

    public function test_likert_report_page_loads_with_caching(): void
    {
        // Create some Likert evaluations
        PaperEvaluation::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
            'evaluation_type' => 'likert',
            'processing_status' => 'completed',
            'source' => 'paper',
            'likert_answers' => [
                'questions' => [
                    '1' => 'A',
                    '2' => 'B',
                    '3' => 'C',
                ],
            ],
        ]);

        // First request - should compute and cache
        $response = $this->actingAs($this->user)
            ->get(route('organization.likert.report', $this->organization));

        $response->assertStatus(200);

        // Verify cache was populated
        $this->assertTrue($this->cacheService->hasLikertReportCache($this->organization->id));
    }

    public function test_list_results_page_loads_with_caching(): void
    {
        // Create some evaluations
        PaperEvaluation::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
            'evaluation_type' => 'referencia_iii',
            'processing_status' => 'completed',
            'source' => 'paper',
        ]);

        // First request - should compute and cache
        $response = $this->actingAs($this->user)
            ->get(route('organization.results.list', $this->organization));

        $response->assertStatus(200);

        // Verify cache was populated
        $this->assertTrue($this->cacheService->hasListResultsCache($this->organization->id));
    }
}
