<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Services\ActiveCompanyContext;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function edit(Request $request, ActiveCompanyContext $activeCompany): Response
    {
        $company = $activeCompany->company();

        abort_if($company === null, 404);

        $this->authorize('update', $company);

        return Inertia::render('Admin/Company', [
            'company' => CompanyResource::make($company),
        ]);
    }

    public function update(UpdateCompanyRequest $request, ActiveCompanyContext $activeCompany): RedirectResponse
    {
        $company = $activeCompany->company();

        abort_if($company === null, 404);

        $before = $company->only(['name', 'legal_name', 'address', 'phone', 'email', 'currency', 'timezone', 'primary_color', 'secondary_color']);
        $company->update($request->validated());

        $this->audit->log(
            'companies', 'updated',
            "Actualizó los datos de la empresa «{$company->name}».",
            $company,
            $before,
            $company->only(['name', 'legal_name', 'address', 'phone', 'email', 'currency', 'timezone', 'primary_color', 'secondary_color']),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Empresa actualizada correctamente.']);

        return to_route('settings.company.edit');
    }
}
