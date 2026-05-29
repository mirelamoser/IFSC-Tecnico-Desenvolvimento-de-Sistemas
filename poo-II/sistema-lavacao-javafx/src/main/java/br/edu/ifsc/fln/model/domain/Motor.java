package br.edu.ifsc.fln.model.domain;

public class Motor {
    private int potencia;
    private ETipoCombustivel tipoCombustivel;//Associação Unidirecional com ETipoCombustivel

    //Construtor
    public Motor(int potencia, ETipoCombustivel tipo) {
        this.potencia = potencia;
        this.tipoCombustivel = tipo;
    }

    //Getters
    public int getPotencia() {
        return potencia;
    }

    public ETipoCombustivel getTipoCombustivel() {
        return tipoCombustivel;
    }

    //Setters
    public void setPotencia(int potencia) {
        this.potencia = potencia;
    }

    public void setTipoCombustivel(ETipoCombustivel tipoCombustivel) {
        this.tipoCombustivel = tipoCombustivel;
    }

    @Override
    public String toString() {
        return "\nPotência do motor " + potencia +
                " e tipo de combustivel: " + tipoCombustivel;
    }
}
