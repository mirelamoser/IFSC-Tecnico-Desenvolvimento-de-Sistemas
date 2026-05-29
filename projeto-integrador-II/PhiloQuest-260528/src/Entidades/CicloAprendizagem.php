<?php
namespace PhiloQuest\Entidades;

use PhiloQuest\Enum\TipoCiclo;
use PhiloQuest\Enum\StatusCiclo;

class CicloAprendizagem {
    private string $nome;
    private TipoCiclo $tipo;
    private StatusCiclo $status;
    private array $etapas = [];

    public function __construct(string $nome, TipoCiclo $tipo) {
        $this->nome = $nome;
        $this->tipo = $tipo;
        $this->status = StatusCiclo::EM_ANDAMENTO;
    }

    public function adicionarEtapa(Etapa $etapa): void {
        $this->etapas[] = $etapa;
    }
}