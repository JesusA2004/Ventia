<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidLicenseKeyException extends RuntimeException
{
    public static function notFound(): self
    {
        return new self('El serial capturado no es válido.');
    }

    public static function alreadyUsed(): self
    {
        return new self('Este serial ya fue utilizado.');
    }

    public static function revoked(): self
    {
        return new self('Este serial fue revocado y ya no puede utilizarse.');
    }
}
