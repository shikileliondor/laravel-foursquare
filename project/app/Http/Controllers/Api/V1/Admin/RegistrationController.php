<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\EventRegistrationResource;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\Sql;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Inscriptions aux événements : consultation, export et suppression,
 * limitées aux événements que l'administrateur gère.
 */
class RegistrationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', EventRegistration::class);

        $registrations = $this->filtered($request)
            ->with(['event:id,title', 'church:id,name'])
            ->latest()
            ->paginate($this->perPage($request));

        return ApiResponse::paginated($registrations, EventRegistrationResource::class);
    }

    /**
     * CSV (séparateur `;`, UTF-8 avec BOM) lisible directement dans Excel.
     */
    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', EventRegistration::class);

        $query = $this->filtered($request)->with(['event:id,title', 'church:id,name'])->oldest();

        AuditLog::record('registrations.exported', null, ['event_id' => $request->input('event_id')]);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Événement', 'Nom et prénoms', 'Téléphone', 'E-mail', 'Église', 'Inscrit le'], ';');

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->event?->title,
                        $row->full_name,
                        $row->phone,
                        $row->email,
                        $row->church->name ?? $row->church_name,
                        $row->created_at?->format('d/m/Y H:i'),
                    ], ';');
                }
            });

            fclose($out);
        }, 'inscriptions-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(string $id): JsonResponse
    {
        $registration = EventRegistration::with('event')->findOrFail($id);

        Gate::authorize('delete', $registration);

        $registration->delete();

        AuditLog::record('deleted', $registration);

        return ApiResponse::ok(['deleted' => true]);
    }

    /**
     * @return Builder<EventRegistration>
     */
    private function filtered(Request $request): Builder
    {
        /** @var User $user */
        $user = $request->user();

        return EventRegistration::query()
            ->whereIn('event_id', Event::query()->manageableBy($user)->select('events.id'))
            ->when($request->filled('event_id'), fn ($q) => $q->where('event_id', $request->string('event_id')->toString()))
            ->when($request->filled('search'), function ($q) use ($request) {
                $like = '%'.$request->string('search')->toString().'%';
                $q->where(fn ($inner) => $inner->where('full_name', Sql::like(), $like)->orWhere('phone', Sql::like(), $like));
            });
    }
}
