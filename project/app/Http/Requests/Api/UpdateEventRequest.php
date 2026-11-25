<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\Concerns\ValidatesScope;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    use ValidatesScope;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('events', 'slug')->ignore($this->route('event'))],
            'description' => ['sometimes', 'string'],
            'cover_media_id' => ['nullable', 'uuid', 'exists:media,id'],
            'organizer_name' => ['nullable', 'string', 'max:255'],
            'start_at' => ['sometimes', 'date'],
            'end_at' => ['sometimes', 'date', 'after_or_equal:start_at'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'commune' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'official_whatsapp' => ['nullable', 'string', 'max:30'],
            'priority' => ['sometimes', Rule::in(Event::PRIORITIES)],
            'is_featured' => ['boolean'],
            'status' => ['sometimes', Rule::in(Event::STATUSES)],
        ], $this->scopeRules(required: false));
    }
}
