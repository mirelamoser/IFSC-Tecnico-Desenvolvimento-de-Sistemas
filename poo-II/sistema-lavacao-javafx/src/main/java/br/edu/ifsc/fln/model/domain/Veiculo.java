package br.edu.ifsc.fln.model.domain;

import java.util.Objects;

public class Veiculo implements IDados {
    private int id;
    private String placa;
    private String observacoes;
    private Cor cor;        // Associação Unidirecional com Cor
    private Modelo modelo;  // Associação Agregação com Modelo
    private Cliente cliente;// Associação Bidirecional com Cliente

    // Construtores
    public Veiculo() {
    }

    public Veiculo(String placa) {
        this.placa = placa;
    }

    public Veiculo(String placa, Modelo modelo) {
        this.placa = placa;
        this.modelo = modelo;
    }

    // Construtor completo
    public Veiculo(int id, String placa, String observacoes, Cor cor, Modelo modelo, Cliente cliente) {
        this.id = id;
        this.placa = placa;
        this.observacoes = observacoes;
        this.cor = cor;
        this.modelo = modelo;
        this.cliente = cliente;
    }

    //Getters
    public int getId() {
        return id;
    }

    public String getPlaca() {
        return placa;
    }

    public String getObservacoes() {
        return observacoes;
    }

    public Cor getCor() {
        return cor;
    }

    public Modelo getModelo() {
        return modelo;
    }

    public Cliente getCliente() {
        return cliente;
    }


    //Setters
    public void setId(int id) {
        this.id = id;
    }

    public void setPlaca(String placa) {
        this.placa = placa;
    }

    public void setObservacoes(String observacoes) {
        this.observacoes = observacoes;
    }

    public void setCor(Cor cor) {
        this.cor = cor;
    }

    public void setModelo(Modelo modelo) {
        this.modelo = modelo;
    }

    public void setCliente(Cliente cliente) {
        this.cliente = cliente;
    }


    //Equals and HashCodes
    @Override
    public boolean equals(Object o) {
        if (o == null || getClass() != o.getClass()) return false;
        Veiculo veiculo = (Veiculo) o;
        return id == veiculo.id;
    }

    @Override
    public int hashCode() {
        return Objects.hashCode(id);
    }


    // IMPLEMENTAÇÃO MÉTODOS DA INTERFACE IDADOS (Comentados por causa das dependências)
    public String getDados() {
        return "\nPlaca: " + this.getPlaca() +
                "\nModelo: " + this.getModelo().getDescricao() +
                "\nMarca: " + this.getModelo().getMarca().getNome() +
                "\nCategoria: " + this.getModelo().getCategoria() +
                "\nPotência: " + this.getModelo().getMotor().getPotencia() + " cv" +
                "\nTipo de Combustível: " + this.getModelo().getMotor().getTipoCombustivel().getDescricao() +
                "\nCliente: " + (cliente != null ? cliente.getNome() : "N/A");
    }

    public String getDados(String observacao) {
        return this.getDados() + "\nObservação: " + observacao;
    }


    @Override
    public String toString() {
        return "ID: " + id + "\n" +
                "Cliente: " + cliente.getNome() + "\n" +
                "Placa: " + placa + "\n" +
                "Modelo: " + modelo.getDescricao() + " (" + modelo.getMarca().getNome() + ")\n" +
                "Cor: " + cor.getNome() + "\n" +
                "Categoria: " + modelo.getCategoria().getDescricao() + "\n" +
                "Tipo de Combustível: " + modelo.getMotor().getTipoCombustivel().getDescricao() + "\n" +
                "Potência: " + modelo.getMotor().getPotencia() + " cv" + "\n" +
                "Observações: " + observacoes;
    }
}
