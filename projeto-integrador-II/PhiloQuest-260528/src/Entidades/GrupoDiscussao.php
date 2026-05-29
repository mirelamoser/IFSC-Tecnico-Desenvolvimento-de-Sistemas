<?php
namespace PhiloQuest\Entidades;

class GrupoDiscussao {
    private string $tema;
    private array $participantes = [];
    private array $mensagens = [];

    public function __construct(string $tema) {
        $this->tema = $tema;
    }

    public function entrarNoGrupo(Aluno $aluno): void {
        $this->participantes[] = $aluno;
    }
}