<?php

declare(strict_types=1);

namespace PhiloQuest\Enum;

enum EpocaFilosofo: string
{
    case ANTIGA = 'ANTIGA';
    case MEDIEVAL = 'MEDIEVAL';
    case MODERNA = 'MODERNA';
    case CONTEMPORANEA = 'CONTEMPORANEA';

    public function rotulo(): string
    {
        return match ($this) {
            self::ANTIGA => 'Antiga',
            self::MEDIEVAL => 'Medieval',
            self::MODERNA => 'Moderna',
            self::CONTEMPORANEA => 'Contemporânea',
        };
    }
}
