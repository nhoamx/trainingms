<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Dimension; // Assuming a Category model exists for names
use App\Models\Domain; // Add Domain model
use App\Models\Evaluation; // Add Dimension model
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB; // For debugging if needed
use Illuminate\Support\Facades\Log;

class ReportService
{
    /**
     * Generates a report grouping question answers by category for a specific reference guide.
     */
    public function generateByCategory(string $referenceGuide = 'III'): Collection
    {
        // Use COALESCE to treat NULL answers as 'INVALID'
        $results = Question::where('reference_guide', $referenceGuide)
            ->select(
                'category_id',
                DB::raw("COALESCE(answer, 'INVALID') as answer_group"), // Group NULLs as 'INVALID'
                DB::raw('count(*) as count')
            )
            ->groupBy('category_id', 'answer_group')
            ->get();

        $categoryIds = $results->pluck('category_id')->unique()->filter();
        $categories = Category::whereIn('id', $categoryIds)->pluck('name', 'id');

        // Group results by category ID first
        $groupedResults = $results->groupBy('category_id');

        // Structure the results as a collection of objects with id, name, and answers
        $report = collect();
        foreach ($groupedResults as $categoryId => $group) {
            if (empty($categoryId)) {
                continue;
            } // Skip entries without a category_id if necessary

            $answers = $group->mapWithKeys(function ($item) {
                return [$item->answer_group => $item->count];
            });

            $report->push([
                'id' => $categoryId,
                'name' => $categories->get($categoryId, 'Unknown Category'),
                'answers' => $answers,
            ]);
        }

        // Optionally sort by category name if desired
        // $report = $report->sortBy('name')->values();

        return $report;
    }

    /**
     * Generates a report grouping question answers by domain, optionally filtered by category.
     */
    public function generateByDomain(string $referenceGuide = 'III', ?string $categoryId = null): Collection
    {
        $query = Question::where('reference_guide', $referenceGuide);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $results = $query->select(
            'domain_id',
            DB::raw("COALESCE(answer, 'INVALID') as answer_group"),
            DB::raw('count(*) as count')
        )
            ->groupBy('domain_id', 'answer_group')
            ->get();

        $domainIds = $results->pluck('domain_id')->unique()->filter();
        $domains = Domain::whereIn('id', $domainIds)->pluck('name', 'id');

        // Group results by domain ID first
        $groupedResults = $results->groupBy('domain_id');

        // Structure the results as a collection of objects
        $report = collect();
        foreach ($groupedResults as $domainId => $group) {
            if (empty($domainId)) {
                continue;
            }

            $answers = $group->mapWithKeys(function ($item) {
                return [$item->answer_group => $item->count];
            });

            $report->push([
                'id' => $domainId,
                'name' => $domains->get($domainId, 'Unknown Domain'),
                'answers' => $answers,
            ]);
        }

        // $report = $report->sortBy('name')->values(); // Optional sort

        return $report;
    }

    /**
     * Generates a report grouping question answers by dimension, optionally filtered by domain.
     */
    public function generateByDimension(string $referenceGuide = 'III', ?string $domainId = null): Collection
    {
        $query = Question::where('reference_guide', $referenceGuide);

        if ($domainId) {
            $query->where('domain_id', $domainId);
        }

        $results = $query->select(
            'dimension_id',
            DB::raw("COALESCE(answer, 'INVALID') as answer_group"),
            DB::raw('count(*) as count')
        )
            ->groupBy('dimension_id', 'answer_group')
            ->get();

        $dimensionIds = $results->pluck('dimension_id')->unique()->filter();
        $dimensions = Dimension::whereIn('id', $dimensionIds)->pluck('name', 'id');

        // Group results by dimension ID first
        $groupedResults = $results->groupBy('dimension_id');

        // Structure the results as a collection of objects
        $report = collect();
        foreach ($groupedResults as $dimensionId => $group) {
            if (empty($dimensionId)) {
                continue;
            }

            $answers = $group->mapWithKeys(function ($item) {
                return [$item->answer_group => $item->count];
            });

            $report->push([
                'id' => $dimensionId,
                'name' => $dimensions->get($dimensionId, 'Unknown Dimension'),
                'answers' => $answers,
            ]);
        }

        // $report = $report->sortBy('name')->values(); // Optional sort

        return $report;
    }

