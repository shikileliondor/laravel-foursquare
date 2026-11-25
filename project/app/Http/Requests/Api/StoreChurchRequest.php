<?php

namespace App\Http\Requests\Api;

use App\Models\Church;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChurchRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'zone_id' => ['required', 'uuid', 'exists:zones,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:churches,slug'],
            'code' => ['nullable', 'string', 'max:50'],
            'pastor_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'commune' => ['nullable', 'string', 'max:255'],
            'quartier' => ['nullable', 'string', 'max:255'],
            'secretariat_phone' => ['nullable', 'string', 'max:30'],
            'official_whatsapp' => ['nullable', 'string', 'max:30'],
            'official_email' => ['nullable', 'email', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'main_service_day' => ['nullable', 'string', 'max:30'],
            'main_service_time' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
            'image_media_id' => ['nullable', 'uuid', 'exists:media,id'],
            'status' => ['sometimes', Rule::in(Church::STATUSES)],
        ];
    }
}
