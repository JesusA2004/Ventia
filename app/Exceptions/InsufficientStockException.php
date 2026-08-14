<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    /**
     * Used by RecordInventoryMovementAction, the single write path shared by
     * sales, adjustments, transfers and counts — every negative-stock
     * rejection across those modules flows through here, so enriching this
     * one message with product/warehouse context benefits all of them at
     * once instead of duplicating messaging per caller.
     */
    public static function forBalance(
        string $available,
        string $requested,
        string $productName,
        string $sku,
        string $warehouseName,
    ): self {
        return new self(
            "Solo hay {$available} unidades disponibles de «{$productName}» (SKU {$sku}) en {$warehouseName}. Se solicitaban {$requested}."
        );
    }

    /**
     * Joined with " | " rather than newlines: this message reaches the UI
     * as a single validation string and often ends up in a one-line toast,
     * so it needs to stay readable even where whitespace collapses.
     *
     * @param  list<array{name: string, sku: string, requested: string, available: string}>  $shortages
     */
    public static function forItems(array $shortages): self
    {
        $lines = array_map(
            fn (array $s) => "{$s['name']} ({$s['sku']}) — solicitado: {$s['requested']}, disponible: {$s['available']}",
            $shortages,
        );

        return new self('No se puede completar la venta por falta de existencias: '.implode(' | ', $lines).'.');
    }
}
