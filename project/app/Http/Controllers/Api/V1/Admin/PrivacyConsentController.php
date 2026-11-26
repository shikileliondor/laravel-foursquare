<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\PrivacyConsentEventResource;
use App\Models\PrivacyConsentEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Journal des consentements, en lecture seule : ces preuves ne se modifient
 * ni ne se suppriment depuis l'administration.
 */
class PrivacyConsentController extends CrudController
{
    protected string $model = PrivacyConsentEvent::class;

    protected string $resource = PrivacyConsentEventResource::class;

    protected ?string $gate = 'manage-national';

    /** @var list<string> */
    protected array $with = ['church:id,name'];

    /** @var list<string> */
    protected array $searchable = ['subject_id', 'purpose'];

    protected string $orderBy = 'occurred_at';

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model = null): array
    {
        return [];
    }
}
