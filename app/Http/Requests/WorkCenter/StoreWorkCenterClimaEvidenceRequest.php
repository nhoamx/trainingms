<?php

namespace App\Http\Requests\WorkCenter;

use App\Models\WorkCenter;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkCenterClimaEvidenceRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'evidence_file' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:8192'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'evidence_file.required' => 'Debes cargar una evidencia.',
            'evidence_file.mimetypes' => 'La evidencia debe ser JPG, PNG o WEBP.',
            'evidence_file.max' => 'La evidencia no debe superar 8MB.',
        ];
    }
}
