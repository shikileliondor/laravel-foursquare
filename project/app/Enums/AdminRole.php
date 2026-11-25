<?php

namespace App\Enums;

/**
 * Les quatre rôles d'administration de la V1. Fixés dans le code : les
 * droits de chacun sont décrits par les Policies, pas par une table.
 */
enum AdminRole: string
{
    case SuperAdmin = 'SUPER_ADMIN';
    case NationalAdmin = 'NATIONAL_ADMIN';
    case DistrictAdmin = 'DISTRICT_ADMIN';
    case ChurchAdmin = 'CHURCH_ADMIN';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super administrateur',
            self::NationalAdmin => 'Administrateur national',
            self::DistrictAdmin => 'Administrateur de district',
            self::ChurchAdmin => 'Administrateur d\'église',
        };
    }

    /**
     * Périmètre national : voit et gère l'ensemble du pays.
     */
    public function isNational(): bool
    {
        return $this === self::SuperAdmin || $this === self::NationalAdmin;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }
}
