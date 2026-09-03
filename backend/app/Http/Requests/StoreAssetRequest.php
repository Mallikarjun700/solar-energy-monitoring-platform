<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plant_id' => [
                'required',
                'integer',
                'exists:plants,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'asset_type' => [
                'required',
                'string',
                'max:100',
            ],

            'serial_number' => [
                'nullable',
                'string',
                'max:100',
                'unique:assets,serial_number',
            ],

            'status' => [
                'required',
                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                    'MAINTENANCE',
                ]),
            ],

            'location' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
