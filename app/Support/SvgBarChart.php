<?php

namespace App\Support;

/**
 * Renders a minimal bar chart as inline SVG markup for embedding in a
 * DomPDF-generated report. DomPDF cannot execute Chart.js (canvas-based), so
 * this trades chart sophistication for something DomPDF renders reliably:
 * plain <rect>/<text> elements, no gradients/shadows/animations.
 */
final class SvgBarChart
{
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
        $count = count($values);

        if ($count === 0) {
            return '';
        }

        $maxValue = max(max($values), 0.0001);
        $chartLeft = 10;
        $chartRight = $width - 10;
        $chartTop = 30;
        $chartBottom = $height - 40;
        $chartHeight = $chartBottom - $chartTop;
        $barGap = 8;
        $barWidth = max(4, (($chartRight - $chartLeft) / $count) - $barGap);

        $bars = '';

        foreach ($values as $index => $value) {
            $barHeight = $maxValue > 0 ? ($value / $maxValue) * $chartHeight : 0;
            $x = $chartLeft + $index * ($barWidth + $barGap);
            $y = $chartBottom - $barHeight;
            $label = self::truncate($labels[$index] ?? '', 12);

            $bars .= sprintf(
                '<rect x="%.2f" y="%.2f" width="%.2f" height="%.2f" fill="%s" />',
                $x, $y, $barWidth, $barHeight, $color,
            );
            $bars .= sprintf(
                '<text x="%.2f" y="%d" font-size="8" fill="#52606d" text-anchor="middle">%s</text>',
                $x + $barWidth / 2, $chartBottom + 12, htmlspecialchars($label),
            );
            $bars .= sprintf(
                '<text x="%.2f" y="%.2f" font-size="8" fill="#1f2933" text-anchor="middle">%s</text>',
                $x + $barWidth / 2, $y - 3, self::formatValue($value),
            );
        }

        return sprintf(
            '<svg width="%d" height="%d" viewBox="0 0 %d %d" xmlns="http://www.w3.org/2000/svg">'
                .'<text x="0" y="14" font-size="11" font-weight="bold" fill="#1f2933">%s</text>'
                .'<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#d9e2ec" stroke-width="1" />'
                .'%s'
                .'</svg>',
            $width, $height, $width, $height,
            htmlspecialchars($title),
            $chartLeft, $chartBottom, $chartRight, $chartBottom,
            $bars,
        );
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
