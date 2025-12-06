<?php

namespace App\Console\Commands;

use App\Models\PaperEvaluation;
use Illuminate\Console\Command;

class FixNullLikertAnswers extends Command
{
    protected $signature = 'likert:fix-null-answers {organization : The UUID of the organization}';

    protected $description = 'Fix null answers in Likert evaluations by replacing them with "A"';

    public function handle(): int
    {
        $organizationId = $this->argument('organization');

        $this->info("Searching for Likert evaluations with null answers in organization: {$organizationId}");

        $evaluations = PaperEvaluation::where('organization_id', $organizationId)
            ->where('evaluation_type', 'likert')
            ->whereNotNull('likert_answers')
            ->get();

        if ($evaluations->isEmpty()) {
            $this->warn('No Likert evaluations found for this organization.');

            return self::SUCCESS;
        }

        $this->info("Found {$evaluations->count()} Likert evaluations. Checking for null answers...");

        $fixedCount = 0;
        $fixedQuestions = 0;

        foreach ($evaluations as $evaluation) {
            $likertAnswers = $evaluation->likert_answers;

            if (! isset($likertAnswers['questions'])) {
                continue;
            }

            $hasNull = false;
            $questions = $likertAnswers['questions'];

            foreach ($questions as $questionNum => $answer) {
                if ($answer === null) {
                    $questions[$questionNum] = 'A';
                    $hasNull = true;
                    $fixedQuestions++;
                }
            }

            if ($hasNull) {
                $likertAnswers['questions'] = $questions;
                $evaluation->likert_answers = $likertAnswers;
                $evaluation->save();

                $fixedCount++;
                $this->line("  Fixed folio: {$evaluation->folio}");
            }
        }

        if ($fixedCount === 0) {
            $this->info('No null answers found. All evaluations are complete.');
        } else {
            $this->info("Fixed {$fixedQuestions} null answers in {$fixedCount} evaluations.");
        }

        return self::SUCCESS;
    }
}
