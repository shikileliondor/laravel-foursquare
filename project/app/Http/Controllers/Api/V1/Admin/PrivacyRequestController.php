<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Resources\PrivacyDataRequestResource;
use App\Models\PrivacyDataRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Demandes d'accès, rectification, suppression, opposition et portabilité :
 * consultation et suivi du traitement. Les données concernées sont modifiées
 * dans leurs propres écrans (inscriptions, appareils).
 */
class PrivacyRequestController extends CrudController
{
    protected string $model = PrivacyDataRequest::class;

    protected string $resource = PrivacyDataRequestResource::class;

    /** Données personnelles de tout le pays : niveau national. */
    protected ?string $gate = 'manage-national';

    /** @var list<string> */
    protected array $with = ['event:id,title', 'handler:id,name'];

    /** @var list<string> */
    protected array $searchable = ['reference', 'full_name', 'phone', 'email'];

    /**
     * @return array<string, mixed>
     */
    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'status' => ['required', Rule::in(PrivacyDataRequest::STATUSES)],
            'resolution_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Horodate la clôture et retient l'administrateur qui a traité la demande.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepare(array $data, Request $request, ?Model $model = null): array
    {
        $closed = in_array($data['status'], PrivacyDataRequest::CLOSED_STATUSES, true);

        $data['handled_by'] = $request->user()?->id;
        $data['completed_at'] = $closed ? ($model?->getAttribute('completed_at') ?? now()) : null;

        return $data;
    }
}
