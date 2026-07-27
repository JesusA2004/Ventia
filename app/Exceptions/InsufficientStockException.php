<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(string $available)
    {
        parent::__construct("Stock insuficiente: disponible {$available}. Esta empresa no permite inventario negativo para este producto.");
    }
}
