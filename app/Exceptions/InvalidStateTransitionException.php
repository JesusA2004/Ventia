<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidStateTransitionException extends RuntimeException
{
    public function __construct(string $entity, string $currentState, string $action)
    {
        parent::__construct("No se puede {$action} porque {$entity} está en estado «{$currentState}».");
    }
}
