<?php

namespace App\Enums;

/**
 * A capability gated behind a commercial plan (see Plan::features()). Kept
 * as an enum rather than scattering string literals so every gate — the
 * `feature` middleware, FeatureGateService, shared Inertia props, sidebar —
 * checks the same finite set of names. Currently only the P3 procurement
 * bundle is Pro-only; add cases here (not `if plan === pro`) as more
 * Pro-only capabilities ship.
 */
enum Feature: string
{
    case Purchasing = 'purchasing';

    public function label(): string
    {
        return match ($this) {
            self::Purchasing => 'Abastecimiento (proveedores, cotizaciones, compras y recepciones)',
        };
    }
}
