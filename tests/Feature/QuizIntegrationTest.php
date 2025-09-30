<?php

namespace Tests\Feature;

use App\Models\OnlineAnswer;
use App\Models\Organization;
use App\Models\Quiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_quiz_flow_for_all_types()
    {
        // Create organization
        $organization = Organization::factory()->create();

        // Test data for each quiz type
        $quizTypes = [
            [
                'name' => 'Normal Quiz',
                'is_reduced' => false,
                'is_cisneros' => false,
                'answers' => [
                    'referencia_iii' => ['trauma_0' => '1'],
                    'referencia_i' => ['sexo' => 'M', 'edad' => '25'],
                    'referencia_v' => ['puesto' => 'Developer'],
                ],
            ],
            [
                'name' => 'Reduced Quiz',
                'is_reduced' => true,
                'is_cisneros' => false,
                'answers' => [
                    'referencia_iii' => ['trauma_0' => '1'],
                    'referencia_i' => ['sexo' => 'F', 'edad' => '30'],
                    'referencia_v' => ['puesto' => 'Manager'],
                ],
            ],
            [
                'name' => 'Cisneros Quiz',
                'is_reduced' => false,
                'is_cisneros' => true,
                'answers' => [
                    'escala_cisneros' => ['cisneros_1' => '2'],
                    'referencia_i' => ['sexo' => 'M', 'edad' => '35'],
                    'referencia_v' => ['puesto' => 'Analyst'],
                ],
            ],
        ];

        foreach ($quizTypes as $quizData) {
            // Create quiz
            $quiz = Quiz::factory()->create([
                'name' => $quizData['name'],
                'organization_id' => $organization->id,
                'is_reduced' => $quizData['is_reduced'],
                'is_cisneros' => $quizData['is_cisneros'],
                'is_active' => true,
                'expires_at' => now()->addHours(2),
            ]);

            // Submit quiz
            $response = $this->post(route('quiz.submit', $quiz), $quizData['answers']);

            // Assert successful completion
            $response->assertStatus(200)
                ->assertInertia(fn ($page) => $page->component('Quiz/Completed')
                    ->has('quiz', fn ($quizProps) => $quizProps->where('name', $quizData['name'])
                        ->where('is_reduced', $quizData['is_reduced'])
                        ->where('is_cisneros', $quizData['is_cisneros'])
                        ->has('id')
                        ->has('organization')
                    )
                    ->has('folio')
                    ->has('personalId')
                );

            // Verify data was stored correctly
            $this->assertDatabaseHas('online_answers', [
                'organization_id' => $organization->id,
                'quiz_id' => $quiz->id,
            ]);

            // Verify correct reference guide was used
            if ($quizData['is_cisneros']) {
                $this->assertDatabaseHas('online_answers', [
                    'quiz_id' => $quiz->id,
                    'reference_guide' => 'Cisneros',
                ]);
            } else {
                $this->assertDatabaseHas('online_answers', [
                    'quiz_id' => $quiz->id,
                    'reference_guide' => 'III',
                ]);
            }

            $this->assertDatabaseHas('online_answers', [
                'quiz_id' => $quiz->id,
                'reference_guide' => 'I',
            ]);

            $this->assertDatabaseHas('online_answers', [
                'quiz_id' => $quiz->id,
                'reference_guide' => 'V',
            ]);
        }
    }

    public function test_folio_and_personal_id_generation_is_consistent()
    {
        $organization = Organization::factory()->create();
        $quiz = Quiz::factory()->create([
            'organization_id' => $organization->id,
            'is_active' => true,
            'expires_at' => now()->addHours(2),
        ]);

        $answers = [
            'referencia_i' => ['sexo' => 'M', 'edad' => '25'],
            'referencia_v' => ['puesto' => 'Developer'],
        ];

        // Submit first quiz
        $response1 = $this->post(route('quiz.submit', $quiz), $answers);
        $response1->assertStatus(200);

        // Submit second quiz
        $response2 = $this->post(route('quiz.submit', $quiz), $answers);
        $response2->assertStatus(200);

        // Get all answers and group by folio to verify incremental generation
        $allAnswers = OnlineAnswer::where('quiz_id', $quiz->id)->orderBy('created_at')->get();
        $folios = $allAnswers->pluck('folio')->unique()->sort()->values();
        $personalIds = $allAnswers->pluck('personal_id')->unique()->sort()->values();

        // Verify we have two different folios and personal IDs
        $this->assertCount(2, $folios);
        $this->assertCount(2, $personalIds);

        // Verify they are incremental
        $this->assertEquals('0001', $folios[0]);
        $this->assertEquals('0002', $folios[1]);
        $this->assertEquals('0001', $personalIds[0]);
        $this->assertEquals('0002', $personalIds[1]);
    }
}
