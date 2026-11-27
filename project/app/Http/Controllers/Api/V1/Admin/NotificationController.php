<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\PushNotificationResource;
use App\Jobs\SendPushNotification;
use App\Models\AuditLog;
use App\Models\PushNotification;
use App\Support\ApiResponse;
use App\Support\Perimeter;
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
            'daily_verse_id' => ['nullable', 'uuid', 'exists:daily_verses,id'],
            'daily_meditation_id' => ['nullable', 'uuid', 'exists:daily_meditations,id'],
            'audience' => ['sometimes', Rule::in(PushNotification::AUDIENCES)],
            'district_id' => $this->target($audience, 'DISTRICT', ['exists:districts,id'], $model !== null),
            'church_id' => $this->target($audience, 'CHURCH', ['exists:churches,id'], $model !== null),
            'status' => ['sometimes', Rule::in(PushNotification::STATUSES)],
        ];
    }

    /**
     * Changer d'audience efface l'ancienne cible.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepare(array $data, Request $request, ?Model $model = null): array
    {
        $audience = $data['audience'] ?? $model?->getAttribute('audience') ?? 'ALL';

        return Perimeter::onlyTargetOf($audience, $data, $model);
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
     * Declenche l'envoi FCM : tout de suite par defaut, ou via la file si
     * FCM_QUEUE est active (voir config/fcm.php).
     *
     * Volontairement separe du PATCH : changer un champ ne doit jamais partir
     * vers les telephones par effet de bord.
     */
    public function send(string $id): JsonResponse
    {
        $notification = PushNotification::findOrFail($id);

        $this->authorizeFor('update', $notification);

        if (in_array($notification->status, ['PENDING', 'PROCESSING', 'SENT'], true)) {
            return ApiResponse::error(
                'NOTIFICATION_NOT_SENDABLE',
                'Cette notification est deja en cours ou deja envoyee.',
                409,
            );
        }

        $notification->update(['status' => 'PENDING', 'last_error' => null]);

        AuditLog::record('queued', $notification);

        if (config()->boolean('fcm.queue')) {
            SendPushNotification::dispatch($notification->id);
        } else {
            // Sans worker : l'envoi se fait pendant la requete. Il doit aller
            // au bout meme si la page se ferme ou si PHP limite la duree.
            if (function_exists('ignore_user_abort')) {
                ignore_user_abort(true);
            }
            if (function_exists('set_time_limit')) {
                @set_time_limit(300);
            }

            SendPushNotification::dispatchSync($notification->id);
        }

        return ApiResponse::ok(PushNotificationResource::make($notification->fresh()));
    }
}
