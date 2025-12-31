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
        $asset = $this->route('asset');
        $organizationId = $this->route('organization')->id;

        return [
            'asset_type' => ['nullable', 'string', 'max:50'],
            'asset_category' => ['required', 'string', 'max:50'],
            'consecutive_number' => [
                'required',
                'string',
                'max:50',
                'unique:assets,consecutive_number,'.$asset->id.',id,organization_id,'.$organizationId,
            ],
            'serial_number' => ['required', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'string', 'max:50'],
            'fire_class' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'asset_category.required' => 'La categoría del activo es requerida.',
            'consecutive_number.required' => 'El número consecutivo es requerido.',
            'consecutive_number.unique' => 'El número consecutivo ya existe en esta organización.',
            'serial_number.required' => 'El número de serie es requerido.',
        ];
    }
}
