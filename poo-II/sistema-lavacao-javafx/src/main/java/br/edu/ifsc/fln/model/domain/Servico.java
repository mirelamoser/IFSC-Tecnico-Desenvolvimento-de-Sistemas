package br.edu.ifsc.fln.model.domain;

import java.util.Objects;

public class Servico {
    private int id;
    private String descricao;
    private double valor;
    private static int pontos;
    private ECategoria categoria;

    //Construtor
    public Servico() {
    }

    public Servico(int id, String descricao, double valor, ECategoria categoria) {
        this.id = id;
        this.descricao = descricao;
        this.valor = valor;
        this.categoria = categoria;
    }

    //Getters
    public int getId() {
        return id;
    }

    public String getDescricao() {
        return descricao;
    }

    public double getValor() {
        return valor;
    }

    public static int getPontos() {
        return pontos;
    }

    public ECategoria getCategoria() {
        return categoria;
    }

    //Setters
    public void setId(int id) {
        this.id = id;
    }

    public void setDescricao(String descricao) {
        this.descricao = descricao;
    }

    public void setValor(double valor) {
        this.valor = valor;
    }

    public static void setPontos(int pontos) {
        Servico.pontos = pontos;
    }

    public void setCategoria(ECategoria categoria) {
        this.categoria = categoria;
    }

    //Equals and HashCodes
    @Override
    public boolean equals(Object o) {
        if (o == null || getClass() != o.getClass()) return false;
        Servico servico = (Servico) o;
        return id == servico.id;
    }

    @Override
    public int hashCode() {
        return Objects.hashCode(id);
    }

    @Override
    public String toString() {
        return descricao;
    }
}
