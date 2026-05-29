<?php
namespace PhiloQuest\Entidades;

use PhiloQuest\Enum\RaridadeFilosofo;

class CartaConceitual {
    public function __construct(
        private string $nome_filosofo,
        private string $citacao,
        private RaridadeFilosofo $raridade
    ) {}

    public function getNomeFilosofo(): string { return $this->nome_filosofo; }
    public function getCitacao(): string { return $this->citacao; }
    public function getRaridade(): RaridadeFilosofo { return $this->raridade; }
}