<?php

declare(strict_types=1);

namespace PhiloQuest\Enum;

enum TipoPostMural: string
{
    case CONQUISTA = 'CONQUISTA';
    case NIVEL_UP = 'NIVEL_UP';
    case NOVA_CARTA = 'NOVA_CARTA';
    case MANUAL = 'MANUAL';
    case MISSAO = 'MISSAO';
    case CICLO_CONCLUIDO = 'CICLO_CONCLUIDO';

    public function icone(): string
    {
        return match ($this) {
            self::CONQUISTA => 'fa-trophy',
            self::NIVEL_UP => 'fa-arrow-up',
            self::NOVA_CARTA => 'fa-scroll',
            self::MANUAL => 'fa-bullhorn',
            self::MISSAO => 'fa-bolt',
            self::CICLO_CONCLUIDO => 'fa-flag-checkered',
        };
    }

    public function rotulo(): string
    {
        return match ($this) {
            self::CONQUISTA => 'Conquista',
            self::NIVEL_UP => 'Subiu de nível',
            self::NOVA_CARTA => 'Nova carta',
            self::MANUAL => 'Aviso',
            self::MISSAO => 'Missão extra',
            self::CICLO_CONCLUIDO => 'Ciclo concluído',
        };
    }

    /** @return array{cor: string, bg: string} */
    public function coresBadge(): array
    {
        return match ($this) {
            self::CONQUISTA => ['cor' => '#047857', 'bg' => '#d1fae5'],
            self::NIVEL_UP => ['cor' => '#7c3aed', 'bg' => '#ede9fe'],
            self::NOVA_CARTA => ['cor' => '#b45309', 'bg' => '#fef3c7'],
            self::MANUAL => ['cor' => '#1d4ed8', 'bg' => '#dbeafe'],
            self::MISSAO => ['cor' => '#c026d3', 'bg' => '#fae8ff'],
            self::CICLO_CONCLUIDO => ['cor' => '#0f766e', 'bg' => '#ccfbf1'],
        };
    }

    public static function fromValor(string $valor): self
    {
        return self::tryFrom($valor) ?? self::CONQUISTA;
    }
}
