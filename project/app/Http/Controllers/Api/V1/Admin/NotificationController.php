<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\PushNotificationResource;
use App\Jobs\SendPushNotification;
use App\Models\AuditLog;
use App\Models\PushNotification;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends CrudController
{
    protected string $model = PushNotification::class;

    protected string $resource = PushNotificationResource::class;

    protected bool $stampsCreator = true;

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model = null): array
    {
        $required = $model ? 'sometimes' : 'required';
        $audience = $request->input('audience') ?? ($model instanceof PushNotification ? $model->audience : 'ALL');

        return [
            'title' => [$required, 'string', 'max:255'],
            'body' => [$required, 'string', 'max:1000'],
            'type' => ['sometimes', Rule::in(PushNotification::TYPES)],
            'news_id' => ['nullable', 'uuid', 'exists:news,id'],
            'event_id' => ['nullable', 'uuid', 'exists:events,id'],
            'audience' => ['sometimes', Rule::in(PushNotification::AUDIENCES)],
            'district_id' => $this->target($audience, 'DISTRICT', ['exists:districts,id'], $model !== null),
            'zone_id' => $this->target($audience, 'ZONE', ['exists:zones,id'], $model !== null),
            'church_id' => $this->target($audience, 'CHURCH', ['exists:churches,id'], $model !== null),
            'status' => ['sometimes', Rule::in(PushNotification::STATUSES)],
        ];
    }

    /**
     * Each audience carries its own target and no other. `prohibited` alone
     * lets the panel send an explicit null to clear a stale target.
     *
     * @param  list<string>  $rules
     * @param  bool  $updating  A partial update may legitimately omit the target.
     * @return list<string>
     */
    private function target(string $audience, string $expected, array $rules, bool $updating): array
    {
        if ($audience !== $expected) {
            return ['prohibited'];
        }

        return array_merge($updating ? ['sometimes', 'required', 'uuid'] : ['required', 'uuid'], $rules);
    }

    /**
     * Met la notification en file et declenche l'envoi FCM.
     *
     * Volontairement separe du PATCH : changer un champ ne doit jamais partir
     * vers les telephones par effet de bord.
     */
    public function send(string $id): JsonResponse
    {
        $notification = PushNotification::findOrFail($id);

        if (in_array($notification->status, ['PENDING', 'PROCESSING', 'SENT'], true)) {
            return ApiResponse::error(
                'NOTIFICATION_NOT_SENDABLE',
                'Cette notification est deja en cours ou deja envoyee.',
                409,
            );
        }

        $notification->update(['status' => 'PENDING', 'last_error' => null]);

        AuditLog::record('queued', $notification);

        SendPushNotification::dispatch($notification->id);

        return ApiResponse::ok(PushNotificationResource::make($notification->fresh()));
    }
}
