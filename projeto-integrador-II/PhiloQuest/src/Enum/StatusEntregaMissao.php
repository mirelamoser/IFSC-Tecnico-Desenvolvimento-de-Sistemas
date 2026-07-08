<?php

declare(strict_types=1);

namespace PhiloQuest\Enum;

enum StatusEntregaMissao: string
{
    case PENDENTE = 'PENDENTE';
    case APROVADO = 'APROVADO';
    case REVISAR = 'REVISAR';

    public function rotulo(): string
    {
        return match ($this) {
            self::PENDENTE => 'Aguardando avaliação',
            self::APROVADO => 'Aprovado',
            self::REVISAR => 'Revisar',
        };
    }

    public function corBadge(): string
    {
        return match ($this) {
            self::PENDENTE => '#F59E0B',
            self::APROVADO => '#27AE60',
            self::REVISAR => '#E53E3E',
        };
    }

    public function corFundoBadge(): string
    {
        return match ($this) {
            self::PENDENTE => '#FEF3C7',
            self::APROVADO => '#E8F5E9',
            self::REVISAR => '#FEE2E2',
        };
    }
}
