<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\Concerns\ValidatesScope;
use App\Models\News;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNewsRequest extends FormRequest
{
    use ValidatesScope;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:news,slug'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'cover_media_id' => ['nullable', 'uuid', 'exists:media,id'],
            'priority' => ['sometimes', Rule::in(News::PRIORITIES)],
            'is_featured' => ['boolean'],
            'status' => ['sometimes', Rule::in(News::STATUSES)],
            'published_at' => ['nullable', 'date'],
        ], $this->scopeRules(required: true));
    }
}
