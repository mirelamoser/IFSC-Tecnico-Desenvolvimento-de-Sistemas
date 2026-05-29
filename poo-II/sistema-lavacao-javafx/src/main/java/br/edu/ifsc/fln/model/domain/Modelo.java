package br.edu.ifsc.fln.model.domain;

import java.util.Objects;

public class Modelo {
    private int id;
    private String descricao;
    private Marca marca;// Associação Unidirecional
    private ECategoria categoria;
    private Motor motor;// Associação com Composição

    // Construtores
    public Modelo() {
        this.motor = new Motor(0, ETipoCombustivel.FLEX);
    }

    public Modelo(String descricao, Marca marca) {
        this.descricao = descricao;
        this.marca = marca;
        this.motor = new Motor(0, ETipoCombustivel.FLEX);
    }

    public Modelo(int id, String descricao, Marca marca, ECategoria categoria, int potencia, ETipoCombustivel tipoCombustivel) {
        this.id = id;
        this.descricao = descricao;
        this.marca = marca;
        this.categoria = categoria;
        this.motor = new Motor(potencia, tipoCombustivel);
    }

    // Getters
    public int getId() {
        return id;
    }

    public String getDescricao() {
        return descricao;
    }

    public Marca getMarca() {
        return marca;
    }

    public ECategoria getCategoria() {
        return categoria;
    }

    public Motor getMotor() {
        return motor;
    }

    // Setters
    public void setId(int id) {
        this.id = id;
    }

    public void setDescricao(String descricao) {
        this.descricao = descricao;
    }

    public void setMarca(Marca marca) {
        this.marca = marca;
    }

    public void setCategoria(ECategoria categoria) {
        this.categoria = categoria;
    }

    public void setMotor(Motor motor) {
        this.motor = motor;
    }

    //Equals and HashCodes
    @Override
    public boolean equals(Object o) {
        if (o == null || getClass() != o.getClass()) return false;
        Modelo modelo = (Modelo) o;
        return id == modelo.id;
    }

    @Override
    public int hashCode() {
        return Objects.hashCode(id);
    }

    @Override
    public String toString() {
        return this.descricao;
    }
}
