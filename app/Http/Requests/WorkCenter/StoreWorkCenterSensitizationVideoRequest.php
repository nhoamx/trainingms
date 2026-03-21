<?php

namespace App\Http\Requests\WorkCenter;

use App\Models\WorkCenter;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkCenterSensitizationVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var WorkCenter $workCenter */
        $workCenter = $this->route('workCenter');

        return $this->user() !== null
            && $this->user()->hasRole(['admin', 'super-admin'])
            && $this->user()->can('viewWorkCenterDashboard', $workCenter);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'audience' => ['required', 'in:gerencia,mandos_medios,trabajadores,general'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime,video/x-msvideo', 'max:51200'],
        ];
    }
}
