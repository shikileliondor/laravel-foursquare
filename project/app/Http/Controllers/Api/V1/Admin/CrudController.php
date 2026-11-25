<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\AuditLog;
use App\Support\ApiResponse;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Minimal CRUD shared by the simple admin resources.
 * Anything with real business rules gets its own controller.
 */
abstract class CrudController extends ApiController
{
    /** @var class-string<Model> */
    protected string $model;

    /** @var class-string<JsonResource> */
    protected string $resource;

    /** @var list<string> */
    protected array $with = [];

    /** @var list<string> */
    protected array $withCount = [];

    /** @var list<string> */
    protected array $searchable = [];

    protected string $orderBy = 'created_at';

    /** @var 'asc'|'desc' */
    protected string $orderDirection = 'desc';

    /** Fields stamped with the authenticated admin id on create. */
    protected bool $stampsCreator = false;

    /**
     * @return array<string, mixed>
     */
    abstract protected function rules(Request $request, ?Model $model = null): array;

    public function index(Request $request): JsonResponse
    {
        $items = $this->query()
            ->when($request->filled('search') && $this->searchable, function (Builder $q) use ($request) {
                $like = '%'.$request->string('search')->toString().'%';
                $op = Sql::like();
                $q->where(function (Builder $inner) use ($like, $op) {
                    foreach ($this->searchable as $field) {
                        $inner->orWhere($field, $op, $like);
                    }
                });
            })
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')->toString()))
            ->orderBy($this->orderBy, $this->orderDirection)
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($items, $this->resource);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules($request));

        if ($this->stampsCreator) {
            $data['created_by'] = $request->user()->id;
        }

        $item = $this->model::create($data);

        AuditLog::record('created', $item);

        return ApiResponse::ok($this->resource::make($item->load($this->with)), 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::ok($this->resource::make($this->find($id)));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $item = $this->find($id);
        $item->update($request->validate($this->rules($request, $item)));

        AuditLog::record('updated', $item);

        return ApiResponse::ok($this->resource::make($item->fresh($this->with)));
    }

    public function destroy(string $id): JsonResponse
    {
        $item = $this->find($id);
        $item->delete();

        AuditLog::record('deleted', $item);

        return ApiResponse::ok(['deleted' => true]);
    }

    /**
     * @return Builder<Model>
     */
    protected function query(): Builder
    {
        return $this->model::query()->with($this->with)->withCount($this->withCount);
    }

    protected function find(string $id): Model
    {
        return $this->query()->findOrFail($id);
    }
}
