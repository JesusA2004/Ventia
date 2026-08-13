<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    /**
     * Low-level fallback used by RecordInventoryMovementAction, the single
     * write path shared by sales, adjustments, transfers and counts — it
     * only has a balance, not product context, so its message stays generic.
     * CompleteSaleAction's pre-flight check (forItems()) is what produces
     * the detailed, per-product message shown at POS checkout.
     */
    public static function forBalance(string $available): self
    {
        return new self("Stock insuficiente: disponible {$available}. Esta empresa no permite inventario negativo para este producto.");
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
