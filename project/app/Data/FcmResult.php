<?php

namespace App\Data;

/**
 * Issue d'un envoi FCM pour un seul appareil.
 */
readonly class FcmResult
{
    public function __construct(
        public bool $delivered,
        /** Le token est définitivement invalide : l'appareil doit être supprimé. */
        public bool $stale = false,
        public ?string $error = null,
    ) {}

    public static function ok(): self
    {
        return new self(delivered: true);
    }

    public static function stale(string $error): self
    {
        return new self(delivered: false, stale: true, error: $error);
    }

    public static function failed(string $error): self
    {
        return new self(delivered: false, error: $error);
    }
}
