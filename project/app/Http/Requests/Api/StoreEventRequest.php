<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\Concerns\ValidatesScope;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    use ValidatesScope;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:events,slug'],
            'description' => ['required', 'string'],
            'cover_media_id' => ['nullable', 'uuid', 'exists:media,id'],
            'organizer_name' => ['nullable', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after_or_equal:start_at'],
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
        ], $this->scopeRules(required: true));
    }
}
