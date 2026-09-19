<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\Concerns\ValidatesScope;
use App\Models\News;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNewsRequest extends FormRequest
{
    use ValidatesScope;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('news', 'slug')->ignore($this->route('news'))],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['sometimes', 'string'],
            'cover_media_id' => ['nullable', 'uuid', 'exists:media,id'],
            'priority' => ['sometimes', Rule::in(News::PRIORITIES)],
            'is_featured' => ['boolean'],
            'status' => ['sometimes', Rule::in(News::STATUSES)],
            'published_at' => ['nullable', 'date'],
        ], $this->scopeRules(required: false));
    }
}
