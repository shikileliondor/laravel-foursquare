<?php

namespace App\Models;

use App\Models\Concerns\RefreshesPublicCache;
use Illuminate\Database\Eloquent\Model;

/**
 * Paramètres de la plateforme, modifiables depuis le back-office par le
 * super administrateur.
 */
class Setting extends Model
{
    use RefreshesPublicCache;

    /** Clés connues et leur règle de validation. */
    public const RULES = [
        'launch_church_id' => ['nullable', 'uuid', 'exists:churches,id'],
        'contact_email' => ['nullable', 'email', 'max:255'],
        'contact_phone' => ['nullable', 'string', 'max:30'],
        'contact_whatsapp' => ['nullable', 'string', 'max:30'],
        'facebook_url' => ['nullable', 'url:https', 'max:255'],
        'tiktok_url' => ['nullable', 'url:https', 'max:255'],
        'youtube_url' => ['nullable', 'url:https', 'max:255'],
    ];

    /** Pages officielles nationales affichées par l'application. */
    public const SOCIAL_KEYS = ['facebook_url', 'tiktok_url', 'youtube_url'];

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    public static function read(string $key, ?string $default = null): ?string
    {
        return static::query()->whereKey($key)->value('value') ?? $default;
    }

    public static function write(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * @return array<string, string|null>
     */
    public static function socialLinks(): array
    {
        $stored = static::query()->whereIn('key', self::SOCIAL_KEYS)->pluck('value', 'key');

        return collect(self::SOCIAL_KEYS)
            ->mapWithKeys(fn (string $key) => [$key => $stored[$key] ?? null])
            ->all();
    }

    /**
     * @return array<string, string|null>
     */
    public static function known(): array
    {
        $stored = static::query()->pluck('value', 'key');

        return collect(array_keys(self::RULES))
            ->mapWithKeys(fn (string $key) => [$key => $stored[$key] ?? null])
            ->all();
    }
}
