<?php
namespace PhiloQuest\Entidades;

use PhiloQuest\Enum\StatusSubmissao;
use DateTime;

class SubmissaoEtapa {
    private StatusSubmissao $status;
    private DateTime $data_envio;

    public function __construct(
        private Aluno $aluno, 
        private string $conteudo
    ) {
        $this->status = StatusSubmissao::AGUARDANDO_VALIDACAO;
        $this->data_envio = new DateTime();
    }

    public function atualizarStatus(StatusSubmissao $novo_status): void {
        $this->status = $novo_status;
    }

    public function getStatus(): StatusSubmissao { return $this->status; }
    public function getAluno(): Aluno { return $this->aluno; }
    public function getConteudo(): string { return $this->conteudo; }
    public function getDataEnvio(): DateTime { return $this->data_envio; }
}