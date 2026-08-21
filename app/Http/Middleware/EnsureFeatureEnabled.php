<?php

namespace App\Http\Middleware;

use App\Enums\Feature;
use App\Services\Licensing\FeatureGateService;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * The real security boundary for Pro-only routes — registered as the
 * `feature` route middleware alias, e.g. ->middleware('feature:purchasing').
 * The sidebar padlock is UX only; this is what stops a Basic company from
 * reaching /suppliers (or any Pro endpoint, including JSON option/search
 * endpoints — those need the middleware too, not just full pages) by typing
 * the URL directly. Applies regardless of the acting user's permissions or
 * superadmin status: a Superadministrator operating a Basic active company
 * sees it locked too, because the entitlement belongs to the company, not
 * the user — see FeatureGateService.
 */
class EnsureFeatureEnabled
{
    public function __construct(private readonly FeatureGateService $features) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $featureEnum = Feature::from($feature);

        if (! $this->features->allows($featureEnum)) {
            return Inertia::render('Billing/FeatureLocked', [
                'feature' => $featureEnum->value,
                'featureLabel' => $featureEnum->label(),
            ])->toResponse($request)->setStatusCode(403);
        }

        return $next($request);
    }
}
