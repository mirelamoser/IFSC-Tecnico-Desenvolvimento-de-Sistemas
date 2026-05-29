<?php
namespace PhiloQuest\Entidades;

use PhiloQuest\Enum\TipoAtividadeHistorico;
use DateTime;

class HistoricoAtividades {
    private Aluno $aluno;
    private string $descricao;
    private TipoAtividadeHistorico $tipo;
    private DateTime $data_hora;

    public function __construct(Aluno $aluno, string $descricao, TipoAtividadeHistorico $tipo) {
        $this->aluno = $aluno;
        $this->descricao = $descricao;
        $this->tipo = $tipo;
        $this->data_hora = new DateTime();
    }
}