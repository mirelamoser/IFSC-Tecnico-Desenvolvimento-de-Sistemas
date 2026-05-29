package br.edu.ifsc.fln.model.domain;

public enum ECategoria {
    PEQUENO("Pequeno"), MEDIO("Médio"), GRANDE("Grande"), MOTO("Moto"), PADRAO("Padrão");

    private String descricao;

    //Construtor
    private ECategoria(String descricao) {
        this.descricao = descricao;
    }

    //Getters
    public String getDescricao() {
        return descricao;
    }

    @Override
    public String toString() {
        return this.descricao;
    }
}
