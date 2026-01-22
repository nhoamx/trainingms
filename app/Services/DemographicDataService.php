<?php

namespace App\Services;

use App\Models\DemographicData;
use App\Models\PaperEvaluation;
use Illuminate\Support\Facades\Log;

class DemographicDataService
{
    public function __construct(
        private DemographicDataNormalizationService $normalizationService
    ) {}

    /**
     * Create DemographicData record from PaperEvaluation
     * Deletes existing demographic data first to avoid duplicates
     *
     * @param  PaperEvaluation  $paperEvaluation  The paper evaluation to create demographic data for
     * @param  array  $rawDemographicData  Raw demographic data from OCR or form submission
     * @return DemographicData The created demographic data record
     *
     * @throws \Exception If demographic data creation fails
     */
    public function createFromPaperEvaluation(
        PaperEvaluation $paperEvaluation,
        array $rawDemographicData
    ): DemographicData {
        try {
            Log::info('Demographic Data: '.json_encode($rawDemographicData));

            // Extract and normalize demographic data
            $extractedData = $this->normalizationService->extractDemographicInfo($rawDemographicData);
            Log::info('extractedData: '.json_encode($extractedData));

            // Delete existing demographic data to avoid duplicates
            $paperEvaluation->demographicData()->delete();

            // Create new demographic data record
            $demographicData = DemographicData::create([
                'paper_evaluation_id' => $paperEvaluation->id,
                'gender' => $extractedData['gender'] ?? null,
                'age' => $extractedData['age'] ?? null,
                'marital_status' => $extractedData['marital_status'] ?? null,
                'education_level' => $extractedData['education_level'] ?? null,
                'position' => $extractedData['position'] ?? null,
                'department' => $extractedData['department'] ?? null,
                'position_type' => $extractedData['position_type'] ?? null,
                'contract_type' => $extractedData['contract_type'] ?? null,
                'personnel_type' => $extractedData['personnel_type'] ?? null,
                'work_schedule' => $extractedData['work_schedule'] ?? null,
                'shift_rotation' => $extractedData['shift_rotation'] ?? null,
                'time_in_current_position' => $extractedData['time_in_current_position'] ?? null,
                'work_experience' => $extractedData['work_experience'] ?? null,
                'extra_fields' => $extractedData['extra_fields'] ?? null,
            ]);

            Log::info("Demographic data saved successfully for evaluation: {$paperEvaluation->folio}");

            return $demographicData;
        } catch (\Exception $e) {
            Log::error("Error saving demographic data for evaluation {$paperEvaluation->folio}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Update or create DemographicData for a PaperEvaluation
     * This method provides a more flexible approach that updates existing records
     *
     * @param  PaperEvaluation  $paperEvaluation  The paper evaluation to update demographic data for
     * @param  array  $rawDemographicData  Raw demographic data from OCR or form submission
     * @return DemographicData The created or updated demographic data record
     *
     * @throws \Exception If demographic data update/creation fails
     */
    public function updateOrCreate(
        PaperEvaluation $paperEvaluation,
        array $rawDemographicData
    ): DemographicData {
        try {
            Log::info('Demographic Data (updateOrCreate): '.json_encode($rawDemographicData));

            // Extract and normalize demographic data
            $extractedData = $this->normalizationService->extractDemographicInfo($rawDemographicData);
            Log::info('extractedData (updateOrCreate): '.json_encode($extractedData));

            // Update or create demographic data record
            $demographicData = DemographicData::updateOrCreate(
                ['paper_evaluation_id' => $paperEvaluation->id],
                [
                    'gender' => $extractedData['gender'] ?? null,
                    'age' => $extractedData['age'] ?? null,
                    'marital_status' => $extractedData['marital_status'] ?? null,
                    'education_level' => $extractedData['education_level'] ?? null,
                    'position' => $extractedData['position'] ?? null,
                    'department' => $extractedData['department'] ?? null,
                    'position_type' => $extractedData['position_type'] ?? null,
                    'contract_type' => $extractedData['contract_type'] ?? null,
                    'personnel_type' => $extractedData['personnel_type'] ?? null,
                    'work_schedule' => $extractedData['work_schedule'] ?? null,
                    'shift_rotation' => $extractedData['shift_rotation'] ?? null,
                    'time_in_current_position' => $extractedData['time_in_current_position'] ?? null,
                    'work_experience' => $extractedData['work_experience'] ?? null,
                    'extra_fields' => $extractedData['extra_fields'] ?? null,
                ]
            );

            Log::info("Demographic data updated/created successfully for evaluation: {$paperEvaluation->folio}");

            return $demographicData;
        } catch (\Exception $e) {
            Log::error("Error updating/creating demographic data for evaluation {$paperEvaluation->folio}: ".$e->getMessage());
            throw $e;
        }
    }
}
