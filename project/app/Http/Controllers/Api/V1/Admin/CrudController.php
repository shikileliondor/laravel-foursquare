<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * Minimal CRUD shared by the simple admin resources.
 * Anything with real business rules gets its own controller.
 *
 * Chaque action est autorisée côté serveur : par la Gate `$gate` si elle est
 * définie, sinon par la Policy du modèle (qui reçoit les données validées).
 */
abstract class CrudController extends ApiController
{
    /** @var class-string<Model> */
    protected string $model;

    /** @var class-string<JsonResource> */
    protected string $resource;

    /** Gate protégeant toute la ressource (ex. `manage-national`). */
    protected ?string $gate = null;

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
     * Valeur enregistrée quand le panel envoie un champ facultatif vide (null)
     * pour une colonne qui n'accepte pas null.
     *
     * @var array<string, mixed>
     */
    protected array $emptyDefaults = [];

    /**
     * @return array<string, mixed>
     */
    abstract protected function rules(Request $request, ?Model $model = null): array;

    public function index(Request $request): JsonResponse
    {
        $this->authorizeFor('viewAny');

        $items = $this->restrict($this->query(), $request->user())
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

        $this->authorizeFor('create', null, $data);

        $data = $this->prepare($this->fillEmptyDefaults($data), $request);

        if ($this->stampsCreator) {
            $data['created_by'] = $request->user()->id;
        }

        $item = $this->model::create($data);

        AuditLog::record('created', $item);

        return ApiResponse::ok($this->resource::make($item->load($this->with)), 201);
    }

    public function show(string $id): JsonResponse
    {
        $item = $this->find($id);

        $this->authorizeFor('view', $item);

        return ApiResponse::ok($this->resource::make($item));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $item = $this->find($id);
        $data = $request->validate($this->rules($request, $item));

        $this->authorizeFor('update', $item, $data);

        $item->update($this->prepare($this->fillEmptyDefaults($data), $request, $item));

        AuditLog::record('updated', $item);

        return ApiResponse::ok($this->resource::make($item->fresh($this->with)));
    }

    public function destroy(string $id): JsonResponse
    {
        $item = $this->find($id);

        $this->authorizeFor('delete', $item);

        $item->delete();

        AuditLog::record('deleted', $item);

        return ApiResponse::ok(['deleted' => true]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function authorizeFor(string $ability, ?Model $item = null, array $data = []): void
    {
        if ($this->gate !== null) {
            Gate::authorize($this->gate);

            return;
        }

        Gate::authorize($ability, $item ? [$item, $data] : [$this->model, $data]);
    }

    /**
     * Limite les listes au périmètre de l'administrateur.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function restrict(Builder $query, User $user): Builder
    {
        return method_exists($this->model, 'scopeManageableBy') ? $query->scopes(['manageableBy' => [$user]]) : $query;
    }

    /**
     * Dernière étape avant l'écriture, une fois la requête autorisée.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepare(array $data, Request $request, ?Model $model = null): array
    {
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function fillEmptyDefaults(array $data): array
    {
        foreach ($this->emptyDefaults as $field => $default) {
            if (array_key_exists($field, $data) && $data[$field] === null) {
                $data[$field] = $default;
            }
        }

        return $data;
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
