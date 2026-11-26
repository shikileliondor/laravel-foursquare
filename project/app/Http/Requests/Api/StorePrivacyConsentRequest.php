<?php

namespace App\Http\Requests\Api;

use App\Models\PrivacyConsentEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrivacyConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'action_id' => ['required', 'uuid'],
            'subject_id' => ['required', 'uuid'],
            'purpose' => ['required', Rule::in(PrivacyConsentEvent::PURPOSES)],
            'policy_version' => ['required', 'string', 'max:50'],
            'platform' => ['required', Rule::in(PrivacyConsentEvent::PLATFORMS)],
            'church_id' => ['nullable', 'uuid', 'exists:churches,id'],
            'event_id' => ['nullable', 'uuid', 'exists:events,id'],
            'website' => ['prohibited'],
        ];
    }
}
