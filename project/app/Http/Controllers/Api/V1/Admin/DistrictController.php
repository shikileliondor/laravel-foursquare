<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\DistrictResource;
use App\Models\District;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DistrictController extends CrudController
{
    protected string $model = District::class;

    protected string $resource = DistrictResource::class;

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
            'name' => [$required, 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('districts', 'slug')->ignore($model?->getKey())],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(District::STATUSES)],
        ];
    }

    /**
     * Supprimer un district ne doit jamais emporter ses églises : on le
     * désactive, ou on déplace d'abord ses églises.
     */
    public function destroy(string $id): JsonResponse
    {
        /** @var District $district */
        $district = $this->find($id);

        $this->authorizeFor('delete', $district);

        if ($district->churches()->exists()) {
            return ApiResponse::error(
                'DISTRICT_HAS_CHURCHES',
                'Ce district contient des églises : désactivez-le ou rattachez ses églises à un autre district avant de le supprimer.',
                409,
            );
        }

        return parent::destroy($id);
    }
}
