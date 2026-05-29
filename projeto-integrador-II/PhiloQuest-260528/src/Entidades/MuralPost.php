<?php
namespace PhiloQuest\Entidades;

use PhiloQuest\Enum\TipoPostMural;
use DateTime;

class MuralPost {
    private Usuario $autor;
    private string $conteudo;
    private TipoPostMural $tipo;
    private DateTime $data_publicacao;

    public function __construct(Usuario $autor, string $conteudo, TipoPostMural $tipo) {
        $this->autor = $autor;
        $this->conteudo = $conteudo;
        $this->tipo = $tipo;
        $this->data_publicacao = new DateTime();
    }
}