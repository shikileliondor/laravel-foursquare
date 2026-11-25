<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\ZoneResource;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ZoneController extends CrudController
{
    protected string $model = Zone::class;

    protected string $resource = ZoneResource::class;

    /** @var list<string> */
    protected array $with = ['district'];

    /** @var list<string> */
    protected array $withCount = ['churches'];

    /** @var list<string> */
    protected array $searchable = ['name', 'code'];

    protected string $orderBy = 'name';

    protected string $orderDirection = 'asc';

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model = null): array
    {
        $required = $model ? 'sometimes' : 'required';

        return [
            'district_id' => [$required, 'uuid', 'exists:districts,id'],
            'name' => [$required, 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('zones', 'slug')->ignore($model?->getKey())],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(Zone::STATUSES)],
        ];
    }
}
