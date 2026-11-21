<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Otra persona cambió la reparación mientras alguien la tenía abierta en el modal
 * de edición: se rechaza el guardado para no pisar sus cambios sin avisar.
 */
class ConflictoEdicionException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Otra persona cambió esta reparación mientras la editabas. Carga sus cambios y vuelve a guardar.'
        );
    }
}
