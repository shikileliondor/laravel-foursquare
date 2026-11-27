<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\ChurchLeaderResource;
use App\Models\Church;
use App\Models\ChurchLeader;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Équipe pastorale : un administrateur ne gère que les églises de son
 * périmètre (même règle que la modification de la fiche église).
 */
class ChurchLeaderController extends CrudController
{
    protected string $model = ChurchLeader::class;

    protected string $resource = ChurchLeaderResource::class;

    protected array $with = ['photo', 'church'];

    protected array $searchable = ['name', 'position'];

    protected string $orderBy = 'display_order';

    protected string $orderDirection = 'asc';

    protected array $emptyDefaults = ['display_order' => 0];

    protected function rules(Request $request, ?Model $model = null): array
    {
        $required = $model ? 'sometimes' : 'required';

        return [
            'church_id' => [$required, 'uuid', 'exists:churches,id'],
            'name' => [$required, 'string', 'max:255'],
            'position' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'photo_media_id' => ['sometimes', 'nullable', 'uuid', 'exists:media,id'],
            'display_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }

    protected function authorizeFor(string $ability, ?Model $item = null, array $data = []): void
    {
        if ($ability === 'viewAny') {
            Gate::authorize('viewAny', Church::class);

            return;
        }

        $churches = array_filter([
            $item instanceof ChurchLeader ? $item->church_id : null,
            $data['church_id'] ?? null,
        ]);

        foreach (array_unique($churches) as $churchId) {
            Gate::authorize($ability === 'view' ? 'view' : 'update', Church::query()->findOrFail($churchId));
        }
    }

    protected function restrict(Builder $query, User $user): Builder
    {
        $churchId = request()->string('church_id')->toString();

        return parent::restrict($query, $user)
            ->when($churchId !== '', fn (Builder $q) => $q->where('church_id', $churchId));
    }
}
