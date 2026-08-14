import { usePage } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import { isValidHex, readableForeground } from '@/lib/color';

const VARIABLE_PAIRS: Array<{
    source: 'primary_color' | 'secondary_color';
    targets: string[];
    foregroundTargets: string[];
}> = [
    {
        source: 'primary_color',
        targets: ['--primary', '--sidebar-primary', '--ring', '--sidebar-ring'],
        foregroundTargets: ['--primary-foreground', '--sidebar-primary-foreground'],
    },
    {
        source: 'secondary_color',
        targets: ['--secondary'],
        foregroundTargets: ['--secondary-foreground'],
    },
];

/**
 * Applies the active company's brand colors as CSS custom properties on the
 * document root, so every `bg-primary`/`text-secondary`/etc. utility across
 * the app picks them up automatically (Tailwind's `@theme inline` maps
 * `--color-primary` straight to `--primary`). Re-runs whenever the shared
 * `activeCompany` Inertia prop changes — including when a superadmin
 * switches active company — and falls back to the stylesheet defaults for
 * any color the company hasn't set.
 */
export function useCompanyTheme() {
    const page = usePage();

    watchEffect(() => {
        const company = page.props.activeCompany;
        const root = document.documentElement;

        for (const pair of VARIABLE_PAIRS) {
            const hex = company?.[pair.source];

            if (isValidHex(hex)) {
                const foreground = readableForeground(hex);

                pair.targets.forEach((target) => root.style.setProperty(target, hex));
                pair.foregroundTargets.forEach((target) =>
                    root.style.setProperty(target, foreground),
                );
            } else {
                pair.targets.forEach((target) => root.style.removeProperty(target));
                pair.foregroundTargets.forEach((target) =>
                    root.style.removeProperty(target),
                );
            }
        }
    });
}
