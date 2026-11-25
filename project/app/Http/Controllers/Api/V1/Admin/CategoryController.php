<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\CategoryResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Base des catégories d'actualités et d'événements.
 */
abstract class CategoryController extends CrudController
{
    protected string $resource = CategoryResource::class;

    /** @var list<string> */
    protected array $searchable = ['name'];

    protected string $orderBy = 'display_order';

    protected string $orderDirection = 'asc';

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model = null): array
    {
        $required = $model ? 'sometimes' : 'required';
        $table = (new $this->model)->getTable();

        return [
            'name' => [$required, 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120', Rule::unique($table, 'slug')->ignore($model?->getKey())],
            'display_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
