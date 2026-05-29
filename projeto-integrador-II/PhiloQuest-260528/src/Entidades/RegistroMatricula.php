<?php
namespace PhiloQuest\Entidades;

use PhiloQuest\Enum\StatusMatricula;

class RegistroMatricula {
    private string $codigo_matricula;
    private StatusMatricula $status;

    public function __construct(string $codigo_matricula) {
        $this->codigo_matricula = $codigo_matricula;
        $this->status = StatusMatricula::DISPONIVEL;
    }

    public function validarMatricula(): bool {
        return $this->status === StatusMatricula::DISPONIVEL;
    }
}