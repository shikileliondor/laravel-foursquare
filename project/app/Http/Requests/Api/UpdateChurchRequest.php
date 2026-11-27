<?php

namespace App\Http\Requests\Api;

use App\Models\Church;
use App\Rules\DonationUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChurchRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'district_id' => ['sometimes', 'uuid', 'exists:districts,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('churches', 'slug')->ignore($this->route('church'))],
            'code' => ['nullable', 'string', 'max:50'],
            'pastor_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'commune' => ['nullable', 'string', 'max:255'],
            'quartier' => ['nullable', 'string', 'max:255'],
            'secretariat_phone' => ['nullable', 'string', 'max:30'],
            'official_whatsapp' => ['nullable', 'string', 'max:30'],
            'official_email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'service_times' => ['nullable', 'string', 'max:1000'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'donation_url' => ['nullable', 'string', 'max:255', new DonationUrl],
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
