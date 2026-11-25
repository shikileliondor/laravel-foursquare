<?php

namespace App\Http\Requests\Api;

use App\Models\Device;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fcm_token' => ['required', 'string', 'max:255'],
            'platform' => ['required', Rule::in(Device::PLATFORMS)],
            'app_version' => ['nullable', 'string', 'max:30'],
            'notifications_enabled' => ['boolean'],
            'church_id' => ['nullable', 'uuid', 'exists:churches,id'],
        ];
    }
}
