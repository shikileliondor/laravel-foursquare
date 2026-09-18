<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function (self $model): void {
            if (blank($model->slug)) {
                $model->slug = $model->generateSlug();
            }
        });
    }

    protected function slugSource(): string
    {
        return (string) ($this->title ?? $this->name ?? Str::random(8));
    }

    protected function generateSlug(): string
    {
        $base = Str::slug($this->slugSource()) ?: Str::lower(Str::random(8));
        $slug = $base;
        $i = 2;

        while (static::query()->where('slug', $slug)->whereKeyNot($this->getKey())->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
