<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Retrouve un enregistrement par son identifiant ou son slug : les
     * bannières et notifications donnent un identifiant, les URL un slug.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeIdentifiedBy($query, string $key)
    {
        return $query->where(fn ($q) => $q->where($this->qualifyColumn('slug'), $key)->orWhere($this->getQualifiedKeyName(), $key));
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
