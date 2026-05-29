<?php
namespace PhiloQuest\Entidades;

class ProgressoCiclo {
    private Aluno $aluno;
    private CicloAprendizagem $ciclo;
    private int $etapa_atual;
    private bool $concluido;

    public function __construct(Aluno $aluno, CicloAprendizagem $ciclo) {
        $this->aluno = $aluno;
        $this->ciclo = $ciclo;
        $this->etapa_atual = 1;
        $this->concluido = false;
    }
}