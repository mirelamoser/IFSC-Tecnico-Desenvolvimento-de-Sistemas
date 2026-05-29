<?php
namespace PhiloQuest\Entidades;

class Turma {
    private ?int $id;
    private string $codigoTurma;
    private string $professorMatricula;
    private ?string $criadoEm;

    public function __construct(string $codigoTurma, string $professorMatricula, ?int $id = null, ?string $criadoEm = null) {
        $this->codigoTurma = $codigoTurma;
        $this->professorMatricula = $professorMatricula;
        $this->id = $id;
        $this->criadoEm = $criadoEm;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getCodigoTurma(): string {
        return $this->codigoTurma;
    }

    public function getProfessorMatricula(): string {
        return $this->professorMatricula;
    }

    public function getCriadoEm(): ?string {
        return $this->criadoEm;
    }
}