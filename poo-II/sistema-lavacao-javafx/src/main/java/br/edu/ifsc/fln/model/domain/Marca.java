package br.edu.ifsc.fln.model.domain;

import java.util.Objects;

public class Marca {
    private int id;
    private String nome;

    //Construtores
    public Marca() {
    }

    public Marca(String nome) {
        this.nome = nome;
    }

    //Getters
    public int getId() {
        return id;
    }

    public String getNome() {
        return nome;
    }

    //Setters
    public void setId(int id) {
        this.id = id;
    }

    public void setNome(String nome) {
        this.nome = nome;
    }

    //Equals and HashCodes
    @Override
    public boolean equals(Object o) {
        if (o == null || getClass() != o.getClass()) return false;
        Marca marca = (Marca) o;
        return id == marca.id;
    }

    @Override
    public int hashCode() {
        return Objects.hashCode(id);
    }

    @Override
    public String toString() {
        return nome;
    }
}
