<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Actions\Licensing\GenerateLicenseKeysAction;
use App\Actions\Licensing\RevokeLicenseKeyAction;
use App\Enums\LicenseKeyStatus;
use App\Enums\Plan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Licensing\GenerateLicenseKeysRequest;
use App\Http\Requests\Licensing\RevokeLicenseKeyRequest;
use App\Http\Resources\LicenseKeyResource;
use App\Models\LicenseKey;
use App\Services\Audit\AuditLogger;
use App\Support\PaginatedResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Licencias Ventia" — Superadministrator-only, exactly like AuditLogController:
 * every action hard-checks isSuperAdmin() in the controller, not only via a
 * permission, so a company admin can never list the serial stock even if a
 * role is misconfigured (see item 28/65 of the spec this shipped against).
 */
class LicenseKeyController extends Controller
{
    public function __construct(private readonly AuditLogger $audit)
    {
        $this->middleware(function (Request $request, \Closure $next) {
            abort_unless($request->user()?->isSuperAdmin(), 403, 'Solo un Superadministrador puede gestionar licencias.');

            return $next($request);
        });
    }

    public function index(Request $request): Response
    {
        $keys = LicenseKey::query()
            ->with(['company:id,name', 'generatedByUser:id,name', 'redeemedByUser:id,name', 'revokedByUser:id,name'])
            ->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))
            ->when($request->integer('company_id'), fn ($q, $id) => $q->where('company_id', $id))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('SuperAdmin/Licenses/Index', [
            'keys' => PaginatedResource::make($keys, LicenseKeyResource::class),
            'filters' => $request->only(['status', 'company_id']),
            'statusOptions' => collect(LicenseKeyStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'counts' => [
                'available' => LicenseKey::query()->where('status', LicenseKeyStatus::Available)->count(),
                'redeemed' => LicenseKey::query()->where('status', LicenseKeyStatus::Redeemed)->count(),
                'revoked' => LicenseKey::query()->where('status', LicenseKeyStatus::Revoked)->count(),
            ],
        ]);
    }

    public function store(GenerateLicenseKeysRequest $request, GenerateLicenseKeysAction $generate): RedirectResponse
    {
        $results = $generate->execute(
            Plan::Pro,
            $request->validated('quantity'),
            $request->user(),
            $request->validated('notes'),
        );

        foreach ($results as $result) {
            $this->audit->log(
                'licenses', 'generated',
                "Generó una licencia Ventia Pro {$result['key']->masked()}.",
                $result['key'],
            );
        }

        // Plaintext codes only ever exist in this response — the flash is
        // read once by the frontend and never persisted anywhere.
        Inertia::flash('generatedCodes', collect($results)->map(fn ($r) => [
            'id' => $r['key']->id,
            'plain' => $r['plain'],
        ])->all());
        Inertia::flash('toast', ['type' => 'success', 'message' => count($results) === 1
            ? 'Serial generado correctamente.'
            : count($results).' seriales generados correctamente.',
        ]);

        return to_route('super-admin.licenses.index');
    }

    public function revoke(RevokeLicenseKeyRequest $request, LicenseKey $licenseKey, RevokeLicenseKeyAction $revoke): RedirectResponse
    {
        $revoke->execute($licenseKey, $request->user(), $request->validated('reason'));

        $this->audit->log(
            'licenses', 'revoked',
            "Revocó la licencia Ventia Pro {$licenseKey->masked()}.",
            $licenseKey,
            reason: $request->validated('reason'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Serial revocado correctamente.']);

        return to_route('super-admin.licenses.index');
    }

    public function reissue(RevokeLicenseKeyRequest $request, LicenseKey $licenseKey, RevokeLicenseKeyAction $revoke): RedirectResponse
    {
        $result = $revoke->reissue($licenseKey, $request->user(), $request->validated('reason'));

        $this->audit->log(
            'licenses', 'reissued',
            "Reemitió la licencia Ventia Pro {$licenseKey->masked()} como {$result['key']->masked()}.",
            $result['key'],
        );

        Inertia::flash('generatedCodes', [['id' => $result['key']->id, 'plain' => $result['plain']]]);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Serial reemitido correctamente.']);

        return to_route('super-admin.licenses.index');
    }
}
