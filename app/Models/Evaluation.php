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

    /**
     * Guarda las respuestas directamente en la tabla questions para evaluaciones en línea
     */
    public function saveOnlineAnswers(array $answers, string $referenceGuide)
    {
        foreach ($answers as $questionKey => $answer) {
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
        foreach ($dimensions as $categoryName => $domains) {
            foreach ($domains as $domainName => $dimensionGroups) {
                foreach ($dimensionGroups as $dimensionName => $questions) {
                    if (in_array($questionNumber, $questions)) {
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

        $answerValues = config('answer_values', []);
        
        // Comprobar si la pregunta está en el grupo 1
        if (in_array($questionKey, $answerValues['group1']['questions'] ?? [])) {
            return $answerValues['group1']['values'][$answer] ?? null;
        }
        
        // Comprobar si la pregunta está en el grupo 2
        if (in_array($questionKey, $answerValues['group2']['questions'] ?? [])) {
            return $answerValues['group2']['values'][$answer] ?? null;
        }

        // Si no se pudo determinar el grupo, comprobar ambos grupos
        if (isset($answerValues['group1']['values'][$answer])) {
            return $answerValues['group1']['values'][$answer];
        } elseif (isset($answerValues['group2']['values'][$answer])) {
            return $answerValues['group2']['values'][$answer];
        }

        // Si la respuesta ya es numérica, devolverla como está
        if (is_numeric($answer)) {
            return (float)$answer;
        }

        return null;
    }
}
