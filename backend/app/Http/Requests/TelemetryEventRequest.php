<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TelemetryEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'events' => [
                'required',
                'array',
                'min:1',
                'max:1000',
            ],

            'events.*.event_id' => [
                'required',
                'uuid',
            ],

            'events.*.tenant_id' => [
                'required',
                'uuid',
            ],

            'events.*.source_id' => [
                'required',
                'uuid',
            ],

            'events.*.event_type' => [
                'required',
                'string',
                'max:100',
            ],

            'events.*.timestamp' => [
                'required',
                'date',
            ],

            'events.*.schema_version' => [
                'required',
                'integer',
                'min:1',
            ],

            'events.*.attributes' => [
                'nullable',
                'array',
            ],

            'events.*.payload' => [
                'nullable',
                'array',
            ],
        ];
    }
}
