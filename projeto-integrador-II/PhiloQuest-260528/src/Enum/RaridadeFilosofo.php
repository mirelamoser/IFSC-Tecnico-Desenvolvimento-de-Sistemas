<?php

declare(strict_types=1);

namespace PhiloQuest\Enum;

enum RaridadeFilosofo: string {
    case COMUM = 'COMUM';
    case RARA = 'RARA';
    case EPICA = 'EPICA';
    case LENDARIA = 'LENDARIA';
}