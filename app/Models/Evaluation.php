<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasUuids;
    protected $fillable = [
        'document_id',
        'folio',
        'organization_id',
        'personal_id',
        'data',
        'reference_guide', // Agregar reference_guide a fillable
        'quiz_id', // Para evaluaciones creadas desde Quiz
    ];

    protected $casts = [
        'data' => 'json',
        'personal_id' => 'string',
        'document_id' => 'integer',
        'folio' => 'string',
    ];

    // Asegurar que el personal_id siempre tenga 4 dígitos
    protected function setPersonalIdAttribute($value)
    {
        $this->attributes['personal_id'] = str_pad($value, 4, '0', STR_PAD_LEFT);
    }

    // Asegurar que el personal_id siempre se devuelva como string
    protected function getPersonalIdAttribute($value)
    {
        return (string) $value;
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Guarda las respuestas directamente en la tabla questions para evaluaciones en línea
     */
    public function saveOnlineAnswers(array $answers, string $referenceGuide)
    {
        foreach ($answers as $questionKey => $answer) {
            // Convertir questionKey a string para consistencia
            $questionKey = (string) $questionKey;
            
            // Obtener información de dimensión/dominio/categoría
            $dimensionInfo = $this->getDimensionInfo($questionKey, $referenceGuide);
            
            // Crear o actualizar la pregunta
            $this->questions()->updateOrCreate(
                [
                    'question' => $questionKey,
                ],
                [
                    'personal_id' => $this->personal_id,
                    'answer' => $answer,
                    'domain_id' => $dimensionInfo['domain_id'] ?? null,
                    'dimension_id' => $dimensionInfo['dimension_id'] ?? null,
                    'category_id' => $dimensionInfo['category_id'] ?? null,
                    'value' => $this->getValueForAnswer($answer, $referenceGuide, $questionKey),
                    'reference_guide' => $referenceGuide,
                ]
            );
        }
    }

    /**
     * Obtiene información sobre dimensión/dominio/categoría para una pregunta
     */
    private function getDimensionInfo($questionNumber, $referenceGuide)
    {
        $dimensions = config('question_dimensions', []);
        
        // Verificar que la configuración existe y es un array
        if (!is_array($dimensions) || empty($dimensions)) {
            return [];
        }
        
        foreach ($dimensions as $categoryName => $domains) {
            if (!is_array($domains)) continue;
            
            foreach ($domains as $domainName => $dimensionGroups) {
                if (!is_array($dimensionGroups)) continue;
                
                foreach ($dimensionGroups as $dimensionName => $questions) {
                    if (!is_array($questions)) continue;
                    
                    if (in_array($questionNumber, $questions)) {
                        try {
                            // Buscar las entidades en la base de datos
                            $category = \App\Models\Category::firstWhere('name', $categoryName);
                            if (!$category) continue;

                            $domain = \App\Models\Domain::where('name', $domainName)
                                             ->where('category_id', $category->id)
                                             ->first();
                            if (!$domain) continue;

                            $dimension = \App\Models\Dimension::where('name', $dimensionName)
                                                  ->where('domain_id', $domain->id)
                                                  ->first();

                            return [
                                'category_id' => $category->id,
                                'domain_id' => $domain->id,
                                'dimension_id' => $dimension->id ?? null,
                            ];
                        } catch (\Exception $e) {
                            \Log::error('Error al obtener información de dimensión: ' . $e->getMessage());
                            continue;
                        }
                    }
                }
            }
        }

        return [];
    }

    /**
     * Obtiene el valor numérico para una respuesta
     */
    private function getValueForAnswer($answer, $referenceGuide, $questionKey = null)
    {
        if ($answer === null) {
            return null;
        }

        // Log para debugging
        if (!is_string($answer) && !is_int($answer) && !is_float($answer) && !is_bool($answer)) {
            \Log::warning('getValueForAnswer recibió un tipo de dato inesperado', [
                'answer' => $answer,
                'answer_type' => gettype($answer),
                'questionKey' => $questionKey,
                'referenceGuide' => $referenceGuide
            ]);
        }

        $answerValues = config('answer_values', []);
        
        // Verificar que la configuración tenga la estructura esperada
        if (!is_array($answerValues) || empty($answerValues)) {
            // Si la respuesta ya es numérica, devolverla como está
            if (is_numeric($answer)) {
                return (float)$answer;
            }
            return null;
        }
        
        // Verificar que $answer sea un tipo válido para usar como clave de array
        if (!is_string($answer) && !is_int($answer) && !is_float($answer)) {
            // Manejar respuestas booleanas (del quiz pueden venir true/false)
            if (is_bool($answer)) {
                $answer = $answer ? 'true' : 'false';
            } else {
                // Si $answer no es un tipo válido, intentar convertirlo a string
                $answer = (string) $answer;
            }
        }
        
        // Comprobar si la pregunta está en el grupo 1
        if (isset($answerValues['group1']['questions']) && 
            is_array($answerValues['group1']['questions']) &&
            in_array($questionKey, $answerValues['group1']['questions'])) {
            return $answerValues['group1']['values'][$answer] ?? null;
        }
        
        // Comprobar si la pregunta está en el grupo 2
        if (isset($answerValues['group2']['questions']) && 
            is_array($answerValues['group2']['questions']) &&
            in_array($questionKey, $answerValues['group2']['questions'])) {
            return $answerValues['group2']['values'][$answer] ?? null;
        }

        // Si no se pudo determinar el grupo, comprobar ambos grupos
        // Verificar que $answer sea un tipo válido para usar como clave de array
        if (is_string($answer) || is_int($answer) || is_float($answer)) {
            if (isset($answerValues['group1']['values']) && 
                is_array($answerValues['group1']['values']) &&
                array_key_exists($answer, $answerValues['group1']['values'])) {
                return $answerValues['group1']['values'][$answer];
            } elseif (isset($answerValues['group2']['values']) && 
                      is_array($answerValues['group2']['values']) &&
                      array_key_exists($answer, $answerValues['group2']['values'])) {
                return $answerValues['group2']['values'][$answer];
            }
        }

        // Si la respuesta ya es numérica, devolverla como está
        if (is_numeric($answer)) {
            return (float)$answer;
        }

        return null;
    }
}
