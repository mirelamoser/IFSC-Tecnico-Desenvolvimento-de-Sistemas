<?php

declare(strict_types=1);

namespace PhiloQuest\Enum;

enum StatusSubmissao: string {
    case AGUARDANDO_VALIDACAO = 'AGUARDANDO_VALIDACAO';
    case NECESSITA_REVISAO = 'NECESSITA_REVISAO';
    case APROVADO = 'APROVADO';                          // 150 XP
    case APROVADO_BEM_FEITO = 'APROVADO_BEM_FEITO';      // 300 XP
    case APROVADO_EXCELENTE = 'APROVADO_EXCELENTE';      // 500 XP
    
    /**
     * Retorna a quantidade de XP baseado no status da submissão
     */
    public function obterXP(): int {
        return match($this) {
            self::APROVADO => 150,
            self::APROVADO_BEM_FEITO => 300,
            self::APROVADO_EXCELENTE => 500,
            default => 0,
        };
    }
    
    /**
     * Retorna a cor do badge para UI
     */
    public function obterCor(): string {
        return match($this) {
            self::APROVADO => '#27AE60',                // Verde
            self::APROVADO_BEM_FEITO => '#27AE60',      // Verde
            self::APROVADO_EXCELENTE => '#27AE60',      // Verde
            self::NECESSITA_REVISAO => '#F39C12',       // Ouro
            self::AGUARDANDO_VALIDACAO => '#95A5A6',    // Cinza
        };
    }
}