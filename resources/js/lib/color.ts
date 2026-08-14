const HEX_PATTERN = /^#[0-9a-fA-F]{6}$/;

export function isValidHex(value: string | null | undefined): value is string {
    return typeof value === 'string' && HEX_PATTERN.test(value);
}

/**
 * Picks black or white text over a background color using relative
 * luminance (WCAG-ish approximation), so a light user-chosen color never
 * ends up with light text on top of it (or the reverse).
 */
export function readableForeground(hex: string): '#000000' | '#ffffff' {
    if (!isValidHex(hex)) {
        return '#ffffff';
    }

    const r = parseInt(hex.slice(1, 3), 16) / 255;
    const g = parseInt(hex.slice(3, 5), 16) / 255;
    const b = parseInt(hex.slice(5, 7), 16) / 255;

    const toLinear = (c: number) =>
        c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;

    const luminance =
        0.2126 * toLinear(r) + 0.7152 * toLinear(g) + 0.0722 * toLinear(b);

    return luminance > 0.55 ? '#000000' : '#ffffff';
}
