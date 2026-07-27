<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function edit(Request $request): Response
    {
        $company = $request->user()->company()->firstOrFail();

        $this->authorize('update', $company);

        return Inertia::render('Admin/Company', [
            'company' => CompanyResource::make($company),
        ]);
    }

    public function update(UpdateCompanyRequest $request): RedirectResponse
    {
        $company = $request->user()->company()->firstOrFail();

        $company->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Empresa actualizada correctamente.']);

        return to_route('settings.company.edit');
    }
}
