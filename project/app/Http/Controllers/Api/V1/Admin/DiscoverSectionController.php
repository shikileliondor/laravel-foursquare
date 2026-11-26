<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\DiscoverSectionResource;
use App\Models\AuditLog;
use App\Models\DiscoverSection;
use App\Services\HtmlSanitizer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DiscoverSectionController extends ApiController
{
    private const WITH = ['image', 'illustration'];

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('manage-national');

        $sections = DiscoverSection::query()
            ->with(self::WITH)
            ->orderBy('display_order')
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($sections, DiscoverSectionResource::class);
    }

    public function show(string $id): JsonResponse
    {
        Gate::authorize('manage-national');

        return ApiResponse::ok(DiscoverSectionResource::make(DiscoverSection::with(self::WITH)->findOrFail($id)));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        Gate::authorize('manage-national');
        $section = DiscoverSection::findOrFail($id);
        $data = $request->validate([
            'content' => ['sometimes', 'nullable', 'string'],
            'image_media_id' => ['sometimes', 'nullable', 'uuid', 'exists:media,id'],
            'illustration_media_id' => ['sometimes', 'nullable', 'uuid', 'exists:media,id'],
            'status' => ['sometimes', Rule::in(DiscoverSection::STATUSES)],
        ]);
        if (array_key_exists('content', $data) && $data['content'] !== null) {
            $data['content'] = HtmlSanitizer::clean($data['content']);
        }
        $section->update($data);
        AuditLog::record('updated', $section);

        return ApiResponse::ok(DiscoverSectionResource::make($section->fresh(self::WITH)));
    }
}
