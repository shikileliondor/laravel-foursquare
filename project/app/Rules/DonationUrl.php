<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Lien de paiement en https, chez un prestataire autorisé
 * (config `app.donation_hosts`, par défaut pay.wave.com).
 */
class DonationUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parts = is_string($value) ? parse_url($value) : false;
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (! is_array($parts) || ($parts['scheme'] ?? null) !== 'https' || ! in_array($host, (array) config('app.donation_hosts'), true)) {
            $fail('Le lien de don doit être une adresse https d’un prestataire autorisé ('.implode(', ', (array) config('app.donation_hosts')).').');
        }
    }
}
