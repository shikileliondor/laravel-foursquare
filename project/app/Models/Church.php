<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Church extends Model
{
    use HasSlug, HasUuids;

    public const STATUSES = ['ACTIVE', 'INACTIVE'];

    protected $table = 'churches';

    protected $fillable = [
        'zone_id', 'name', 'slug', 'code', 'pastor_name', 'address', 'commune', 'quartier',
        'secretariat_phone', 'official_whatsapp', 'official_email', 'latitude', 'longitude',
        'main_service_day', 'main_service_time', 'description', 'image_media_id', 'status',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * @return BelongsTo<Zone, $this>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_media_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.$term.'%';
        $op = Sql::like();

        return $query->where(function (Builder $q) use ($like, $op) {
            $q->where('churches.name', $op, $like)
                ->orWhere('churches.commune', $op, $like)
                ->orWhere('churches.quartier', $op, $like)
                ->orWhereHas('zone', fn (Builder $z) => $z->where('name', $op, $like)
                    ->orWhereHas('district', fn (Builder $d) => $d->where('name', $op, $like)));
        });
    }
}
