<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Licensing\RedeemLicenseKeyAction;
use App\Exceptions\InvalidLicenseKeyException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Licensing\ActivateLicenseRequest;
use App\Http\Resources\CompanyEntitlementResource;
use App\Services\ActiveCompanyContext;
use App\Services\Audit\AuditLogger;
use App\Services\Licensing\FeatureGateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The company-facing "Plan y licencia" screen. Never lists other companies'
 * plans or the global serial stock — only the active company's own
 * entitlement (see FeatureGateService::entitlementFor()).
 */
class LicenseController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function show(ActiveCompanyContext $activeCompany, FeatureGateService $features): Response
    {
        $company = $activeCompany->company();

        abort_if($company === null, 404);

        $this->authorize('update', $company);

        $entitlement = $features->entitlementFor($company);
        $entitlement?->load(['activatedByUser:id,name', 'licenseKey']);

        return Inertia::render('Admin/Plan', [
            'entitlement' => $entitlement !== null
                ? CompanyEntitlementResource::make($entitlement)
                : ['plan' => 'basic', 'plan_label' => 'Ventia Básico', 'is_pro' => false, 'activated_at' => null, 'activated_by' => null, 'license_masked' => null],
        ]);
    }

    public function activate(ActivateLicenseRequest $request, ActiveCompanyContext $activeCompany, RedeemLicenseKeyAction $redeem): RedirectResponse
    {
        $company = $activeCompany->company();

        abort_if($company === null, 404);

        try {
            $redeem->execute($request->validated('code'), $company, $request->user());
        } catch (InvalidLicenseKeyException $e) {
            throw ValidationException::withMessages(['code' => $e->getMessage()]);
        }

        $this->audit->log('licenses', 'activated', "Activó Ventia Pro para «{$company->name}».", $company);

        Inertia::flash('toast', ['type' => 'success', 'message' => '¡Ventia Pro activado correctamente!']);

        return to_route('settings.license.show');
    }
}
