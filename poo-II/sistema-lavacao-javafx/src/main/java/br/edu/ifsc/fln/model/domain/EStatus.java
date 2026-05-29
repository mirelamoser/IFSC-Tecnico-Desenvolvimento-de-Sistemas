package br.edu.ifsc.fln.model.domain;

public enum EStatus {
    ABERTA("Aberta"), FECHADA("Fechada"), CANCELADA("Cancelada");

    private String descricao;

    //Construtor
    EStatus(String descricao) {
        this.descricao = descricao;
    }

    //Getter
    public String getDescricao() {
        return descricao;
    }

    //Setter
    public void setDescricao(String descricao) {
        this.descricao = descricao;
    }

    @Override
    public String toString() {
        return this.descricao;
    }
}

