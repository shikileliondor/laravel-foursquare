<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\StorePrivacyDataRequest;
use App\Models\PrivacyDataRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PrivacyDataRequestController extends ApiController
{
    public function store(StorePrivacyDataRequest $request): JsonResponse
    {
        $data = $request->safe()->except('website');

        $privacyRequest = PrivacyDataRequest::create($data + [
            'reference' => $this->uniqueReference(),
            'status' => 'RECEIVED',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500) ?: null,
        ]);

        return ApiResponse::ok([
            'reference' => $privacyRequest->reference,
            'type' => $privacyRequest->type,
            'status' => $privacyRequest->status,
            'submitted_at' => $privacyRequest->created_at?->toIso8601String(),
            'message' => 'Votre demande a été enregistrée. Conservez cette référence.',
        ], 201);
    }

    private function uniqueReference(): string
    {
        do {
            $reference = 'DCP-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (PrivacyDataRequest::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
