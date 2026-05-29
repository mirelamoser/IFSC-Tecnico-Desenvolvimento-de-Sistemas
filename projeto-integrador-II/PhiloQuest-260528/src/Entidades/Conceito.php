<?php
namespace PhiloQuest\Entidades;

class Conceito {
    private string $nome;
    private string $descricao;
    private Filosofo $filosofo_relacionado;

    public function __construct(string $nome, string $descricao, Filosofo $filosofo) {
        $this->nome = $nome;
        $this->descricao = $descricao;
        $this->filosofo_relacionado = $filosofo;
    }
}