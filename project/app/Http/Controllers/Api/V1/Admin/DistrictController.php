<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\DistrictResource;
use App\Models\District;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DistrictController extends CrudController
{
    protected string $model = District::class;

    protected string $resource = DistrictResource::class;

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
            'name' => [$required, 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('districts', 'slug')->ignore($model?->getKey())],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(District::STATUSES)],
        ];
    }
}
