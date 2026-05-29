<?php
namespace PhiloQuest\Entidades;

use PhiloQuest\Enum\StatusSubmissao;
use DateTime;

class Professor extends Usuario {
    public function __construct(string $matricula, string $nome, string $senha_hash) {
        parent::__construct($matricula, $nome, $senha_hash);
    }

    public function criarMissao(string $titulo, string $descricao, int $xp_recompensa, DateTime $data_limite): Missao {
        return new Missao($titulo, $descricao, $xp_recompensa, $data_limite, $this);
    }

    public function validarEtapa(SubmissaoEtapa $submissao, StatusSubmissao $novo_status): void {
        $submissao->atualizarStatus($novo_status);
    }
}