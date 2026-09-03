<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assetId = $this->route('asset')?->id;

        return [
            'plant_id' => [
                'sometimes',
                'integer',
                'exists:plants,id',
            ],

            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'asset_type' => [
                'sometimes',
                'string',
                'max:100',
            ],

            'serial_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('assets', 'serial_number')
                    ->ignore($assetId),
            ],

            'status' => [
                'sometimes',
                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                    'MAINTENANCE',
                ]),
            ],

            'location' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
