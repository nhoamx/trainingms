<?php

namespace App\Http\Requests\WorkCenter;

use App\Models\WorkCenter;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkCenterClimaReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var WorkCenter $workCenter */
        $workCenter = $this->route('workCenter');

        return $this->user() !== null
            && $this->user()->hasRole(['admin', 'super-admin'])
            && $this->user()->can('viewWorkCenterDashboard', $workCenter);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'language' => ['required', 'in:es,en'],
            'report_file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'is_published' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título del informe es obligatorio.',
            'report_file.required' => 'Debes cargar un archivo de informe.',
            'report_file.mimes' => 'El informe debe ser PDF, DOC o DOCX.',
            'report_file.max' => 'El informe no debe superar 10MB.',
        ];
    }
}
