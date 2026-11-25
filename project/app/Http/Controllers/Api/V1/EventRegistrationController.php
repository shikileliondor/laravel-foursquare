<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\RegisterToEventRequest;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class EventRegistrationController extends ApiController
{
    /**
     * Inscription à un événement, sans compte. La réponse ne renvoie aucune
     * donnée personnelle.
     */
    public function store(RegisterToEventRequest $request, string $event): JsonResponse
    {
        $model = Event::query()->published()->identifiedBy($event)->first();

        if (! $model) {
            return ApiResponse::error('EVENT_NOT_FOUND', 'Événement introuvable', 404);
        }

        if (! $model->registrationOpen()) {
            return ApiResponse::error('REGISTRATION_CLOSED', 'Les inscriptions ne sont pas ouvertes pour cet événement.', 409);
        }

        $data = $request->validated();

        if (EventRegistration::query()->where('event_id', $model->id)->where('phone', $data['phone'])->exists()) {
            return ApiResponse::error('ALREADY_REGISTERED', 'Ce numéro est déjà inscrit à cet événement.', 409);
        }

        EventRegistration::create([
            'event_id' => $model->id,
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'church_id' => $data['church_id'] ?? null,
            'church_name' => $data['church_name'] ?? null,
        ]);

        return ApiResponse::ok([
            'registered' => true,
            'event' => ['id' => $model->id, 'slug' => $model->slug, 'title' => $model->title],
        ], 201);
    }
}
