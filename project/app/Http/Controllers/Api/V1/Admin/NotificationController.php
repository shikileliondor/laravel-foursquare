<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\PushNotificationResource;
use App\Models\PushNotification;
use Illuminate\Database\Eloquent\Model;
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
        $audience = $request->input('audience', $model instanceof PushNotification ? $model->audience : 'ALL');

        return [
            'title' => [$required, 'string', 'max:255'],
            'body' => [$required, 'string', 'max:1000'],
            'type' => ['nullable', Rule::in(PushNotification::TYPES)],
            'news_id' => ['nullable', 'uuid', 'exists:news,id'],
            'event_id' => ['nullable', 'uuid', 'exists:events,id'],
            'audience' => ['nullable', Rule::in(PushNotification::AUDIENCES)],
            'district_id' => [$audience === 'DISTRICT' ? 'required' : 'prohibited', 'uuid', 'exists:districts,id'],
            'zone_id' => [$audience === 'ZONE' ? 'required' : 'prohibited', 'uuid', 'exists:zones,id'],
            'church_id' => [$audience === 'CHURCH' ? 'required' : 'prohibited', 'uuid', 'exists:churches,id'],
            'status' => ['nullable', Rule::in(PushNotification::STATUSES)],
        ];
    }
}
