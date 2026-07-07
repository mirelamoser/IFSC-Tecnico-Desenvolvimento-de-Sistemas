<?php

declare(strict_types=1);

namespace PhiloQuest;

use Exception;
use PDOException;

final class ExceptionHelper
{
    /**
     * Registra o erro PDO e devolve uma exceção com mensagem segura para o utilizador.
     */
    public static function fromPdo(PDOException $e, string $userMessage): Exception
    {
        error_log($userMessage . ' [' . $e->getMessage() . ']');

        return new Exception($userMessage);
    }
}
