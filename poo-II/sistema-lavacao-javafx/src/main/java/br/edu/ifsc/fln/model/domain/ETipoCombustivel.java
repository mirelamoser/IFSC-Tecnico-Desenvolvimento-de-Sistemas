package br.edu.ifsc.fln.model.domain;

public enum ETipoCombustivel {
    GASOLINA("Gasolina"), ETANOL("Etanol"), FLEX("Flex"), DIESEL("Diesel"), GNV("Gnv"), OUTRO("Outro");
    private String descricao;

    //Construtor
    private ETipoCombustivel(String descricao) {
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
