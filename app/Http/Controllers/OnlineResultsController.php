<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\OnlineAnswer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class OnlineResultsController extends Controller
{
    /**
     * Mostrar la lista de participantes de una organización
     */
    public function index($organizationId)
    {
        $organization = Organization::findOrFail($organizationId);
        
        // Obtener participantes únicos con sus datos básicos
        $participants = OnlineAnswer::where('organization_id', $organizationId)
            ->select('personal_id', 'folio', 'quiz_id', 'created_at')
            ->with(['quiz:id,name,is_reduced,is_cisneros'])
            ->groupBy('personal_id', 'folio', 'quiz_id', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($participant) {
                // Obtener datos personales del participante
                $personalData = OnlineAnswer::where('personal_id', $participant->personal_id)
                    ->where('organization_id', $participant->organization_id)
                    ->where('reference_guide', 'V')
                    ->whereIn('question_key', ['sexo', 'edad', 'datos_laborales_ocupacion_puesto'])
                    ->get()
                    ->keyBy('question_key');

                return [
                    'personal_id' => $participant->personal_id,
                    'folio' => $participant->folio,
                    'quiz_name' => $participant->quiz->name ?? 'Quiz eliminado',
                    'quiz_type' => $participant->quiz ? 
                        ($participant->quiz->is_cisneros ? 'Cisneros' : 
                         ($participant->quiz->is_reduced ? 'Reducido' : 'Completo')) : 'N/A',
                    'completed_at' => $participant->created_at->format('d/m/Y H:i'),
                    'sexo' => $personalData->get('sexo')?->answer_value ?? 'N/A',
                    'edad' => $personalData->get('edad')?->answer_value ?? 'N/A',
                    'puesto' => $personalData->get('datos_laborales_ocupacion_puesto')?->answer_value ?? 'N/A',
                ];
            });

        return Inertia::render('OnlineResults/Index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'participants' => $participants,
        ]);
    }

    /**
     * Mostrar los detalles de un participante específico
     */
    public function showParticipant($organizationId, $participantId)
    {
        $organization = Organization::findOrFail($organizationId);
        
        // Obtener todas las respuestas del participante
        $answers = OnlineAnswer::where('organization_id', $organizationId)
            ->where('personal_id', $participantId)
            ->with('quiz:id,name,is_reduced,is_cisneros')
            ->orderBy('reference_guide')
            ->orderBy('question_key')
            ->get();

        if ($answers->isEmpty()) {
            abort(404, 'Participante no encontrado');
        }

        // Obtener las preguntas de acontecimientos traumáticos desde la configuración
        $traumaticQuestions = [];
        $traumaticConfig = config('referencia_iii_reduced.acontecimientos_traumaticos');
        if (isset($traumaticConfig['questions'])) {
            $traumaticQuestions = $traumaticConfig['questions'];
        }

        // Obtener las preguntas de referencia I desde la configuración
        $referenciaIQuestions = config('referencia_i', []);

        // Obtener las preguntas de referencia III desde la configuración
        $referenciaIIIQuestions = config('referencia_iii', []);

        // Obtener las preguntas de la Escala Cisneros desde la configuración
        $escalaCisnerosQuestions = config('escala_cisneros', []);

        // Organizar respuestas por guía de referencia
        $organizedAnswers = [
            'V' => [], // Datos personales
            'III' => [], // Cuestionario principal
            'I' => [], // Preguntas adicionales
            'Cisneros' => [], // Escala Cisneros
        ];

        $participantInfo = [
            'personal_id' => $participantId,
            'folio' => $answers->first()->folio,
            'quiz_name' => $answers->first()->quiz->name ?? 'Quiz eliminado',
            'quiz_type' => $answers->first()->quiz ? 
                ($answers->first()->quiz->is_cisneros ? 'Cisneros' : 
                 ($answers->first()->quiz->is_reduced ? 'Reducido' : 'Completo')) : 'N/A',
            'completed_at' => $answers->first()->created_at->format('d/m/Y H:i'),
        ];

        // Separar imágenes del INE
        $ineImages = [];

        foreach ($answers as $answer) {
            $guide = $answer->reference_guide;
            
            // Manejar imágenes del INE por separado
            if (in_array($answer->question_key, ['ine_frente', 'ine_reverso'])) {
                $ineImages[$answer->question_key] = [
                    'path' => $answer->answer_value,
                    'url' => Storage::url($answer->answer_value),
                    'exists' => Storage::disk('public')->exists($answer->answer_value)
                ];
                continue;
            }

            if (isset($organizedAnswers[$guide])) {
                $organizedAnswers[$guide][] = [
                    'question_key' => $answer->question_key,
                    'answer_value' => $answer->answer_value,
                    'formatted_value' => $this->formatAnswerValue($answer->answer_value, $answer->question_key),
                ];
            }
        }

        return Inertia::render('OnlineResults/ParticipantDetail', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'participant' => $participantInfo,
            'answers' => $organizedAnswers,
            'ine_images' => $ineImages,
            'traumatic_questions' => $traumaticQuestions,
            'referencia_i_questions' => $referenciaIQuestions,
            'referencia_iii_questions' => $referenciaIIIQuestions,
            'escala_cisneros_questions' => $escalaCisnerosQuestions,
        ]);
    }

    /**
     * Formatear valores de respuesta para mejor presentación
     */
    private function formatAnswerValue($value, $questionKey)
    {
        // Si es JSON, intentar decodificar
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Formatear valores booleanos
        if ($value === '1' || $value === 'true') {
            return 'Sí';
        }
        if ($value === '0' || $value === 'false') {
            return 'No';
        }

        // Formatear claves de preguntas para mejor legibilidad
        $formattedKey = str_replace('_', ' ', $questionKey);
        $formattedKey = ucwords($formattedKey);

        return $value;
    }
}