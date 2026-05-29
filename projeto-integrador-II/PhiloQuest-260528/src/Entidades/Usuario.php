<?php
namespace PhiloQuest\Entidades;

abstract class Usuario {
    protected string $matricula;
    protected string $nome;
    protected string $senhaHash;
    protected string $tipoUsuario; // Pode ser 'ALUNO', 'PROFESSOR' ou 'ADMIN'

    public function __construct(string $matricula, string $nome, string $senhaHash, string $tipoUsuario) {
        $this->matricula = $matricula;
        $this->nome = $nome;
        $this->senhaHash = $senhaHash;
        $this->tipoUsuario = $tipoUsuario;
    }

    public function getMatricula(): string {
        return $this->matricula;
    }

    public function getNome(): string {
        return $this->nome;
    }

    public function getSenhaHash(): string {
        return $this->senhaHash;
    }

    public function getTipoUsuario(): string {
        return $this->tipoUsuario;
    }
}