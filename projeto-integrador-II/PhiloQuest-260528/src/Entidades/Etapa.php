<?php
namespace PhiloQuest\Entidades;

class Etapa {
    private string $titulo;
    private string $conteudo_estudo;
    private string $pergunta_desafio;

    public function __construct(string $titulo, string $conteudo, string $pergunta) {
        $this->titulo = $titulo;
        $this->conteudo_estudo = $conteudo;
        $this->pergunta_desafio = $pergunta;
    }
}