<?php

namespace App\Support;

/**
 * Renders a bar chart as a PNG (via GD, already required by the platform —
 * no new dependency) embedded as a base64 data URI <img>. Replaces the
 * previous inline-<svg> approach: dompdf's SVG support does not reliably
 * honor <text> positioning/text-anchor, which made multi-bar charts render
 * as a run of unpositioned, concatenated text instead of a chart. A raster
 * image has no such ambiguity in dompdf.
 *
 * Renders at 2x and displays at logical size, so print output stays crisp.
 * Supports negative values (e.g. cash differences) with bars extending
 * below a zero baseline instead of assuming an all-positive dataset.
 */
final class PdfBarChart
{
    private const SCALE = 2;

    private const MAX_BARS = 20;

    /**
     * @param  list<string>  $labels
     * @param  list<float>  $values
     */
    public static function render(
        array $labels,
        array $values,
        string $title,
        string $color = '#4f46e5',
        int $width = 680,
        int $height = 220,
    ): string {
        if (count($values) === 0) {
            return '';
        }

        if (count($values) > self::MAX_BARS) {
            $labels = array_slice($labels, -self::MAX_BARS);
            $values = array_slice($values, -self::MAX_BARS);
        }

        $count = count($values);
        $scale = self::SCALE;
        $w = max(1, $width * $scale);
        $h = max(1, $height * $scale);

        $im = imagecreatetruecolor($w, $h);
        $white = self::allocateColor($im, 255, 255, 255);
        imagefill($im, 0, 0, $white);

        [$r, $g, $b] = self::hexToRgb($color);
        $barColor = self::allocateColor($im, $r, $g, $b);
        $negativeColor = self::allocateColor($im, 220, 38, 38);
        $titleColor = self::allocateColor($im, 31, 41, 51);
        $labelColor = self::allocateColor($im, 82, 96, 109);
        $valueColor = self::allocateColor($im, 31, 41, 51);
        $gridColor = self::allocateColor($im, 217, 226, 236);

        $fontRegular = self::fontPath('DejaVuSans.ttf');
        $fontBold = self::fontPath('DejaVuSans-Bold.ttf');

        $maxValue = 0.0;
        $minValue = 0.0;

        foreach ($values as $value) {
            $maxValue = max($maxValue, $value);
            $minValue = min($minValue, $value);
        }

        $range = ($maxValue - $minValue) > 0 ? ($maxValue - $minValue) : 1.0;

        $chartLeft = 10 * $scale;
        $chartRight = $w - 10 * $scale;
        // Tall enough above the tallest possible bar's value label that it
        // never collides with the chart title drawn just above it.
        $chartTop = 42 * $scale;
        // Bottom margin is taller than a purely-positive chart needs so a
        // negative bar's value label (drawn below the bar) always has room
        // to sit above the category-label row instead of colliding with it.
        $chartBottom = $h - 54 * $scale;
        $chartHeight = $chartBottom - $chartTop;
        $barGap = 8 * $scale;
        $barWidth = max(4 * $scale, (($chartRight - $chartLeft) / $count) - $barGap);

        $zeroY = (int) round($chartBottom - ((0 - $minValue) / $range) * $chartHeight);

        imageline($im, (int) $chartLeft, $zeroY, (int) $chartRight, $zeroY, $gridColor);

        $titleSize = 12 * $scale * 0.75;
        $labelSize = 8 * $scale * 0.75;
        $valueSize = 8 * $scale * 0.75;

        imagettftext($im, $titleSize, 0, (int) $chartLeft, (int) round(15 * $scale), $titleColor, $fontBold, $title);

        // With many bars, only annotate every Nth one so text never overlaps —
        // every bar still renders, only the text labels are thinned out.
        $labelStep = $count > 8 ? (int) ceil($count / 8) : 1;

        foreach ($values as $index => $value) {
            $x = $chartLeft + $index * ($barWidth + $barGap);
            $valueY = (int) round($chartBottom - (($value - $minValue) / $range) * $chartHeight);
            $top = min($valueY, $zeroY);
            $bottom = max($valueY, $zeroY);
            $barColorForValue = $value < 0 ? $negativeColor : $barColor;

            if ($bottom > $top) {
                imagefilledrectangle($im, (int) round($x), $top, (int) round($x + $barWidth), $bottom, $barColorForValue);
            }

            if ($index % $labelStep !== 0) {
                continue;
            }

            $centerX = (int) round($x + $barWidth / 2);
            $label = self::truncate($labels[$index] ?? '', 12);

            self::centeredText($im, $fontRegular, $labelSize, $labelColor, $centerX, (int) round($chartBottom + 20 * $scale), $label);

            $valueLabelY = $value < 0
                ? min($bottom + (int) round(10 * $scale), (int) round($chartBottom + 12 * $scale))
                : max($top - (int) round(6 * $scale), (int) round(12 * $scale));

            self::centeredText($im, $fontRegular, $valueSize, $valueColor, $centerX, $valueLabelY, self::formatValue($value));
        }

        ob_start();
        imagepng($im);
        $png = (string) ob_get_clean();
        imagedestroy($im);

        return sprintf(
            '<img src="data:image/png;base64,%s" width="%d" height="%d" alt="%s" style="display:block" />',
            base64_encode($png),
            $width,
            $height,
            htmlspecialchars($title),
        );
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [(int) hexdec(substr($hex, 0, 2)), (int) hexdec(substr($hex, 2, 2)), (int) hexdec(substr($hex, 4, 2))];
    }

    private static function fontPath(string $filename): string
    {
        return base_path('vendor/dompdf/dompdf/lib/fonts/'.$filename);
    }

    /**
     * @param  \GdImage  $im
     */
    private static function allocateColor($im, int $red, int $green, int $blue): int
    {
        $clamp = fn (int $channel): int => max(0, min(255, $channel));

        // Only fails once a palette-based image already has every color slot
        // used, which imagecreatetruecolor's 24-bit canvas never hits.
        $color = imagecolorallocate($im, $clamp($red), $clamp($green), $clamp($blue));

        return $color === false ? 0 : $color;
    }

    /**
     * @param  \GdImage  $im
     */
    private static function centeredText($im, string $font, float $size, int $color, int $centerX, int $baselineY, string $text): void
    {
        $box = imagettfbbox($size, 0, $font, $text);
        $textWidth = $box === false ? 0.0 : abs($box[4] - $box[0]);
        imagettftext($im, $size, 0, (int) round($centerX - $textWidth / 2), $baselineY, $color, $font, $text);
    }

    private static function truncate(string $value, int $length): string
    {
        return mb_strlen($value) > $length ? mb_substr($value, 0, $length - 1).'…' : $value;
    }

    private static function formatValue(float $value): string
    {
        return $value === floor($value) ? number_format($value, 0) : number_format($value, 2);
    }
}
