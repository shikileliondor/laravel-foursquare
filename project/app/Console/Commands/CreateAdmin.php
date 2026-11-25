<?php

namespace App\Console\Commands;

use App\Enums\AdminRole;
use App\Models\AuditLog;
use App\Models\Church;
use App\Models\District;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Initialisation sécurisée des comptes : seul moyen de créer le premier
 * administrateur en production. Le mot de passe est demandé de façon masquée.
 */
class CreateAdmin extends Command
{
    protected $signature = 'foursquare:create-admin
        {email? : E-mail de connexion}
        {--name= : Nom affiché}
        {--role=SUPER_ADMIN : SUPER_ADMIN, NATIONAL_ADMIN, DISTRICT_ADMIN ou CHURCH_ADMIN}
        {--district= : Slug ou identifiant du district (DISTRICT_ADMIN)}
        {--church= : Slug ou identifiant de l\'église (CHURCH_ADMIN)}
        {--password= : Mot de passe ; à éviter, il resterait dans l\'historique du shell}';

    protected $description = 'Crée ou met à jour un compte administrateur';

    public function handle(): int
    {
        $role = AdminRole::tryFrom(strtoupper((string) $this->option('role')));

        if (! $role) {
            $this->error('Rôle inconnu. Valeurs possibles : '.implode(', ', AdminRole::values()));

            return self::FAILURE;
        }

        $districtId = null;
        $churchId = null;

        if ($role === AdminRole::DistrictAdmin) {
            $districtId = $this->findId(District::class, (string) $this->option('district'));

            if (! $districtId) {
                $this->error('District introuvable : précisez --district=<slug ou identifiant>.');

                return self::FAILURE;
            }
        }

        if ($role === AdminRole::ChurchAdmin) {
            $churchId = $this->findId(Church::class, (string) $this->option('church'));

            if (! $churchId) {
                $this->error('Église introuvable : précisez --church=<slug ou identifiant>.');

                return self::FAILURE;
            }
        }

        $input = [
            'email' => $this->argument('email') ?? text('E-mail', required: true),
            'name' => $this->option('name') ?? text('Nom', default: 'Administrateur', required: true),
            'password' => $this->option('password')
                ?? password('Mot de passe (12 caractères, majuscules, minuscules et chiffres)', required: true),
        ];

        $validator = Validator::make($input, [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', Password::min(12)->mixedCase()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(['email' => $input['email']], [
            'name' => $input['name'],
            'password' => $input['password'],
            'role' => $role->value,
            'status' => 'ACTIVE',
            'district_id' => $districtId,
            'church_id' => $churchId,
            'email_verified_at' => now(),
        ]);

        AuditLog::record('admin.saved_from_cli', $user, ['role' => $role->value]);

        $this->info("{$role->label()} prêt : {$user->email}");

        return self::SUCCESS;
    }

    /**
     * @param  class-string<District|Church>  $model
     */
    private function findId(string $model, string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        return $model::query()->whereKey($value)->orWhere('slug', $value)->value('id');
    }
}
