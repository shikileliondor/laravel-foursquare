<?php

namespace App\Http\Requests\Api;

use App\Models\PrivacyDataRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrivacyDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => preg_replace('/[\s.\-()]/', '', (string) $this->input('phone'))]);
        }

        if ($this->filled('full_name')) {
            $this->merge(['full_name' => trim(preg_replace('/\s+/', ' ', (string) $this->input('full_name')))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(PrivacyDataRequest::TYPES)],
            'full_name' => ['required', 'string', 'min:3', 'max:120'],
            'phone' => ['nullable', 'required_without:email', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:255'],
            'event_id' => ['nullable', 'uuid', 'exists:events,id'],
            'details' => ['nullable', 'string', 'max:2000'],
            'privacy_notice_version' => ['required', 'string', 'max:50'],
            'website' => ['prohibited'],
        ];
    }
}
