<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Dimension;
use App\Models\Domain;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PeopleListController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display the list of people for a specific category and answer key.
     *
     * @return \Inertia\Response
     */
    public function show(Request $request, string $categoryId, string $answerKey)
    {
        // Optional: Add authorization check if needed for this route

        // Basic validation
        if (! in_array($answerKey, ['A', 'B', 'C', 'D', 'E', 'INVALID'])) {
            abort(404, 'Invalid answer key.');
        }

        $category = Category::find($categoryId);
        if (! $category) {
            abort(404, 'Category not found.');
        }

        $peopleDetails = $this->reportService->getPeopleWithAnswerInCategory($categoryId, $answerKey);

        // Map answer key to label for display
        $answerLabels = [
            'E' => 'Nulo',
            'D' => 'Bajo',
            'C' => 'Medio',
            'B' => 'Alto',
            'A' => 'Muy Alto',
            'INVALID' => 'Inválido',
        ];
        $answerLabel = $answerLabels[$answerKey] ?? $answerKey;

        return Inertia::render('Reports/PeopleList', [
            'categoryName' => $category->name,
            'answerLabel' => $answerLabel,
            'peopleDetails' => $peopleDetails, // Collection of { personal_id, evaluation_id, organization_id }
            // Pass title for the layout
            'title' => "Personal - {$answerLabel} en {$category->name}",
        ]);
    }

    /**
     * Display the list of people for a specific DOMAIN and answer key.
     *
     * @return \Inertia\Response
     */
    public function showDomainList(Request $request, string $domainId, string $answerKey)
    {
        if (! in_array($answerKey, ['A', 'B', 'C', 'D', 'E', 'INVALID'])) {
            abort(404, 'Invalid answer key.');
        }

        $domain = Domain::find($domainId);
        if (! $domain) {
            abort(404, 'Domain not found.');
        }

        // Need a new service method for this!
        // Let's assume we create getPeopleWithAnswerInDomain
        $peopleDetails = $this->reportService->getPeopleWithAnswerInDomain($domainId, $answerKey);

        $answerLabels = [
            'E' => 'Nulo', 'D' => 'Bajo', 'C' => 'Medio',
            'B' => 'Alto', 'A' => 'Muy Alto', 'INVALID' => 'Inválido',
        ];
        $answerLabel = $answerLabels[$answerKey] ?? $answerKey;

        return Inertia::render('Reports/PeopleList', [
            // Pass domain name instead of category name
            'domainName' => $domain->name,
            'answerLabel' => $answerLabel,
            'peopleDetails' => $peopleDetails,
            'title' => "Personal - {$answerLabel} en {$domain->name}", // Update title
        ]);
    }

    /**
     * Display the list of people for a specific DIMENSION and answer key.
     */
    public function showDimensionList(Request $request, string $dimensionId, string $answerKey)
    {
        if (! in_array($answerKey, ['A', 'B', 'C', 'D', 'E', 'INVALID'])) {
            abort(404, 'Invalid answer key.');
        }

        $dimension = Dimension::find($dimensionId);
        if (! $dimension) {
            abort(404, 'Dimension not found.');
        }

        $peopleDetails = $this->reportService->getPeopleWithAnswerInDimension($dimensionId, $answerKey);

        $answerLabels = [
            'E' => 'Nulo', 'D' => 'Bajo', 'C' => 'Medio',
            'B' => 'Alto', 'A' => 'Muy Alto', 'INVALID' => 'Inválido',
        ];
        $answerLabel = $answerLabels[$answerKey] ?? $answerKey;

        return Inertia::render('Reports/PeopleList', [
            'dimensionName' => $dimension->name, // Pass dimension name
            'answerLabel' => $answerLabel,
            'peopleDetails' => $peopleDetails,
            'title' => "Personal - {$answerLabel} en {$dimension->name}", // Update title
        ]);
    }

    /**
     * Display the list of people for a specific demographic field and answer identifier.
     *
     * @return \Inertia\Response
     */
    public function showDemographicList(Request $request, string $fieldKey, string $identifier)
    {
        // Basic validation (can add more specific checks if needed)
        // Refined check: Use strict comparison for empty string and check for null explicitly
        // Allows \"0\" as a valid identifier
        if ($fieldKey === '' || is_null($fieldKey) || $identifier === '' || is_null($identifier)) {
            abort(400, 'Invalid field key or identifier provided.');
        }

        // Optional: Scope by organization if the user is an org user
        $user = $request->user();
        $personalIdsFilter = [];
        if ($user->hasRole('organization') && $user->organization) {
            // Assuming EvaluationService or similar can provide this list
            // Placeholder: $personalIdsFilter = $this->getPersonalIdsForOrg($user->organization->id);
            // For now, we might pass an empty array, letting the service potentially handle it
            // OR we need to implement fetching these IDs.
            Log::warning('Demographic people list filtering by organization is not fully implemented yet.');
        }

        $peopleDetails = $this->reportService->getPeopleWithDemographicAnswer($fieldKey, $identifier, $personalIdsFilter);

        // Generate a user-friendly label for the identifier
        // This requires mapping back from the identifier/fieldKey, which might be complex.
        // For now, let's use the identifier directly or a generic label.
        // TODO: Implement a better way to get the display label for the identifier.
        $displayFieldLabel = ucwords(str_replace('-', ' ', $fieldKey)); // Simple label from key
        $displayValueLabel = ($identifier === '__NO_RESPONSE__') ? 'No Respondido' : $identifier; // Basic label for value

        // Consider using the label from the data structure if passed or re-calculated?

        return Inertia::render('Reports/PeopleList', [
            'demographicField' => $displayFieldLabel, // e.g., 'Estado Civil'
            'demographicValue' => $displayValueLabel, // e.g., 'Casado' or the raw identifier
            'peopleDetails' => $peopleDetails,
            'title' => "Personal - {$displayFieldLabel}: {$displayValueLabel}", // Update title
        ]);
    }
}
