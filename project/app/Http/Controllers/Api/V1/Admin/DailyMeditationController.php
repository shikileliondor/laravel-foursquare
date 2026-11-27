<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\DailyMeditationResource;
use App\Models\DailyMeditation;
use App\Services\DailyPushPublisher;
use App\Services\HtmlSanitizer;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DailyMeditationController extends CrudController
{
    protected string $model = DailyMeditation::class;

    protected string $resource = DailyMeditationResource::class;

    protected ?string $gate = 'manage-national';

    protected array $with = ['notification', 'image'];

    protected bool $stampsCreator = true;

    protected string $orderBy = 'publication_date';

    protected array $searchable = ['title', 'author', 'scripture_reference'];

    public function __construct(private readonly DailyPushPublisher $publisher) {}

    /**
     * Envoi manuel de la méditation du jour (relance après un échec, ou si la
     * tâche planifiée de 07 h 00 n'a pas tourné).
     */
    public function send(string $id): JsonResponse
    {
        Gate::authorize('manage-national');

        /** @var DailyMeditation $meditation */
        $meditation = DailyMeditation::findOrFail($id);

        if (! $meditation->isToday()) {
            return ApiResponse::error(
                'MEDITATION_NOT_TODAY',
                'Seule la méditation du jour peut être envoyée.',
                422,
            );
        }

        if ($this->publisher->alreadySent($meditation)) {
            return ApiResponse::error(
                'NOTIFICATION_NOT_SENDABLE',
                'La notification de cette méditation est déjà envoyée ou en cours d’envoi.',
                409,
            );
        }

        $this->publisher->publish($meditation);

        return ApiResponse::ok(DailyMeditationResource::make($meditation->fresh($this->with)));
    }

    /**
     * La méditation du jour part tout de suite vers les téléphones, une seule
     * fois. Une correction ultérieure ne renvoie rien.
     */
    protected function saved(Model $item): void
    {
        /** @var DailyMeditation $item */
        if ($item->isToday() && $item->notification_created_at === null) {
            $this->publisher->publish($item);
        }
    }

    protected function prepare(array $data, Request $request, ?Model $model = null): array
    {
        if (array_key_exists('content', $data)) {
            $data['content'] = HtmlSanitizer::clean($data['content']);
        }

        return $data;
    }

    protected function rules(Request $request, ?Model $model = null): array
    {
        $required = $model ? 'sometimes' : 'required';

        return [
            'title' => [$required, 'string', 'max:255'],
            'content' => [$required, 'string'],
            'author' => ['sometimes', 'nullable', 'string', 'max:255'],
            'scripture_reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'image_media_id' => ['sometimes', 'nullable', 'uuid', 'exists:media,id'],
            'publication_date' => [
                $required,
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, \Closure $fail) use ($model): void {
                    $exists = DailyMeditation::query()
                        ->whereDate('publication_date', (string) $value)
                        ->when($model, fn ($query) => $query->whereKeyNot($model->getKey()))
                        ->exists();

                    if ($exists) {
                        $fail('Une méditation est déjà programmée à cette date.');
                    }
                },
            ],
        ];
    }
}
