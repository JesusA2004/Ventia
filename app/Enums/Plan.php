<?php

namespace App\Enums;

/**
 * The two commercial tiers Ventia ships with. Basic already includes every
 * module that existed before P2 (POS, inventory, promotions, reports, ...) —
 * nothing was moved behind a gate to make Pro look bigger. Pro is Basic plus
 * the Feature set below. See FeatureGateService for how this is consulted
 * and CompanyEntitlement for how a company's plan is stored.
 */
enum Plan: string
{
    case Basic = 'basic';
    case Pro = 'pro';

    public function label(): string
    {
        return match ($this) {
            self::Basic => 'Ventia Básico',
            self::Pro => 'Ventia Pro',
        };
    }

    /** @return list<Feature> */
    public function features(): array
    {
        return match ($this) {
            self::Basic => [],
            self::Pro => [Feature::Purchasing],
        };
    }

    public function includesFeature(Feature $feature): bool
    {
        return in_array($feature, $this->features(), true);
    }
}
