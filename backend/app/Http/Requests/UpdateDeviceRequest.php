<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeviceRequest extends FormRequest
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
        $deviceId = $this->route('device')?->id;

        return [
            'asset_id' => [
                'sometimes',
                'integer',
                'exists:assets,id',
            ],

            'device_type' => [
                'sometimes',
                'string',
                'max:100',
            ],

            'serial_number' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('devices', 'serial_number')
                    ->ignore($deviceId),
            ],

            'status' => [
                'sometimes',
                Rule::in([
                    'ONLINE',
                    'OFFLINE',
                    'MAINTENANCE',
                ]),
            ],

            'last_seen_at' => [
                'sometimes',
                'nullable',
                'date',
            ],
        ];
    }
}
