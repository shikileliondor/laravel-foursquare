<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponse
{
    public static function ok(mixed $data = [], int $status = 200): JsonResponse
    {
        if ($data instanceof JsonResource) {
            $data = $data->resolve();
        }

        return response()->json(['success' => true, 'data' => $data], $status);
    }

    /**
     * @template TModel of Model
     *
     * @param  LengthAwarePaginator<int, TModel>  $paginator
     * @param  class-string<JsonResource>|null  $resource
     */
    public static function paginated(LengthAwarePaginator $paginator, ?string $resource = null): JsonResponse
    {
        $items = $resource
            ? $resource::collection($paginator->getCollection())->resolve()
            : $paginator->getCollection()->toArray();

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public static function error(string $code, string $message, int $status = 400, array $extra = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => array_merge(['code' => $code, 'message' => $message], $extra),
        ], $status);
    }

    /**
     * Resolve a collection of resources without the "data" wrapper.
     *
     * @return array<int|string, mixed>
     */
    public static function collect(ResourceCollection|JsonResource $resource): array
    {
        return $resource->resolve();
    }
}
