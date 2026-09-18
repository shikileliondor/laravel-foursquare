<?php

namespace App\Http\Requests\Api\Concerns;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

trait ValidatesScope
{
    /**
     * Shared scope_type + target id rules.
     *
     * @return array<string, mixed>
     */
    protected function scopeRules(bool $required): array
    {
        $presence = $required ? 'required' : 'sometimes';

        return [
            'scope_type' => [$presence, Rule::in(['NATIONAL', 'DISTRICT', 'ZONE', 'CHURCH'])],
            'district_id' => ['nullable', 'uuid', 'exists:districts,id'],
            'zone_id' => ['nullable', 'uuid', 'exists:zones,id'],
            'church_id' => ['nullable', 'uuid', 'exists:churches,id'],
        ];
    }

    /**
     * NATIONAL takes no target, the other scopes take exactly their own.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('scope_type')) {
                return;
            }

            $expected = match ($this->input('scope_type')) {
                'DISTRICT' => 'district_id',
                'ZONE' => 'zone_id',
                'CHURCH' => 'church_id',
                default => null,
            };

            foreach (['district_id', 'zone_id', 'church_id'] as $field) {
                if ($field === $expected && ! $this->filled($field)) {
                    $validator->errors()->add($field, "Le champ {$field} est requis pour ce scope.");
                }

                if ($field !== $expected && $this->filled($field)) {
                    $validator->errors()->add($field, "Le champ {$field} n'est pas autorisé pour ce scope.");
                }
            }
        });
    }
}
