<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\BannerResource;
use App\Models\Banner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BannerController extends CrudController
{
    protected string $model = Banner::class;

    protected string $resource = BannerResource::class;

    /** @var list<string> */
    protected array $with = ['media'];

    /** @var list<string> */
    protected array $searchable = ['title'];

    protected string $orderBy = 'display_order';

    protected string $orderDirection = 'asc';

    protected bool $stampsCreator = true;

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model = null): array
    {
        $required = $model ? 'sometimes' : 'required';
        $linkType = $request->input('link_type', $model instanceof Banner ? $model->link_type : 'NONE');

        return [
            'title' => [$required, 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'media_id' => [$required, 'uuid', 'exists:media,id'],
            'button_text' => ['nullable', 'string', 'max:50'],
            'link_type' => ['nullable', Rule::in(Banner::LINK_TYPES)],
            'news_id' => [$linkType === 'NEWS' ? 'required' : 'prohibited', 'uuid', 'exists:news,id'],
            'event_id' => [$linkType === 'EVENT' ? 'required' : 'prohibited', 'uuid', 'exists:events,id'],
            'church_id' => [$linkType === 'CHURCH' ? 'required' : 'prohibited', 'uuid', 'exists:churches,id'],
            'external_url' => [$linkType === 'EXTERNAL' ? 'required' : 'prohibited', 'url', 'max:2048'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
