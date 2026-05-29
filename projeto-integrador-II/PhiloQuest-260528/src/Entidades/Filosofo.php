<?php
namespace PhiloQuest\Entidades;

use PhiloQuest\Enum\EpocaFilosofo;
use PhiloQuest\Enum\RaridadeFilosofo;

class Filosofo {
    private string $nome;
    private EpocaFilosofo $epoca;
    private RaridadeFilosofo $raridade;
    private string $biografia;

    public function __construct(string $nome, EpocaFilosofo $epoca, RaridadeFilosofo $raridade, string $biografia) {
        $this->nome = $nome;
        $this->epoca = $epoca;
        $this->raridade = $raridade;
        $this->biografia = $biografia;
    }
}