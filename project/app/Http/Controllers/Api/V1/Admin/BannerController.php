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
        $linkType = $request->input('link_type') ?? ($model instanceof Banner ? $model->link_type : 'NONE');

        return [
            'title' => [$required, 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'media_id' => [$required, 'uuid', 'exists:media,id'],
            'button_text' => ['nullable', 'string', 'max:50'],
            'link_type' => ['sometimes', Rule::in(Banner::LINK_TYPES)],
            'news_id' => $this->target($linkType, 'NEWS', ['uuid', 'exists:news,id'], $model !== null),
            'event_id' => $this->target($linkType, 'EVENT', ['uuid', 'exists:events,id'], $model !== null),
            'church_id' => $this->target($linkType, 'CHURCH', ['uuid', 'exists:churches,id'], $model !== null),
            'external_url' => $this->target($linkType, 'EXTERNAL', ['url', 'max:2048'], $model !== null),
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    /**
     * The link target only exists for its own link_type. Anywhere else the
     * field must stay empty, and `prohibited` alone lets the panel send an
     * explicit null to clear a target that is no longer relevant.
     *
     * @param  list<string>  $rules
     * @param  bool  $updating  A partial update may legitimately omit the target.
     * @return list<string>
     */
    private function target(string $linkType, string $expected, array $rules, bool $updating): array
    {
        if ($linkType !== $expected) {
            return ['prohibited'];
        }

        return array_merge($updating ? ['sometimes', 'required'] : ['required'], $rules);
    }
}
