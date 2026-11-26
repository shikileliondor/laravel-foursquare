<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\DailyVerseResource;
use App\Models\DailyVerse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class DailyVerseController extends CrudController
{
    protected string $model = DailyVerse::class;

    protected string $resource = DailyVerseResource::class;

    protected ?string $gate = 'manage-national';

    protected bool $stampsCreator = true;

    protected string $orderBy = 'publication_date';

    protected array $searchable = ['text', 'reference', 'bible_version'];

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
