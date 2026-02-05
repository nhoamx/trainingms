<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Quiz;
use App\Models\WorkCenter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GenerateSlugsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Generating slugs for existing records...');

        // Generate slugs for Organizations
        $this->generateOrganizationSlugs();

        // Generate slugs for Work Centers
        $this->generateWorkCenterSlugs();

        // Generate unique identifiers for Quizzes
        $this->generateQuizIdentifiers();

        $this->command->info('Slugs generated successfully!');
    }

    private function generateOrganizationSlugs(): void
    {
        $organizations = Organization::whereNull('slug')->get();

        foreach ($organizations as $organization) {
            $baseSlug = Str::slug($organization->name);
            $slug = $baseSlug;
            $counter = 1;

            // Handle duplicates
            while (Organization::where('slug', $slug)->where('id', '!=', $organization->id)->exists()) {
                $slug = $baseSlug.'-'.$counter;
                $counter++;
            }

            $organization->update(['slug' => $slug]);
            $this->command->line("  Organization: {$organization->name} → {$slug}");
        }

        $this->command->info("Generated slugs for {$organizations->count()} organizations");
    }

    private function generateWorkCenterSlugs(): void
    {
        $workCenters = WorkCenter::whereNull('slug')->get();

        foreach ($workCenters as $workCenter) {
            $baseSlug = Str::slug($workCenter->name);
            $slug = $baseSlug;
            $counter = 1;

            // Handle duplicates within same organization
            while (WorkCenter::where('organization_id', $workCenter->organization_id)
                ->where('slug', $slug)
                ->where('id', '!=', $workCenter->id)
                ->exists()) {
                $slug = $baseSlug.'-'.$counter;
                $counter++;
            }

            $workCenter->update(['slug' => $slug]);
            $this->command->line("  Work Center: {$workCenter->name} → {$slug}");
        }

        $this->command->info("Generated slugs for {$workCenters->count()} work centers");
    }

    private function generateQuizIdentifiers(): void
    {
        $quizzes = Quiz::whereNull('unique_identifier')->get();

        foreach ($quizzes as $quiz) {
            // Generate a unique 8-character identifier
            $identifier = $this->generateUniqueIdentifier();

            // Ensure it's unique
            while (Quiz::where('unique_identifier', $identifier)->exists()) {
                $identifier = $this->generateUniqueIdentifier();
            }

            $quiz->update(['unique_identifier' => $identifier]);
            $this->command->line("  Quiz: {$quiz->name} → {$identifier}");
        }

        $this->command->info("Generated identifiers for {$quizzes->count()} quizzes");
    }

    private function generateUniqueIdentifier(): string
    {
        return substr(md5(uniqid(mt_rand(), true)), 0, 8);
    }
}
