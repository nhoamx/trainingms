<?php

namespace App\Http\Requests\WorkCenter;

use App\Models\WorkCenter;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkCenterConclusionsFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var WorkCenter $workCenter */
        $workCenter = $this->route('workCenter');

        return $this->user() !== null
            && $this->user()->hasRole(['admin', 'super-admin'])
            && $this->user()->can('viewWorkCenterDashboard', $workCenter);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'slot' => ['required', 'integer', 'in:1,2,3'],
            'title' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'in:teal,blue,red,amber,slate'],
            'conclusions_file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slot.required' => 'Debes indicar el slot del archivo.',
            'slot.in' => 'El slot debe ser 1, 2 o 3.',
            'title.required' => 'El título es obligatorio.',
            'conclusions_file.required' => 'Debes subir un archivo.',
            'conclusions_file.mimes' => 'Solo se permiten archivos PDF, DOC o DOCX.',
            'conclusions_file.max' => 'El archivo no puede superar 20 MB.',
        ];
    }
}