    /**
     * Calculates category scores per person and qualifies them based on predefined ranges.
     * Returns the count of people in each qualification level per category.
     */
    public function calculateCategoryQualifications(string $referenceGuide = 'III', $organizationId = null): Collection
    {
        // 1. Get all categories
        $categories = Category::pluck('name', 'id');
        if ($categories->isEmpty()) {
            Log::warning('No categories found in database.');

            return collect();
        }
        $qualificationRanges = $this->getCategoryQualificationRanges();

        // 2. Get scores per person per category_id
        $scoresPerPerson = Question::where('reference_guide', $referenceGuide)
            ->whereNotNull('personal_id')->whereNotNull('value')
            ->select('personal_id', 'category_id', DB::raw('SUM(value) as total_score'))
            ->groupBy('personal_id', 'category_id')
            ->get();
        if ($scoresPerPerson->isEmpty()) {
            return collect();
        }

        // 3. Qualify each person's score
        $qualifiedScores = $scoresPerPerson->map(function ($score) use ($categories, $qualificationRanges) {
            $categoryName = $categories->get($score->category_id);
            $qualificationLevel = 'Desconocido';
            if ($categoryName && isset($qualificationRanges[$categoryName])) {
                $ranges = $qualificationRanges[$categoryName];
                $scoreValue = $score->total_score;
                // Determine level based on scoreValue and ranges['Level']['min']/['max']
                if ($scoreValue < $ranges['Nulo']['min']) {
                    $qualificationLevel = 'Nulo';
                } // Should not happen with min 0? Check logic if needed.
                elseif ($scoreValue <= $ranges['Nulo']['max']) {
                    $qualificationLevel = 'Nulo';
                } elseif ($scoreValue <= $ranges['Bajo']['max']) {
                    $qualificationLevel = 'Bajo';
                } elseif ($scoreValue <= $ranges['Medio']['max']) {
                    $qualificationLevel = 'Medio';
                } elseif ($scoreValue <= $ranges['Alto']['max']) {
                    $qualificationLevel = 'Alto';
                } else {
                    $qualificationLevel = 'Muy Alto';
                }
            } else {
                Log::warning("Category name or qualification range not found for category_id: {$score->category_id} (Name: {$categoryName})");
            }

            return [
                'category_id' => $score->category_id,
                'category_name' => $categoryName ?? 'Categoría Desconocida',
                'personal_id' => $score->personal_id,
                'qualification_level' => $qualificationLevel,
            ];
        })->filter(fn ($item) => $item['qualification_level'] !== 'Desconocido');

        // 4. Count people per qualification level per category
        // Group by category_id first to easily retain it
        $finalReportData = [];
        $qualifiedScores->groupBy('category_id')
            ->each(function ($categoryGroup, $categoryId) use (&$finalReportData, $categories) {
                if (empty($categoryId) || ! $categories->has($categoryId)) {
                    return;
                } // Skip if no valid category ID

                $categoryName = $categories->get($categoryId);
                $levelCounts = $categoryGroup->groupBy('qualification_level')
                    ->map(fn ($levelGroup) => $levelGroup->count());

                $allLevels = ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'];
                $completeCounts = collect($allLevels)->mapWithKeys(function ($level) use ($levelCounts) {
                    return [$level => $levelCounts->get($level, 0)];
                });

                // Add to the final array
                $finalReportData[] = [
                    'id' => $categoryId,
                    'name' => $categoryName,
                    'qualifications' => $completeCounts->toArray(), // Ensure it's an array for consistency
                ];
            });

        // Sort by category name
        usort($finalReportData, fn ($a, $b) => strcmp($a['name'], $b['name']));

        // Return as a collection of arrays/objects
        return collect($finalReportData);
    }

    /**
     * Helper function to define the qualification ranges.
     * Could be moved to a config file or database table later.
     * Based on Image 2 provided by the user.
     */
    private function getCategoryQualificationRanges(): array
    {
        // IMPORTANT: Ensure these names EXACTLY match the names in your 'categories' table.
        return [
            'Ambiente de trabajo' => [
                'Nulo' => ['min' => 0, 'max' => 4],     // Ccat < 5
                'Bajo' => ['min' => 5, 'max' => 8],     // 5 <= Ccat < 9
                'Medio' => ['min' => 9, 'max' => 10],   // 9 <= Ccat < 11
                'Alto' => ['min' => 11, 'max' => 13],  // 11 <= Ccat < 14
                'Muy Alto' => ['min' => 14, 'max' => PHP_INT_MAX], // Ccat >= 14
            ],
            'Factores propios de la actividad' => [
                'Nulo' => ['min' => 0, 'max' => 14],   // Ccat < 15
                'Bajo' => ['min' => 15, 'max' => 29],  // 15 <= Ccat < 30
                'Medio' => ['min' => 30, 'max' => 44], // 30 <= Ccat < 45
                'Alto' => ['min' => 45, 'max' => 59],  // 45 <= Ccat < 60
                'Muy Alto' => ['min' => 60, 'max' => PHP_INT_MAX], // Ccat >= 60
            ],
            'Organización del tiempo de trabajo' => [
                'Nulo' => ['min' => 0, 'max' => 4],    // Ccat < 5
                'Bajo' => ['min' => 5, 'max' => 6],    // 5 <= Ccat < 7
                'Medio' => ['min' => 7, 'max' => 9],   // 7 <= Ccat < 10
                'Alto' => ['min' => 10, 'max' => 12], // 10 <= Ccat < 13
                'Muy Alto' => ['min' => 13, 'max' => PHP_INT_MAX], // Ccat >= 13
            ],
            'Liderazgo y relaciones en el trabajo' => [
                'Nulo' => ['min' => 0, 'max' => 13],   // Ccat < 14
                'Bajo' => ['min' => 14, 'max' => 28],  // 14 <= Ccat < 29
                'Medio' => ['min' => 29, 'max' => 41], // 29 <= Ccat < 42
                'Alto' => ['min' => 42, 'max' => 57],  // 42 <= Ccat < 58
                'Muy Alto' => ['min' => 58, 'max' => PHP_INT_MAX], // Ccat >= 58
            ],
            'Entorno organizacional' => [
                'Nulo' => ['min' => 0, 'max' => 9],    // Ccat < 10
                'Bajo' => ['min' => 10, 'max' => 13],  // 10 <= Ccat < 14
                'Medio' => ['min' => 14, 'max' => 17], // 14 <= Ccat < 18
                'Alto' => ['min' => 18, 'max' => 22],  // 18 <= Ccat < 23
                'Muy Alto' => ['min' => 23, 'max' => PHP_INT_MAX], // Ccat >= 23
            ],
            // Add other categories if they exist
        ];
    }

