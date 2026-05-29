<?php

declare(strict_types=1);

namespace PhiloQuest\Enum;

enum TipoPostMural {
    case CONQUISTA;
    case NIVEL_UP;
    case NOVA_CARTA;
    case MANUAL;
}