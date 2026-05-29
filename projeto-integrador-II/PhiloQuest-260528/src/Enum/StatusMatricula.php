<?php

declare(strict_types=1);

namespace PhiloQuest\Enum;

enum StatusMatricula {
    case DISPONIVEL;
    case UTILIZADA;
    case BLOQUEADA;
}