package br.edu.ifsc.fln.model.domain;

import java.time.LocalDate;

public class PessoaJuridica extends Cliente {
    private String cnpj;
    private String inscricaoEstadual;

    //CONSTRUTOR
    public PessoaJuridica() {
    }

    public PessoaJuridica(int id, String nome, String celular, String email, LocalDate dataCadastro, String cnpj, String inscricaoEstadual) {
        super(id, nome, celular, email, dataCadastro);
        this.cnpj = cnpj;
        this.inscricaoEstadual = inscricaoEstadual;
    }

    //GETTERS
    public String getCnpj() {
        return cnpj;
    }

    public String getInscricaoEstadual() {
        return inscricaoEstadual;
    }

    //SETTERS
    public void setCnpj(String cnpj) {
        this.cnpj = cnpj;
    }

    public void setInscricaoEstadual(String inscricaoEstadual) {
        this.inscricaoEstadual = inscricaoEstadual;
    }

    //IMPLEMENTAÇÃO METODOS DA INTERFACE
    @Override
    public String getDados() {
        return super.getDados() +
                "\nCNPJ: " + cnpj +
                "\nInscrição Estadual: " + inscricaoEstadual;
    }

    @Override
    public String getDados(String observacao) {
        return super.getDados(observacao) +
                "\nCNPJ: " + cnpj +
                "\nInscrição Estadual: " + inscricaoEstadual;
    }

    //TO STRING
    @Override
    public String toString() {
        return "Pessoa Juridica" +
                " | CNPJ: " + cnpj +
                " | Inscrição Estadual: " + inscricaoEstadual;
    }
}
