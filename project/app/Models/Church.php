<?php

namespace App\Models;

use App\Enums\AdminRole;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\RefreshesPublicCache;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Church extends Model
{
    use HasSlug, HasUuids, RefreshesPublicCache;

    public const STATUSES = ['ACTIVE', 'INACTIVE'];

    protected $table = 'churches';

    protected $fillable = [
        'district_id', 'name', 'slug', 'code', 'pastor_name', 'address', 'commune', 'quartier',
        'secretariat_phone', 'official_whatsapp', 'official_email', 'latitude', 'longitude',
        'main_service_day', 'main_service_time', 'description', 'image_media_id', 'status',
        'city', 'service_times', 'facebook_url', 'youtube_url', 'instagram_url', 'tiktok_url', 'website_url',
        'donation_url',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    protected static function booted(): void
    {
        // Les notifications de district ciblent devices.district_id : un
        // appareil doit suivre son église quand elle change de district.
        static::updated(function (Church $church): void {
            if ($church->wasChanged('district_id')) {
                Device::query()->where('church_id', $church->id)
                    ->update(['district_id' => $church->district_id]);
            }
        });
    }

    /**
     * @return BelongsTo<District, $this>
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Équipe pastorale, dans l'ordre d'affichage.
     *
     * @return HasMany<ChurchLeader, $this>
     */
    public function leaders(): HasMany
    {
        return $this->hasMany(ChurchLeader::class)->orderBy('display_order')->orderBy('name');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_media_id');
    }

    /**
     * Églises gérées par l'administrateur.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeManageableBy(Builder $query, User $user): Builder
    {
        return match ($user->adminRole()) {
            AdminRole::SuperAdmin, AdminRole::NationalAdmin => $query,
            AdminRole::DistrictAdmin => $user->district_id
                ? $query->where('churches.district_id', $user->district_id)
                : $query->whereRaw('1 = 0'),
            AdminRole::ChurchAdmin => $user->church_id
                ? $query->whereKey($user->church_id)
                : $query->whereRaw('1 = 0'),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Visible publiquement : l'église et son district sont actifs.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('churches.status', 'ACTIVE')
            ->whereHas('district', fn (Builder $d) => $d->where('status', 'ACTIVE'));
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
                ->orWhereHas('district', fn (Builder $d) => $d->where('name', $op, $like));
        });
    }
}