    /**
     * Gets the distribution of raw answers for a specific category.
     * Now includes both answer counts and unique person counts.
     */
    public function getCategoryAnswerDistribution(string $categoryId, string $referenceGuide = 'III'): Collection
    {
        // Get personal_ids with Guide III evaluations to ensure consistency with people lists
        $guideIIIPersonalIds = Evaluation::where('reference_guide', 'III')
            ->whereNotNull('personal_id')
            ->pluck('personal_id')
            ->unique()
            ->filter();

        if ($guideIIIPersonalIds->isEmpty()) {
            Log::warning("No personal_ids found with Guide III evaluations for category distribution {$categoryId}.");

            return collect([
                'answers' => $this->getEmptyAnswerDistribution(),
                'people' => $this->getEmptyAnswerDistribution(),
            ]); // Return empty distribution structure
        }

        // Original query: Count of individual answers grouped by answer type
        $answerResults = Question::where('reference_guide', $referenceGuide)
            ->where('category_id', $categoryId)
            ->whereIn('personal_id', $guideIIIPersonalIds) // Filter by people with Guide III evals
            ->select(
                DB::raw("COALESCE(answer, 'INVALID') as answer_group"),
                DB::raw('count(*) as count')
            )
            ->groupBy('answer_group')
            ->pluck('count', 'answer_group'); // Returns a collection like ['A' => 10, 'B' => 25, ...]

        // New query: Count of unique persons grouped by answer type
        // For each person, count them once per answer type they've given in this category
        $peopleResults = Question::where('reference_guide', $referenceGuide)
            ->where('category_id', $categoryId)
            ->whereIn('personal_id', $guideIIIPersonalIds)
            ->select(
                'personal_id',
                DB::raw("COALESCE(answer, 'INVALID') as answer_group")
            )
            ->distinct() // Only count each person once per answer type
            ->get()
            ->groupBy('answer_group')
            ->map(function ($group) {
                return $group->count(); // Count unique persons for this answer type
            });

        // Ensure both results have complete distribution (all possible answer keys)
        $completeAnswerResults = $this->ensureCompleteDistribution($answerResults);
        $completePeopleResults = $this->ensureCompleteDistribution($peopleResults);

        // Return both distributions in a single collection
        return collect([
            'answers' => $completeAnswerResults,
            'people' => $completePeopleResults,
        ]);
    }

    /**
     * Helper to ensure all standard answer keys exist in a distribution collection.
     */
    private function ensureCompleteDistribution(Collection $results): Collection
    {
        $allAnswers = ['A', 'B', 'C', 'D', 'E', 'INVALID'];

        return collect($allAnswers)->mapWithKeys(function ($answer) use ($results) {
            return [$answer => $results->get($answer, 0)];
        });
    }

