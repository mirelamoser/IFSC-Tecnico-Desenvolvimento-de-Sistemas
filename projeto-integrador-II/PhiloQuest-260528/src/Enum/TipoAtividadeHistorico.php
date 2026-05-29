<?php

declare(strict_types=1);

namespace PhiloQuest\Enum;

enum TipoAtividadeHistorico {
    case ETAPA_VALIDADA;
    case CONQUISTA;
    case NOVA_CARTA;
    case NIVEL_UP;
    case DISCUSSAO;
    case MISSAO;
}