<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Services\ActiveCompanyContext;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lets a superadministrator pick which company they're acting on. Regular
 * users never see this screen — they always operate on their own company.
 */
class CompanySelectionController extends Controller
{
    public function index(Request $request, ActiveCompanyContext $context): Response|RedirectResponse
    {
        abort_unless($context->isSuperAdmin(), 403);

        if ($context->companyId() !== null) {
            return to_route('dashboard');
        }

        $companies = Company::query()->where('status', Status::Active)->orderBy('name')->get();

        return Inertia::render('Companies/Select', [
            'companies' => CompanyResource::collection($companies),
        ]);
    }

    public function store(Request $request, ActiveCompanyContext $context, AuditLogger $audit): RedirectResponse
    {
        abort_unless($context->isSuperAdmin(), 403);

        $validated = $request->validate([
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')->where('status', Status::Active->value)],
        ]);

        $company = Company::query()->find((int) $validated['company_id']);

        $context->select((int) $validated['company_id']);

        $audit->log('companies', 'active_company_changed', "Cambió la empresa activa a «{$company?->name}».", $company);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Empresa activa cambiada correctamente.']);

        return to_route('dashboard');
    }
}
