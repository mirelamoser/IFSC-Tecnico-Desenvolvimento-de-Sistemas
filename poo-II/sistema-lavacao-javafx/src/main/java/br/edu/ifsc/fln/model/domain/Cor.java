package br.edu.ifsc.fln.model.domain;

import java.util.Objects;

public class Cor {
    private int id;
    private String nome;

    public Cor() {
    }

    public Cor(int id, String nome) {
        this.id = id;
        this.nome = nome;
    }

    public int getId() {
        return id;
    }

    public String getNome() {
        return nome;
    }

    public void setId(int id) {
        this.id = id;
    }

    public void setNome(String nome) {
        this.nome = nome;
    }

    @Override
    public boolean equals(Object o) {
        if (o == null || getClass() != o.getClass()) return false;
        Cor cor = (Cor) o;
        return getId() == cor.getId();
    }

    @Override
    public int hashCode() {
        return Objects.hashCode(getId());
    }

    @Override
    public String toString() {
        return nome;
    }
}
