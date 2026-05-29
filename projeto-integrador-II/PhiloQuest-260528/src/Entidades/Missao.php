<?php
namespace PhiloQuest\Entidades;

use DateTime;

class Missao {
    public function __construct(
        private string $titulo,
        private string $descricao,
        private int $xp_recompensa,
        private DateTime $data_limite,
        private Professor $criador
    ) {}

    public function getTitulo(): string { return $this->titulo; }
    public function getDescricao(): string { return $this->descricao; }
    public function getXpRecompensa(): int { return $this->xp_recompensa; }
    public function getDataLimite(): DateTime { return $this->data_limite; }
    public function getCriador(): Professor { return $this->criador; }
}