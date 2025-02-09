<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Domain;
use App\Models\Dimension;
use Illuminate\Database\Seeder;

class QuestionDimensionsSeeder extends Seeder
{
    public function run(): void
    {
        $dimensions = config('question_dimensions');

        foreach ($dimensions as $categoryName => $domains) {
            $category = Category::create(['name' => $categoryName]);

            foreach ($domains as $domainName => $dimensionGroups) {
                $domain = Domain::create([
                    'name' => $domainName,
                    'category_id' => $category->id
                ]);

                foreach ($dimensionGroups as $dimensionName => $questions) {
                    Dimension::create([
                        'name' => $dimensionName,
                        'domain_id' => $domain->id
                    ]);
                }
            }
        }
    }
}