<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\DailyVerseResource;
use App\Models\DailyVerse;
use App\Services\DailyVersePublisher;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DailyVerseController extends CrudController
{
    protected string $model = DailyVerse::class;

    protected string $resource = DailyVerseResource::class;

    protected ?string $gate = 'manage-national';

    protected array $with = ['notification'];

    protected bool $stampsCreator = true;

    protected string $orderBy = 'publication_date';

    protected array $searchable = ['text', 'reference', 'bible_version'];

    public function __construct(private readonly DailyVersePublisher $publisher) {}

    /**
     * Envoi manuel du verset du jour : utile si la notification a échoué ou si
     * la tâche planifiée de 07 h 00 n'a pas tourné.
     */
    public function send(string $id): JsonResponse
    {
        Gate::authorize('manage-national');

        /** @var DailyVerse $verse */
        $verse = DailyVerse::findOrFail($id);

        if (! $verse->isToday()) {
            return ApiResponse::error(
                'VERSE_NOT_TODAY',
                'Seul le verset du jour peut être envoyé.',
                422,
            );
        }

        if ($this->publisher->alreadySent($verse)) {
            return ApiResponse::error(
                'NOTIFICATION_NOT_SENDABLE',
                'La notification de ce verset est déjà envoyée ou en cours d’envoi.',
                409,
            );
        }

        $this->publisher->publish($verse);

        return ApiResponse::ok(DailyVerseResource::make($verse->fresh($this->with)));
    }

    /**
     * Le verset du jour part tout de suite vers les téléphones, une seule fois.
     * Une correction ultérieure du texte ne renvoie rien.
     */
    protected function saved(Model $item): void
    {
        /** @var DailyVerse $item */
        if ($item->isToday() && $item->notification_created_at === null) {
            $this->publisher->publish($item);
        }
    }

    protected function rules(Request $request, ?Model $model = null): array
    {
        $required = $model ? 'sometimes' : 'required';

        return [
            'text' => [$required, 'string'],
            'reference' => [$required, 'string', 'max:255'],
            'bible_version' => [$required, 'string', 'max:255'],
            'publication_date' => [
                $required,
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, \Closure $fail) use ($model): void {
                    $exists = DailyVerse::query()
                        ->whereDate('publication_date', (string) $value)
                        ->when($model, fn ($query) => $query->whereKeyNot($model->getKey()))
                        ->exists();

                    if ($exists) {
                        $fail('Un verset est déjà programmé à cette date.');
                    }
                },
            ],
        ];
    }
}
