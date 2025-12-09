<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\OrganizationDataService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationDashboardController extends Controller
{
    use AuthorizesRequests;

    /**
     * Constructor
     */
    public function __construct(
        protected OrganizationDataService $organizationDataService
    ) {}

    /**
     * Muestra el dashboard de la organización
     */
    public function show(Organization $organization): Response
    {
        $this->authorize('viewOrganizationDashboard', $organization);

        $data = $this->organizationDataService->getDashboardData($organization);


        return Inertia::render('Organizations/Dashboard', [
            'title' => 'Clima Laboral',
            'dashboardData' => $data,
        ]);
    }
}
