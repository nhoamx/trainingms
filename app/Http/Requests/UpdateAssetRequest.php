<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_type' => ['required', 'string', 'max:50'],
            'serial_number' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'string', 'max:50'],
            'fire_class' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'asset_type.required' => 'El tipo de activo es requerido.',
            'serial_number.required' => 'El número de serie es requerido.',
            'location.required' => 'La ubicación es requerida.',
        ];
    }
}
