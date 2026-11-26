<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\NationalLeaderResource;
use App\Models\NationalLeader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class NationalLeaderController extends CrudController
{
    protected string $model = NationalLeader::class;

    protected string $resource = NationalLeaderResource::class;

    protected ?string $gate = 'manage-national';

    protected array $with = ['photo'];

    protected array $searchable = ['name', 'position'];

    protected string $orderBy = 'display_order';

    protected string $orderDirection = 'asc';

    protected array $emptyDefaults = ['display_order' => 0];

    protected function rules(Request $request, ?Model $model = null): array
    {
        $required = $model ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:255'],
            'position' => [$required, 'string', 'max:255'],
            'photo_media_id' => ['sometimes', 'nullable', 'uuid', 'exists:media,id'],
            'display_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
