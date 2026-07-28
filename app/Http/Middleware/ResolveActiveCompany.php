<?php

namespace App\Http\Middleware;

use App\Services\ActiveCompanyContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Proactively gates the "enter as this company" screens (POS, abrir caja,
 * Mi empresa): a superadministrator without a valid active company is sent
 * to the selector with a clear message instead of hitting a crash further
 * down the request. Regular users are never affected — they always resolve
 * to their own company. This is a UX front-line only; the deeper safety net
 * is ActiveCompanyContext::requireCompanyId() throwing
 * NoActiveCompanySelectedException, which every write path goes through.
 */
class ResolveActiveCompany
{
    public function __construct(private readonly ActiveCompanyContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->context->isSuperAdmin() && $this->context->companyId() === null) {
            return redirect()
                ->route('company-selection.index')
                ->with('error', 'Selecciona una empresa activa para continuar.');
        }

        return $next($request);
    }
}
