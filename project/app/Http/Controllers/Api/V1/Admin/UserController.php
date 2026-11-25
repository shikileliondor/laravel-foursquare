<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\AdminRole;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Comptes administrateurs. Les droits (qui crée quel rôle, qui modifie qui)
 * sont dans UserPolicy.
 */
class UserController extends CrudController
{
    protected string $model = User::class;

    protected string $resource = UserResource::class;

    /** @var list<string> */
    protected array $with = ['district', 'church'];

    /** @var list<string> */
    protected array $searchable = ['name', 'email'];

    protected string $orderBy = 'name';

    protected string $orderDirection = 'asc';

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model = null): array
    {
        $required = $model ? 'sometimes' : 'required';
        $role = $request->input('role', $model?->getAttribute('role'));

        return [
            'name' => [$required, 'string', 'max:255'],
            'email' => [$required, 'email', 'max:255', Rule::unique('users', 'email')->ignore($model?->getKey())],
            'password' => [$model ? 'nullable' : 'required', 'string', Password::min(12)->mixedCase()->numbers()],
            'role' => [$required, Rule::in(AdminRole::values())],
            'status' => ['sometimes', Rule::in(['ACTIVE', 'SUSPENDED'])],
            'district_id' => $role === AdminRole::DistrictAdmin->value
                ? [$model ? 'sometimes' : 'required', 'required', 'uuid', 'exists:districts,id']
                : ['prohibited'],
            'church_id' => $role === AdminRole::ChurchAdmin->value
                ? [$model ? 'sometimes' : 'required', 'required', 'uuid', 'exists:churches,id']
                : ['prohibited'],
        ];
    }

    /**
     * Le périmètre suit le rôle ; un mot de passe vide ne remplace rien.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepare(array $data, Request $request, ?Model $model = null): array
    {
        $role = $data['role'] ?? $model?->getAttribute('role');

        if ($role !== AdminRole::DistrictAdmin->value) {
            $data['district_id'] = null;
        }

        if ($role !== AdminRole::ChurchAdmin->value) {
            $data['church_id'] = null;
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function restrict(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->whereIn('role', [AdminRole::DistrictAdmin->value, AdminRole::ChurchAdmin->value])
            ->orWhereKey($user->getKey()));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $response = parent::update($request, $id);

        // Un compte suspendu perd immédiatement ses sessions API.
        $user = User::findOrFail($id);

        if ($user->status !== 'ACTIVE') {
            $user->tokens()->delete();
        }

        return $response;
    }

    public function destroy(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $this->authorizeFor('delete', $user);

        $user->tokens()->delete();

        return parent::destroy($id);
    }
}