    /**
     * Helper to get an empty distribution structure.
     */
    private function getEmptyAnswerDistribution(): array
    {
        return ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'INVALID' => 0];
    }

    /**
     * Gets the list of distinct people details (ID, Eval ID, Org ID) for a specific answer within a category.
     *
     * @param  string  $answerKey  ('A', 'B', 'C', 'D', 'E', or 'INVALID')
     * @return Collection List of objects [{ personal_id, guide_iii_evaluation_id, organization_id }]
     */
    public function getPeopleWithAnswerInCategory(string $categoryId, string $answerKey, string $referenceGuide = 'III'): Collection
    {
        $query = Question::query()
            // Join evaluations table twice: once for the context eval, once for Guide III eval
            ->join('evaluations as eval_context', 'questions.evaluation_id', '=', 'eval_context.id')
            ->join('evaluations as eval_guide_iii', 'questions.personal_id', '=', 'eval_guide_iii.personal_id')
            ->where('questions.reference_guide', $referenceGuide)
            ->where('questions.category_id', $categoryId)
            ->whereNotNull('questions.personal_id')
            ->where('eval_guide_iii.reference_guide', 'III')
            ->groupBy('personal_id'); // Ensure the second join targets Guide III

        if ($answerKey === 'INVALID') {
            $query->whereNull('questions.answer');
        } else {
            if (in_array($answerKey, ['A', 'B', 'C', 'D', 'E'])) {
                $query->where('questions.answer', $answerKey);
            } else {
                Log::warning("Invalid answer key requested for people list: {$answerKey}");

                return collect();
            }
        }

        // First get all the personal_ids that match our criteria
        $personalIds = $query->select('questions.personal_id')
            ->distinct()
            ->pluck('personal_id');

        // Then find the most recent evaluation for each person
        $peopleDetails = collect();

        foreach ($personalIds as $personalId) {
            $evaluation = \App\Models\Evaluation::where('personal_id', $personalId)
                ->where('reference_guide', 'III')
                ->orderBy('created_at', 'desc') // Get the most recent evaluation
                ->first();

            if ($evaluation) {
                $peopleDetails->push([
                    'personal_id' => $personalId,
                    'guide_iii_evaluation_id' => $evaluation->id,
                    'organization_id' => $evaluation->organization_id,
                ]);
            }
        }

        return $peopleDetails; // Returns collection of objects
    }

    /**
     * Gets the list of distinct people details (ID, Eval ID, Org ID) for a specific answer within a DOMAIN.
     *
     * @param  string  $answerKey  ('A', 'B', 'C', 'D', 'E', or 'INVALID')
     * @return Collection List of objects [{ personal_id, guide_iii_evaluation_id, organization_id }]
     */
    public function getPeopleWithAnswerInDomain(string $domainId, string $answerKey, string $referenceGuide = 'III'): Collection
    {
        $query = Question::query()
            // Join evaluations table twice: once for the context eval, once for Guide III eval
            ->join('evaluations as eval_context', 'questions.evaluation_id', '=', 'eval_context.id')
            ->join('evaluations as eval_guide_iii', 'questions.personal_id', '=', 'eval_guide_iii.personal_id')
            ->where('questions.reference_guide', $referenceGuide)
            ->where('questions.domain_id', $domainId) // Filter by domain_id
            ->whereNotNull('questions.personal_id')
            ->where('eval_guide_iii.reference_guide', 'III') // Ensure Guide III target
            ->groupBy('personal_id');

        if ($answerKey === 'INVALID') {
            $query->whereNull('questions.answer');
        } else {
            if (in_array($answerKey, ['A', 'B', 'C', 'D', 'E'])) {
                $query->where('questions.answer', $answerKey);
            } else {
                Log::warning("Invalid answer key requested for domain people list: {$answerKey}");

                return collect();
            }
        }

        // First get all the personal_ids that match our criteria
        $personalIds = $query->select('questions.personal_id')
            ->distinct()
            ->pluck('personal_id');

        // Then find the most recent evaluation for each person
        $peopleDetails = collect();

        foreach ($personalIds as $personalId) {
            $evaluation = \App\Models\Evaluation::where('personal_id', $personalId)
                ->where('reference_guide', 'III')
                ->orderBy('created_at', 'desc') // Get the most recent evaluation
                ->first();

            if ($evaluation) {
                $peopleDetails->push([
                    'personal_id' => $personalId,
                    'guide_iii_evaluation_id' => $evaluation->id,
                    'organization_id' => $evaluation->organization_id,
                ]);
            }
        }

        return $peopleDetails;
    }

    /**
     * Calculates domain scores per person and qualifies them based on predefined ranges.
     * Returns a collection of objects [{id, name, qualifications: {Nulo, ...}}].
     */
    public function calculateDomainQualifications(string $referenceGuide = 'III'): Collection
    {
        // 1. Get all domains with their category_id
        // Assuming Domain model has category_id. Adjust if relationship is named differently.
        $domains = Domain::select('id', 'name', 'category_id')->get()->keyBy('id');
        if ($domains->isEmpty()) {
            Log::warning('No domains found in database.');

            return collect();
        }
        $qualificationRanges = $this->getDomainQualificationRanges();

        // 2. Get scores per person per domain_id
        $scoresPerPerson = Question::where('reference_guide', $referenceGuide)
            ->whereNotNull('personal_id')->whereNotNull('value')
            // Ensure we only get scores for existing domains
            ->whereIn('domain_id', $domains->keys())
            ->select('personal_id', 'domain_id', DB::raw('SUM(value) as total_score'))
            ->groupBy('personal_id', 'domain_id')
            ->get();
        if ($scoresPerPerson->isEmpty()) {
            return collect();
        }

        // 3. Qualify scores
        $qualifiedScores = $scoresPerPerson->map(function ($score) use ($domains, $qualificationRanges) {
            // Get domain details (including category_id) from the collection fetched earlier
            $domain = $domains->get($score->domain_id);
            $domainName = $domain ? $domain->name : null;
            $categoryId = $domain ? $domain->category_id : null; // Get category_id
            $qualificationLevel = 'Desconocido';

            if ($domainName && $categoryId && isset($qualificationRanges[$domainName])) {
                $ranges = $qualificationRanges[$domainName];
                $scoreValue = $score->total_score;
                if ($scoreValue < $ranges['Nulo']['min']) {
                    $qualificationLevel = 'Nulo';
                } elseif ($scoreValue <= $ranges['Nulo']['max']) {
                    $qualificationLevel = 'Nulo';
                } elseif ($scoreValue <= $ranges['Bajo']['max']) {
                    $qualificationLevel = 'Bajo';
                } elseif ($scoreValue <= $ranges['Medio']['max']) {
                    $qualificationLevel = 'Medio';
                } elseif ($scoreValue <= $ranges['Alto']['max']) {
                    $qualificationLevel = 'Alto';
                } else {
                    $qualificationLevel = 'Muy Alto';
                }
            } else {
                Log::warning("Domain details or range not found for domain_id: {$score->domain_id}");
            }

            return [
                'domain_id' => $score->domain_id,
                'category_id' => $categoryId, // Include category_id
                'domain_name' => $domainName ?? 'Dominio Desconocido',
                'personal_id' => $score->personal_id,
                'qualification_level' => $qualificationLevel,
            ];
        })->filter(fn ($item) => $item['qualification_level'] !== 'Desconocido' && ! is_null($item['category_id']));

        // 4. Count people per level per domain
        $finalReportData = [];
        $qualifiedScores->groupBy('domain_id')
            ->each(function ($domainGroup, $domainId) use (&$finalReportData, $domains) {
                $domain = $domains->get($domainId); // Get domain object again
                if (! $domain) {
                    return;
                }

                $levelCounts = $domainGroup->groupBy('qualification_level')
                    ->map(fn ($levelGroup) => $levelGroup->count());
                $allLevels = ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'];
                $completeCounts = collect($allLevels)->mapWithKeys(fn ($level) => [$level => $levelCounts->get($level, 0)]);

                $finalReportData[] = [
                    'id' => $domainId,
                    'name' => $domain->name,
                    'category_id' => $domain->category_id, // Add category_id to the final output
                    'qualifications' => $completeCounts->toArray(),
                ];
            });

        usort($finalReportData, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return collect($finalReportData);
    }

    /**
     * Gets the distribution of raw answers for a specific domain.
     */
    public function getDomainAnswerDistribution(string $domainId, string $referenceGuide = 'III'): Collection
    {
        // Get personal_ids with Guide III evaluations
        $guideIIIPersonalIds = Evaluation::where('reference_guide', 'III')
            ->whereNotNull('personal_id')
            ->pluck('personal_id')
            ->unique()
            ->filter();

        if ($guideIIIPersonalIds->isEmpty()) {
            Log::warning("No personal_ids found with Guide III evaluations for domain distribution {$domainId}.");

            return collect([
                'answers' => $this->getEmptyAnswerDistribution(),
                'people' => $this->getEmptyAnswerDistribution(),
            ]);
        }

        // Original query: Count of individual answers grouped by answer type
        $answerResults = Question::where('reference_guide', $referenceGuide)
            ->where('domain_id', $domainId) // Filter by domain_id
            ->whereIn('personal_id', $guideIIIPersonalIds) // Filter by people with Guide III evals
            ->select(
                DB::raw("COALESCE(answer, 'INVALID') as answer_group"),
                DB::raw('count(*) as count')
            )
            ->groupBy('answer_group')
            ->pluck('count', 'answer_group');

        // New query: Count of unique persons grouped by answer type
        $peopleResults = Question::where('reference_guide', $referenceGuide)
            ->where('domain_id', $domainId)
            ->whereIn('personal_id', $guideIIIPersonalIds)
            ->select(
                'personal_id',
                DB::raw("COALESCE(answer, 'INVALID') as answer_group")
            )
            ->distinct() // Only count each person once per answer type
            ->get()
            ->groupBy('answer_group')
            ->map(function ($group) {
                return $group->count(); // Count unique persons for this answer type
            });

        // Ensure both results have complete distribution
        $completeAnswerResults = $this->ensureCompleteDistribution($answerResults);
        $completePeopleResults = $this->ensureCompleteDistribution($peopleResults);

        return collect([
            'answers' => $completeAnswerResults,
            'people' => $completePeopleResults,
        ]);
    }

    /**
     * Helper function to define the DOMAIN qualification ranges.
     * Based on Image provided by the user.
     */
    private function getDomainQualificationRanges(): array
    {
        // IMPORTANT: Ensure these names EXACTLY match the names in your 'domains' table.
        return [
            'Condiciones en el ambiente de trabajo' => [
                'Nulo' => ['min' => 0, 'max' => 4],    // Cdom < 5
                'Bajo' => ['min' => 5, 'max' => 8],    // 5 <= Cdom < 9
                'Medio' => ['min' => 9, 'max' => 10],   // 9 <= Cdom < 11
                'Alto' => ['min' => 11, 'max' => 13],  // 11 <= Cdom < 14
                'Muy Alto' => ['min' => 14, 'max' => PHP_INT_MAX], // Cdom >= 14
            ],
            'Carga de trabajo' => [
                'Nulo' => ['min' => 0, 'max' => 14],   // Cdom < 15
                'Bajo' => ['min' => 15, 'max' => 20],  // 15 <= Cdom < 21
                'Medio' => ['min' => 21, 'max' => 26], // 21 <= Cdom < 27
                'Alto' => ['min' => 27, 'max' => 36],  // 27 <= Cdom < 37
                'Muy Alto' => ['min' => 37, 'max' => PHP_INT_MAX], // Cdom >= 37
            ],
            'Falta de control sobre el trabajo' => [
                'Nulo' => ['min' => 0, 'max' => 10],   // Cdom < 11
                'Bajo' => ['min' => 11, 'max' => 15],  // 11 <= Cdom < 16
                'Medio' => ['min' => 16, 'max' => 20], // 16 <= Cdom < 21
                'Alto' => ['min' => 21, 'max' => 24],  // 21 <= Cdom < 25
                'Muy Alto' => ['min' => 25, 'max' => PHP_INT_MAX], // Cdom >= 25
            ],
            'Jornada de trabajo' => [
                'Nulo' => ['min' => 0, 'max' => 0],    // Cdom < 1
                'Bajo' => ['min' => 1, 'max' => 1],    // 1 <= Cdom < 2
                'Medio' => ['min' => 2, 'max' => 3],   // 2 <= Cdom < 4
                'Alto' => ['min' => 4, 'max' => 5],    // 4 <= Cdom < 6
                'Muy Alto' => ['min' => 6, 'max' => PHP_INT_MAX], // Cdom >= 6
            ],
            'Interferencia en la relación trabajo-familia' => [
                'Nulo' => ['min' => 0, 'max' => 3],    // Cdom < 4
                'Bajo' => ['min' => 4, 'max' => 5],    // 4 <= Cdom < 6
                'Medio' => ['min' => 6, 'max' => 7],   // 6 <= Cdom < 8
                'Alto' => ['min' => 8, 'max' => 9],    // 8 <= Cdom < 10
                'Muy Alto' => ['min' => 10, 'max' => PHP_INT_MAX], // Cdom >= 10
            ],
            'Liderazgo' => [
                'Nulo' => ['min' => 0, 'max' => 8],    // Cdom < 9
                'Bajo' => ['min' => 9, 'max' => 11],   // 9 <= Cdom < 12
                'Medio' => ['min' => 12, 'max' => 15],  // 12 <= Cdom < 16
                'Alto' => ['min' => 16, 'max' => 19],  // 16 <= Cdom < 20
                'Muy Alto' => ['min' => 20, 'max' => PHP_INT_MAX], // Cdom >= 20
            ],
            'Relaciones en el trabajo' => [
                'Nulo' => ['min' => 0, 'max' => 9],    // Cdom < 10
                'Bajo' => ['min' => 10, 'max' => 12],   // 10 <= Cdom < 13
                'Medio' => ['min' => 13, 'max' => 16],  // 13 <= Cdom < 17
                'Alto' => ['min' => 17, 'max' => 20],  // 17 <= Cdom < 21
                'Muy Alto' => ['min' => 21, 'max' => PHP_INT_MAX], // Cdom >= 21
            ],
            'Violencia' => [
                'Nulo' => ['min' => 0, 'max' => 6],    // Cdom < 7
                'Bajo' => ['min' => 7, 'max' => 9],    // 7 <= Cdom < 10
                'Medio' => ['min' => 10, 'max' => 12],  // 10 <= Cdom < 13
                'Alto' => ['min' => 13, 'max' => 15],  // 13 <= Cdom < 16
                'Muy Alto' => ['min' => 16, 'max' => PHP_INT_MAX], // Cdom >= 16
            ],
            'Reconocimiento del desempeño' => [
                'Nulo' => ['min' => 0, 'max' => 5],    // Cdom < 6
                'Bajo' => ['min' => 6, 'max' => 9],    // 6 <= Cdom < 10
                'Medio' => ['min' => 10, 'max' => 13], // 10 <= Cdom < 14
                'Alto' => ['min' => 14, 'max' => 17],  // 14 <= Cdom < 18
                'Muy Alto' => ['min' => 18, 'max' => PHP_INT_MAX], // Cdom >= 18
            ],
            'Insuficiente sentido de pertenencia e inestabilidad' => [
                'Nulo' => ['min' => 0, 'max' => 3],    // Cdom < 4
                'Bajo' => ['min' => 4, 'max' => 5],    // 4 <= Cdom < 6
                'Medio' => ['min' => 6, 'max' => 7],   // 6 <= Cdom < 8
                'Alto' => ['min' => 8, 'max' => 9],    // 8 <= Cdom < 10
                'Muy Alto' => ['min' => 10, 'max' => PHP_INT_MAX], // Cdom >= 10
            ],
        ];
    }

    /**
     * Calculates dimension qualifications based on their parent domain's qualification level.
     * Filters for a specific domain ID.
     *
     * @param  string  $domainId  The ID of the parent domain.
     * @return Collection Collection of [{id, name, qualifications: {Nulo, ...}}]
     */
    public function calculateDimensionQualifications(string $domainId, string $referenceGuide = 'III'): Collection
    {
        // 1. Get all domains and their qualification ranges
        $domains = Domain::pluck('name', 'id');
        $domainQualificationRanges = $this->getDomainQualificationRanges();

        // 2. Get dimensions belonging to the specified domain
        $dimensions = Dimension::where('domain_id', $domainId)->pluck('name', 'id');
        if ($dimensions->isEmpty()) {
            return collect();
        }

        // 3. Get scores per person for the PARENT domain
        $scoresPerPersonForDomain = Question::where('reference_guide', $referenceGuide)
            ->where('domain_id', $domainId) // Filter by the specific domain
            ->whereNotNull('personal_id')->whereNotNull('value')
            ->select('personal_id', DB::raw('SUM(value) as total_score'))
            ->groupBy('personal_id')
            ->get();

        if ($scoresPerPersonForDomain->isEmpty()) {
            return collect();
        }

        // 4. Determine the qualification level for each person based on the PARENT domain score
        $domainName = $domains->get($domainId);
        $qualifiedPeople = collect(); // Store qualification level per person for this domain
        if ($domainName && isset($domainQualificationRanges[$domainName])) {
            $ranges = $domainQualificationRanges[$domainName];
            $qualifiedPeople = $scoresPerPersonForDomain->mapWithKeys(function ($score) use ($ranges) {
                $scoreValue = $score->total_score;
                $level = 'Muy Alto'; // Default to highest
                if ($scoreValue < $ranges['Nulo']['min']) {
                    $level = 'Nulo';
                } elseif ($scoreValue <= $ranges['Nulo']['max']) {
                    $level = 'Nulo';
                } elseif ($scoreValue <= $ranges['Bajo']['max']) {
                    $level = 'Bajo';
                } elseif ($scoreValue <= $ranges['Medio']['max']) {
                    $level = 'Medio';
                } elseif ($scoreValue <= $ranges['Alto']['max']) {
                    $level = 'Alto';
                }

                return [$score->personal_id => $level]; // Map: personal_id => qualification_level
            });
        } else {
            Log::warning("Domain name or range not found for domain ID {$domainId} during dimension calculation.");

            return collect(); // Cannot proceed without domain ranges
        }

        if ($qualifiedPeople->isEmpty()) { // No people qualified for this domain
            return collect();
        }

        // 5. Count how many people fall into each qualification level (overall for this domain)
        $levelCountsForDomain = $qualifiedPeople->groupBy(fn ($level) => $level)
            ->map(fn ($group) => $group->count());

        // 6. Assign the domain's level counts to EACH dimension belonging to it
        $finalReportData = [];
        $allLevels = ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'];
        foreach ($dimensions as $dimensionId => $dimensionName) {
            $completeCounts = collect($allLevels)->mapWithKeys(fn ($level) => [
                $level => $levelCountsForDomain->get($level, 0),
            ]);

            $finalReportData[] = [
                'id' => $dimensionId,
                'name' => $dimensionName,
                // Removed category_id as it's implicit from the domain filter
                'qualifications' => $completeCounts->toArray(),
            ];
        }

        // No need to sort here as it's already filtered for one domain's dimensions
        return collect($finalReportData);
    }

    /**
     * Gets the distribution of raw answers for a specific dimension.
     * (Similar to domain/category versions)
     */
    public function getDimensionAnswerDistribution(string $dimensionId, string $referenceGuide = 'III'): Collection
    {
        // Get personal_ids with Guide III evaluations
        $guideIIIPersonalIds = Evaluation::where('reference_guide', 'III')
            ->whereNotNull('personal_id')
            ->pluck('personal_id')
            ->unique()
            ->filter();

        if ($guideIIIPersonalIds->isEmpty()) {
            Log::warning("No personal_ids found with Guide III evaluations for dimension distribution {$dimensionId}.");

            return collect([
                'answers' => $this->getEmptyAnswerDistribution(),
                'people' => $this->getEmptyAnswerDistribution(),
            ]);
        }

        // Original query: Count of individual answers grouped by answer type
        $answerResults = Question::where('reference_guide', $referenceGuide)
            ->where('dimension_id', $dimensionId) // Filter by dimension_id
            ->whereIn('personal_id', $guideIIIPersonalIds) // Filter by people with Guide III evals
            ->select(
                DB::raw("COALESCE(answer, 'INVALID') as answer_group"),
                DB::raw('count(*) as count')
            )
            ->groupBy('answer_group')
            ->pluck('count', 'answer_group');

        // New query: Count of unique persons grouped by answer type
        $peopleResults = Question::where('reference_guide', $referenceGuide)
            ->where('dimension_id', $dimensionId)
            ->whereIn('personal_id', $guideIIIPersonalIds)
            ->select(
                'personal_id',
                DB::raw("COALESCE(answer, 'INVALID') as answer_group")
            )
            ->distinct() // Only count each person once per answer type
            ->get()
            ->groupBy('answer_group')
            ->map(function ($group) {
                return $group->count(); // Count unique persons for this answer type
            });

        // Ensure both results have complete distribution
        $completeAnswerResults = $this->ensureCompleteDistribution($answerResults);
        $completePeopleResults = $this->ensureCompleteDistribution($peopleResults);

        return collect([
            'answers' => $completeAnswerResults,
            'people' => $completePeopleResults,
        ]);
    }

    /**
     * Gets the list of distinct people details for a specific answer within a DIMENSION.
     *
     * @return Collection List of objects [{ personal_id, guide_iii_evaluation_id, organization_id }]
     */
    public function getPeopleWithAnswerInDimension(string $dimensionId, string $answerKey, string $referenceGuide = 'III'): Collection
    {
        $query = Question::query()
            // Join evaluations table twice: once for the context eval, once for Guide III eval
            ->join('evaluations as eval_context', 'questions.evaluation_id', '=', 'eval_context.id')
            ->join('evaluations as eval_guide_iii', 'questions.personal_id', '=', 'eval_guide_iii.personal_id')
            ->where('questions.reference_guide', $referenceGuide)
            ->where('questions.dimension_id', $dimensionId) // Filter by dimension_id
            ->whereNotNull('questions.personal_id')
            ->where('eval_guide_iii.reference_guide', 'III')
            ->groupBy('personal_id'); // Ensure Guide III target

        if ($answerKey === 'INVALID') {
            $query->whereNull('questions.answer');
        } else {
            if (in_array($answerKey, ['A', 'B', 'C', 'D', 'E'])) {
                $query->where('questions.answer', $answerKey);
            } else {
                Log::warning("Invalid answer key requested for dimension people list: {$answerKey}");

                return collect();
            }
        }

        // First get all the personal_ids that match our criteria
        $personalIds = $query->select('questions.personal_id')
            ->distinct()
            ->pluck('personal_id');

        // Then find the most recent evaluation for each person
        $peopleDetails = collect();

        foreach ($personalIds as $personalId) {
            $evaluation = \App\Models\Evaluation::where('personal_id', $personalId)
                ->where('reference_guide', 'III')
                ->orderBy('created_at', 'desc') // Get the most recent evaluation
                ->first();

            if ($evaluation) {
                $peopleDetails->push([
                    'personal_id' => $personalId,
                    'guide_iii_evaluation_id' => $evaluation->id,
                    'organization_id' => $evaluation->organization_id,
                ]);
            }
        }

        return $peopleDetails;
    }

    /**
     * Helper function to normalize strings for comparison.
     * Converts to lowercase, removes common accents, replaces underscores/hyphens with spaces.
     */
    private function normalizeForComparison(?string $str): string
    {
        if (is_null($str)) {
            return '';
        }

        $str = mb_strtolower($str, 'UTF-8'); // Use mb_strtolower for UTF-8
        $str = str_replace(['_', '-'], ' ', $str);

        // More comprehensive accent removal
        $unwanted_array = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N',
            // Add more if needed
        ];
        $str = strtr($str, $unwanted_array);

        // Remove extra whitespace
        $str = trim(preg_replace('/\s+/', ' ', $str));

        return $str;
    }

    /**
     * Calculate and structure demographic distributions based on Guide V answers.
     *
     * @param  array  $personalIds  Optional array of personal_ids to filter by. If empty, considers all.
     * @return array Array of objects, each with key, label, and data [{label, count, identifier}].
     */
    public function getDemographicDistributions(array $personalIds = []): array
    {

        // First, get all personal_ids that have a completed Guide III evaluation
        // This ensures counts only include people linkable from the people list
        $guideIIIPersonalIds = Evaluation::where('reference_guide', 'III')
            ->whereNotNull('personal_id')
            ->pluck('personal_id')
            ->unique()
            ->filter();

        if ($guideIIIPersonalIds->isEmpty()) {
            return [];
        }

        $query = Question::where('reference_guide', 'V');

        // Apply the Guide III filter
        $query->whereIn('personal_id', $guideIIIPersonalIds);

        // Apply the specific personalId filter if provided (e.g., for org scope)
        if (! empty($personalIds)) {
            $query->whereIn('personal_id', $personalIds);
        }

        // Fetch all relevant Guide V answers efficiently (now pre-filtered)
        $guideVAnswers = $query->get(['personal_id', 'question', 'answer']);
        if ($guideVAnswers->isEmpty()) {
            // Adjusted log message
            Log::warning('No Guide V answers found for demographic calculation (after filtering for Guide III presence).');

            return [];
        }

        $config = config('referencia_v');
        $distributionsResult = []; // Changed variable name

        // Define the fields we want to process and their mapping info
        $demographicFields = [
            'sexo' => ['label' => 'Sexo', 'config_key' => 'sexo', 'question_key' => 'sexo'],
            'estado_civil' => ['label' => 'Estado Civil', 'config_key' => 'estado_civil', 'question_key' => 'estado_civil'],
            'edad' => ['label' => 'Rango de Edad', 'config_key' => 'edad', 'question_key' => 'edad_d2'],
            'nivel_estudios' => ['label' => 'Nivel de Estudios', 'config_key' => 'nivel_estudios', 'question_key' => 'ultimo_nivel_estudio'],
            'tipo_puesto' => ['label' => 'Tipo de Puesto', 'config_key' => 'datos_laborales.tipo_puesto', 'question_key' => 'tipo_puesto'],
            'tipo_contratacion' => ['label' => 'Tipo de Contratación', 'config_key' => 'datos_laborales.tipo_contratacion', 'question_key' => 'tipo_contratacion'],
            'tipo_personal' => ['label' => 'Tipo de Personal', 'config_key' => 'datos_laborales.tipo_personal', 'question_key' => 'tipo_personal'],
            'tipo_jornada' => ['label' => 'Tipo de Jornada Laboral', 'config_key' => 'datos_laborales.tipo_jornada', 'question_key' => 'tipo_jornada'],
            'rotacion_turnos' => ['label' => 'Rotación de Turnos', 'config_key' => 'datos_laborales.rotacion_turnos', 'question_key' => 'rotacion_turnos'],
            'tiempo_puesto_actual' => ['label' => 'Tiempo en Puesto Actual', 'config_key' => 'datos_laborales.experiencia.tiempo_puesto_actual', 'question_key' => 'tiempo_puesto_actual'],
            'experiencia_laboral' => ['label' => 'Experiencia Laboral Total', 'config_key' => 'datos_laborales.experiencia.tiempo_experiencia_laboral', 'question_key' => 'experiencia_vida_laboral'],
        ];

        $answersByPerson = $guideVAnswers->groupBy('personal_id');

        foreach ($demographicFields as $fieldKey => $fieldInfo) {
            // Use an array to store label, count, and identifier
            $resultsForField = []; // Store {label: '...', identifier: '...', count: 0}
            $configOptions = data_get($config, $fieldInfo['config_key']);
            if (is_null($configOptions)) {
                Log::warning("Config key [{$fieldInfo['config_key']}] not found for demographic field: {$fieldKey}");

                continue;
            }

            foreach ($answersByPerson as $personalId => $personAnswers) {
                $answerRecord = $personAnswers->firstWhere('question', $fieldInfo['question_key']);
                $rawAnswer = $answerRecord ? $answerRecord->answer : null;
                $displayLabel = 'No Respondido';
                $identifier = '__NO_RESPONSE__'; // Special identifier for no response

                if (! is_null($rawAnswer) && $rawAnswer !== '') {
                    $identifier = $rawAnswer; // Default identifier is the raw answer
                    // --- Specific mapping logic --- (Refactored logic remains largely the same)
                    if ($fieldKey === 'edad') {
                        if (is_numeric($rawAnswer)) {
                            $index = intval($rawAnswer) - 1;
                            if (isset($configOptions[$index])) {
                                $displayLabel = $configOptions[$index];
                                $identifier = $rawAnswer;
                            } else {
                                $displayLabel = 'Edad Inválida';
                                $identifier = $rawAnswer; // Store the original invalid answer
                            }
                        } else {
                            $displayLabel = 'Edad Inválida';
                            $identifier = $rawAnswer; // Store the original non-numeric answer
                        }
                    } elseif ($fieldKey === 'nivel_estudios') {
                        $parts = explode('_', $rawAnswer);
                        $levelKeyPart = $this->normalizeForComparison($parts[0]);

                        $matchedKey = collect($configOptions)->search(function ($value, $key) use ($levelKeyPart) {
                            return str_contains($this->normalizeForComparison($key), $levelKeyPart);
                        });

                        if ($matchedKey !== false) {
                            $displayLabel = $matchedKey;
                            $identifier = $rawAnswer;
                        } else {
                            $displayLabel = 'Estudio Desconocido';
                            $identifier = $rawAnswer; // Store the original unmappable answer
                        }
                    } else {
                        // General case: Use normalization for comparison
                        $normalizedRawAnswer = $this->normalizeForComparison($rawAnswer);
                        $foundLabel = null;

                        foreach ($configOptions as $optionLabel) {
                            if ($this->normalizeForComparison($optionLabel) === $normalizedRawAnswer) {
                                $foundLabel = $optionLabel;
                                $identifier = $rawAnswer; // Use raw answer as identifier
                                break;
                            }
                        }

                        if ($foundLabel) {
                            $displayLabel = $foundLabel;
                        } else {
                            $displayLabel = 'Otro';
                            $identifier = $rawAnswer; // Keep raw answer for 'Otro' category
                        }
                    }
                }

                // Find or create the entry for this label/identifier combination
                $foundIndex = -1;
                foreach ($resultsForField as $idx => $item) {
                    if ($item['label'] === $displayLabel && $item['identifier'] === $identifier) {
                        $foundIndex = $idx;
                        break;
                    }
                }

                if ($foundIndex !== -1) {
                    $resultsForField[$foundIndex]['count']++;
                } else {
                    $resultsForField[] = ['label' => $displayLabel, 'identifier' => $identifier, 'count' => 1];
                }
            }

            // Sort final results by label
            usort($resultsForField, fn ($a, $b) => strcmp($a['label'], $b['label']));

            // Add to the main result array
            $distributionsResult[] = [
                'key' => $fieldKey,
                'label' => $fieldInfo['label'],
                'data' => $resultsForField,
            ];
        }

        return $distributionsResult;
    }

    /**
     * Gets the list of distinct people details for a specific demographic answer.
     *
     * @param  string  $fieldKey  The key identifying the demographic field (e.g., 'sexo', 'edad')
     * @param  string  $identifier  The raw answer or special identifier used for querying.
     * @param  array  $personalIds  Optional filter for specific personal IDs.
     * @return Collection List of objects [{ personal_id, guide_iii_evaluation_id, organization_id }]
     */
    public function getPeopleWithDemographicAnswer(string $fieldKey, string $identifier, array $personalIds = []): Collection
    {

        // Mapping from field key back to question key in DB
        $questionKeyMap = [
            'sexo' => 'sexo',
            'estado_civil' => 'estado_civil',
            'edad' => 'edad_d2',
            'nivel_estudios' => 'ultimo_nivel_estudio',
            'tipo_puesto' => 'tipo_puesto',
            'tipo_contratacion' => 'tipo_contratacion',
            'tipo_personal' => 'tipo_personal',
            'tipo_jornada' => 'tipo_jornada',
            'rotacion_turnos' => 'rotacion_turnos',
            'tiempo_puesto_actual' => 'tiempo_puesto_actual',
            'experiencia_laboral' => 'experiencia_vida_laboral',
        ];

        if (! isset($questionKeyMap[$fieldKey])) {
            Log::error("Invalid fieldKey provided for demographic people list: {$fieldKey}");

            return collect();
        }
        $questionColumn = $questionKeyMap[$fieldKey];

        $query = Question::query()
            // Join evaluations twice: once for Guide V, once for Guide III
            ->join('evaluations as eval_guide_v', 'questions.evaluation_id', '=', 'eval_guide_v.id')
            ->join('evaluations as eval_guide_iii', 'questions.personal_id', '=', 'eval_guide_iii.personal_id')
            ->where('questions.reference_guide', 'V') // Source answers are Guide V
            ->where('questions.question', $questionColumn)
            ->whereNotNull('questions.personal_id')
            ->where('eval_guide_iii.reference_guide', 'III'); // Target eval is Guide III

        // Handle the identifier to query the correct answer
        if ($identifier === '__NO_RESPONSE__') {
            $query->where(function ($q) {
                $q->whereNull('questions.answer')->orWhere('questions.answer', '');
            });
        } else {
            // Direct match for all other cases
            $query->where('questions.answer', $identifier);
        }

        // Apply personalId filter if provided (e.g., for organization scope)
        if (! empty($personalIds)) {
            // IMPORTANT: Filter on the question's personal_id, not the eval table one
            $query->whereIn('questions.personal_id', $personalIds);
        }

        $peopleDetails = $query->select(
            'questions.personal_id',
            'eval_guide_iii.id as guide_iii_evaluation_id',
            'eval_guide_iii.organization_id'
        )
            ->distinct()
            ->get();

        return $peopleDetails;
    }
}
