package br.edu.ifsc.fln.model.domain;

public class Pontuacao {
    private int quantidade;

    //CONSTRUTOR
    public Pontuacao() {
        this.quantidade = 0;
    }

    //GETTER
    public int getQuantidade() {
        return quantidade;
    }


    //METODOS DO DIAGRAMA
    public int adicionar(int qtd) {
        if (qtd > 0) { //só adiciona se for positivo
            this.quantidade += qtd;
        }
        return this.quantidade;//nova pontuação
    }

    public int subtrair(int qtd) {
        if (qtd > 0) { //só diminui se for positivo
            this.quantidade -= qtd;
        }

        if (this.quantidade < 0) {
            this.quantidade = 0;
        }
        return this.quantidade;//nova pontuação
    }

    public int saldo() {
        return this.quantidade;
    }


    @Override
    public String toString() {
        return "Pontuacao{" +
                "quantidade=" + quantidade +
                '}';
    }
}
