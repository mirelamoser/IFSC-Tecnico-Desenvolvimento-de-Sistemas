package br.edu.ifsc.fln.model.domain;

import java.time.LocalDate;
import java.util.ArrayList;
import java.util.List;

public abstract class Cliente implements IDados {
    protected int id;
    protected String nome;
    protected String celular;
    protected String email;
    protected LocalDate dataCadastro;
    private List<Veiculo> veiculos;//Associação Bidirecional com Cliente - 1 cliente possui 0 ou vários veículos
    private Pontuacao pontuacao;   //Associação com Composição

    //Construtor
    public Cliente() {
        this.pontuacao = new Pontuacao();
        this.veiculos = new ArrayList<>();
    }

    public Cliente(int id, String nome, String celular, String email, LocalDate dataCadastro) {
        this.id = id;
        this.nome = nome;
        this.celular = celular;
        this.email = email;
        this.dataCadastro = dataCadastro;
        this.veiculos = new ArrayList<>();
        this.pontuacao = new Pontuacao();
    }

    //Getters
    public int getId() {
        return id;
    }

    public String getNome() {
        return nome;
    }

    public String getCelular() {
        return celular;
    }

    public String getEmail() {
        return email;
    }

    public LocalDate getDataCadastro() {
        return dataCadastro;
    }

    public List<Veiculo> getVeiculos() {
        return veiculos;
    }

    public Pontuacao getPontuacao() {
        return pontuacao;
    }

    //Setters
    public void setId(int id) {
        this.id = id;
    }

    public void setNome(String nome) {
        this.nome = nome;
    }

    public void setCelular(String celular) {
        this.celular = celular;
    }

    public void setEmail(String email) {
        this.email = email;
    }

    public void setDataCadastro(LocalDate dataCadastro) {
        this.dataCadastro = dataCadastro;
    }

    public void setVeiculos(List<Veiculo> veiculos) {
        this.veiculos = veiculos;
    }

    //ADICIONAR
    public void adicionarVeiculo(Veiculo veiculo) {
        this.veiculos.add(veiculo);
        veiculo.setCliente(this);
    }

    //REMOVER
    public void removerVeiculo(Veiculo veiculo) {
        this.veiculos.remove(veiculo);
        veiculo.setCliente(null);//quando remove o veiculo ele não pertence a mais nenhum cliente
    }

    //IMPLEMENTAÇÃO METODOS DA INTERFACE
    @Override
    public String getDados() {
        return "Nome: " + nome + '\n' +
                "Celular: " + celular + '\n' +
                "Email: " + email + '\n' +
                "Data Cadastro: " + dataCadastro;
    }

    @Override
    public String getDados(String observacao) {
        return this.getDados() + "\nObservação: " + observacao;
    }

    //TO STRING
    @Override
    public String toString() {
        return "id: " + id + '\n' +
                "Nome: " + nome + '\n' +
                "Celular: " + celular + '\n' +
                "Email: " + email + '\n' +
                "Data de Cadastro: " + dataCadastro + '\n' +
                "Veículo: " + getVeiculos().size() + " veículo(s) cadastrado(s).";
    }
}
