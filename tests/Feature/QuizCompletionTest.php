<?php

namespace Tests\Feature;

use App\Models\Quiz;
use App\Models\Organization;
use App\Models\OnlineAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class QuizCompletionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test organization
        $this->organization = Organization::factory()->create([
            'name' => 'Test Organization'
        ]);
    }

    /** @test */
    public function normal_quiz_completion_returns_correct_data()
    {
        // Create a normal quiz
        $quiz = Quiz::factory()->create([
            'organization_id' => $this->organization->id,
            'is_reduced' => false,
            'is_cisneros' => false,
            'is_active' => true,
            'expires_at' => now()->addHours(2)
        ]);

        // Prepare quiz answers
        $answers = [
            'referencia_iii' => [
                'trauma_0' => '1',
                'trauma_1' => '0'
            ],
            'referencia_i' => [
                'sexo' => 'M',
                'edad' => '25'
            ],
            'referencia_v' => [
                'puesto' => 'Developer',
                'datos_laborales' => [
                    'antiguedad' => '2',
                    'jornada' => '8'
                ]
            ]
        ];

        // Submit the quiz
        $response = $this->post(route('quiz.submit', $quiz), $answers);

        // Assert the response is successful and contains correct data
        $response->assertStatus(200);
        
        // Check that the completion page receives the correct props
        $response->assertInertia(fn ($page) => 
            $page->component('Quiz/Completed')
                ->has('quiz', fn ($quiz) => 
                    $quiz->where('is_reduced', false)
                        ->where('is_cisneros', false)
                        ->has('id')
                        ->has('name')
                        ->has('organization', fn ($org) => 
                            $org->where('name', 'Test Organization')
                                ->has('id')
                        )
                )
                ->has('folio')
                ->has('personalId')
                ->where('message', 'Examen completado exitosamente')
        );

        // Verify answers were stored in the database
        $this->assertDatabaseHas('online_answers', [
            'organization_id' => $this->organization->id,
            'quiz_id' => $quiz->id,
            'question_key' => 'trauma_0',
            'answer_value' => '1',
            'reference_guide' => 'III'
        ]);

        $this->assertDatabaseHas('online_answers', [
            'organization_id' => $this->organization->id,
            'quiz_id' => $quiz->id,
            'question_key' => 'sexo',
            'answer_value' => 'M',
            'reference_guide' => 'I'
        ]);

        $this->assertDatabaseHas('online_answers', [
            'organization_id' => $this->organization->id,
            'quiz_id' => $quiz->id,
            'question_key' => 'datos_laborales_antiguedad',
            'answer_value' => '2',
            'reference_guide' => 'V'
        ]);
    }

    /** @test */
    public function reduced_quiz_completion_returns_correct_data()
    {
        // Create a reduced quiz
        $quiz = Quiz::factory()->create([
            'organization_id' => $this->organization->id,
            'is_reduced' => true,
            'is_cisneros' => false,
            'is_active' => true,
            'expires_at' => now()->addHours(2)
        ]);

        // Prepare quiz answers (reduced quiz only has referencia_iii, referencia_i, and referencia_v)
        $answers = [
            'referencia_iii' => [
                'trauma_0' => '1',
                'trauma_1' => '0'
            ],
            'referencia_i' => [
                'sexo' => 'F',
                'edad' => '30'
            ],
            'referencia_v' => [
                'puesto' => 'Manager'
            ]
        ];

        // Submit the quiz
        $response = $this->post(route('quiz.submit', $quiz), $answers);

        // Assert the response is successful and contains correct data
        $response->assertStatus(200);
        
        // Check that the completion page receives the correct props
        $response->assertInertia(fn ($page) => 
            $page->component('Quiz/Completed')
                ->has('quiz', fn ($quiz) => 
                    $quiz->where('is_reduced', true)
                        ->where('is_cisneros', false)
                        ->has('id')
                        ->has('name')
                        ->has('organization', fn ($org) => 
                            $org->where('name', 'Test Organization')
                                ->has('id')
                        )
                )
                ->has('folio')
                ->has('personalId')
                ->where('message', 'Examen completado exitosamente')
        );

        // Verify answers were stored in the database
        $this->assertDatabaseHas('online_answers', [
            'organization_id' => $this->organization->id,
            'quiz_id' => $quiz->id,
            'question_key' => 'trauma_0',
            'answer_value' => '1',
            'reference_guide' => 'III'
        ]);
    }

    /** @test */
    public function cisneros_quiz_completion_returns_correct_data()
    {
        // Create a Cisneros quiz
        $quiz = Quiz::factory()->create([
            'organization_id' => $this->organization->id,
            'is_reduced' => false,
            'is_cisneros' => true,
            'is_active' => true,
            'expires_at' => now()->addHours(2)
        ]);

        // Prepare quiz answers (Cisneros quiz has escala_cisneros instead of referencia_iii)
        $answers = [
            'escala_cisneros' => [
                'cisneros_1' => '2',
                'cisneros_2' => '3'
            ],
            'referencia_i' => [
                'sexo' => 'M',
                'edad' => '35'
            ],
            'referencia_v' => [
                'puesto' => 'Analyst'
            ]
        ];

        // Submit the quiz
        $response = $this->post(route('quiz.submit', $quiz), $answers);

        // Assert the response is successful and contains correct data
        $response->assertStatus(200);
        
        // Check that the completion page receives the correct props
        $response->assertInertia(fn ($page) => 
            $page->component('Quiz/Completed')
                ->has('quiz', fn ($quiz) => 
                    $quiz->where('is_reduced', false)
                        ->where('is_cisneros', true)
                        ->has('id')
                        ->has('name')
                        ->has('organization', fn ($org) => 
                            $org->where('name', 'Test Organization')
                                ->has('id')
                        )
                )
                ->has('folio')
                ->has('personalId')
                ->where('message', 'Examen completado exitosamente')
        );

        // Verify answers were stored in the database with Cisneros reference guide
        $this->assertDatabaseHas('online_answers', [
            'organization_id' => $this->organization->id,
            'quiz_id' => $quiz->id,
            'question_key' => 'cisneros_1',
            'answer_value' => '2',
            'reference_guide' => 'Cisneros'
        ]);

        $this->assertDatabaseHas('online_answers', [
            'organization_id' => $this->organization->id,
            'quiz_id' => $quiz->id,
            'question_key' => 'sexo',
            'answer_value' => 'M',
            'reference_guide' => 'I'
        ]);
    }

    /** @test */
    public function completion_page_receives_folio_and_personal_id()
    {
        // Create a quiz
        $quiz = Quiz::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => true,
            'expires_at' => now()->addHours(2)
        ]);

        // Prepare minimal quiz answers
        $answers = [
            'referencia_i' => [
                'sexo' => 'M',
                'edad' => '25'
            ],
            'referencia_v' => [
                'puesto' => 'Developer'
            ]
        ];

        // Submit the quiz
        $response = $this->post(route('quiz.submit', $quiz), $answers);

        // Assert the response contains folio and personalId
        $response->assertInertia(fn ($page) => 
            $page->component('Quiz/Completed')
                ->has('folio')
                ->has('personalId')
                ->where('folio', fn ($folio) => 
                    is_string($folio) && strlen($folio) === 4 && ctype_digit($folio)
                )
                ->where('personalId', fn ($personalId) => 
                    is_string($personalId) && strlen($personalId) === 4 && ctype_digit($personalId)
                )
        );
    }

    /** @test */
    public function user_experience_remains_unchanged_across_quiz_types()
    {
        $quizTypes = [
            ['is_reduced' => false, 'is_cisneros' => false], // Normal
            ['is_reduced' => true, 'is_cisneros' => false],  // Reduced
            ['is_reduced' => false, 'is_cisneros' => true],  // Cisneros
        ];

        foreach ($quizTypes as $quizType) {
            $quiz = Quiz::factory()->create([
                'organization_id' => $this->organization->id,
                'is_reduced' => $quizType['is_reduced'],
                'is_cisneros' => $quizType['is_cisneros'],
                'is_active' => true,
                'expires_at' => now()->addHours(2)
            ]);

            // Prepare appropriate answers based on quiz type
            $answers = [
                'referencia_i' => [
                    'sexo' => 'M',
                    'edad' => '25'
                ],
                'referencia_v' => [
                    'puesto' => 'Developer'
                ]
            ];

            if ($quizType['is_cisneros']) {
                $answers['escala_cisneros'] = ['cisneros_1' => '2'];
            } else {
                $answers['referencia_iii'] = ['trauma_0' => '1'];
            }

            // Submit the quiz
            $response = $this->post(route('quiz.submit', $quiz), $answers);

            // Assert consistent response structure
            $response->assertStatus(200)
                ->assertInertia(fn ($page) => 
                    $page->component('Quiz/Completed')
                        ->has('quiz')
                        ->has('folio')
                        ->has('personalId')
                        ->where('message', 'Examen completado exitosamente')
                );
        }
    }
}