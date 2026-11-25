<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Inscription publique, sans compte. Le champ `website` est un piège à
 * robots : invisible dans l'application, il doit rester vide.
 */
class RegisterToEventRequest extends FormRequest
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

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:3', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'church_id' => ['nullable', 'uuid', 'exists:churches,id'],
            'church_name' => ['nullable', 'string', 'max:255'],
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Numéro de téléphone invalide.',
        ];
    }
}
