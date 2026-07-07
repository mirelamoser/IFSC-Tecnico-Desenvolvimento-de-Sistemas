<?php

declare(strict_types=1);

namespace PhiloQuest;

use PhiloQuest\Enum\EpocaFilosofo;

final class Etapa4FilosofoHelper
{
    public static function epocaValida(string $valor): bool
    {
        return EpocaFilosofo::tryFrom($valor) !== null;
    }

    public static function rotuloEpoca(string $valor): string
    {
        $enum = EpocaFilosofo::tryFrom($valor);

        return $enum?->rotulo() ?? $valor;
    }
}
