<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
         return [
            'asset_id' => [
                'required',
                'integer',
                'exists:assets,id',
            ],

            'device_type' => [
                'required',
                'string',
                'max:100',
            ],

            'serial_number' => [
                'required',
                'string',
                'max:100',
                'unique:devices,serial_number',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'ONLINE',
                    'OFFLINE',
                    'MAINTENANCE',
                ]),
            ],

            'last_seen_at' => [
                'nullable',
                'date',
            ],
        ];
    }
}
