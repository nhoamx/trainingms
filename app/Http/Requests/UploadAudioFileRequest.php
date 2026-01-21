<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadAudioFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['admin', 'super-admin']) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question_type' => ['required', 'string', 'in:general,conditional,traumatic,cisneros,referencia_i'],
            'question_index' => ['required', 'integer', 'min:0'],
            'audio_file' => [
                'required',
                'file',
                function ($attribute, $value, $fail) {
                    // Validate file extension - most reliable for audio files
                    $validExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac'];
                    $extension = strtolower($value->getClientOriginalExtension());

                    if (! in_array($extension, $validExtensions)) {
                        $fail('El archivo debe ser un archivo de audio válido (MP3, WAV, OGG, M4A)');
                    }

                    // Validate MIME type as secondary check
                    $validMimes = [
                        'audio/mpeg',     // MP3
                        'audio/mp3',      // MP3 variant
                        'audio/wav',      // WAV
                        'audio/x-wav',    // WAV variant
                        'audio/wave',     // WAV variant
                        'audio/ogg',      // OGG
                        'audio/x-ogg',    // OGG variant
                        'audio/m4a',      // M4A
                        'audio/x-m4a',    // M4A variant
                        'audio/mp4',      // M4A / MP4
                        'audio/aac',      // AAC
                        'audio/x-aac',    // AAC variant
                    ];

                    if (! in_array($value->getMimeType(), $validMimes)) {
                        // If MIME type doesn't match but extension is valid, allow it
                        // (some systems report MIME types incorrectly)
                        if (! in_array($extension, $validExtensions)) {
                            $fail('El tipo de archivo no es válido. Se detectó: '.$value->getMimeType());
                        }
                    }
                },
                'max:'.config('audio.max_file_size'),
            ],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'question_type.required' => 'Debes seleccionar un tipo de pregunta.',
            'question_type.in' => 'El tipo de pregunta seleccionado no es válido.',
            'question_index.required' => 'Debes especificar el índice de la pregunta.',
            'question_index.integer' => 'El índice de la pregunta debe ser un número entero.',
            'question_index.min' => 'El índice de la pregunta debe ser mayor o igual a 0.',
            'audio_file.required' => 'Debes seleccionar un archivo de audio.',
            'audio_file.file' => 'El archivo seleccionado no es válido.',
            'audio_file.mimes' => 'Solo se permiten archivos de audio en formato MP3, WAV, OGG o M4A.',
            'audio_file.max' => 'El archivo de audio no debe superar los '.(config('audio.max_file_size') / 1024).' MB.',
        ];
    }

    /**
     * Get custom attribute names for validation.
     */
    public function attributes(): array
    {
        return [
            'question_type' => 'tipo de pregunta',
            'question_index' => 'índice de pregunta',
            'audio_file' => 'archivo de audio',
        ];
    }
}
