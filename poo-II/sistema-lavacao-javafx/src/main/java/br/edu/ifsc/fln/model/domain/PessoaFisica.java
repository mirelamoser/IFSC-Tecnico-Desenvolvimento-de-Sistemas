package br.edu.ifsc.fln.model.domain;

import java.time.LocalDate;
import java.time.temporal.WeekFields;
import java.util.Locale;

public class PessoaFisica extends Cliente {
    private String cpf;
    private LocalDate dataNascimento;

    //CONSTRUTOR
    public PessoaFisica() {
    }

    public PessoaFisica(int id, String nome, String celular, String email, LocalDate dataCadastro, String cpf, LocalDate dataNascimento) {
        super(id, nome, celular, email, dataCadastro);
        this.cpf = cpf;
        this.dataNascimento = dataNascimento;
    }

    //GETTERS
    public String getCpf() {
        return cpf;
    }

    public LocalDate getDataNascimento() {
        return dataNascimento;
    }

    //SETTERS
    public void setCpf(String cpf) {
        this.cpf = cpf;
    }

    public void setDataNascimento(LocalDate dataNascimento) {
        this.dataNascimento = dataNascimento;
    }


    //METODO VERIFICAÇÃO ANIVERSÁRIO - compara a semana do ano em que o cliente faz aniversário com a semana atual
    public boolean temDescontoAniversario() {
        LocalDate hoje = LocalDate.now();
        LocalDate aniversarioEsteAno = dataNascimento.withYear(hoje.getYear());

        WeekFields wf = WeekFields.of(Locale.getDefault());//padrão de semana conforme o local
        int semanaAtual = hoje.get(wf.weekOfWeekBasedYear());//descobre a semana do ano em que estamos
        int semanaAniversario = aniversarioEsteAno.get(wf.weekOfWeekBasedYear());//descobre em qual semana do ano o aniversário cai neste ano

        return semanaAtual == semanaAniversario;//Se forem iguais é a semana do aniversário
    }

    //METODO DESCONTO
    public double calcularDesconto() {
        if (temDescontoAniversario()) {
            return 0.20; //20% de desconto
        }
        return 0.0;
    }

    //IMPLEMENTAÇÃO METODOS DA INTERFACE
    @Override
    public String getDados() {
        String dados = super.getDados() +
                "\nCPF: " + cpf +
                "\nData de Nascimento: " + dataNascimento;

        if (temDescontoAniversario()) {
            dados += "\n Semana do aniversário! Desconto aplicado: 20%";
        }
        return dados;
    }

    @Override
    public String getDados(String observacao) {
        String dados = super.getDados(observacao) +
                "\nCPF: " + cpf +
                "\nData de Nascimento: " + dataNascimento;

        if (temDescontoAniversario()) {
            dados += "\n Semana do aniversário! Desconto aplicado: 20%";
        }
        return dados;
    }

    //TO STRING
    @Override
    public String toString() {
        return "Pessoa Fisica " +
                " | CPF: " + cpf +
                " | Data de Nascimento: " + dataNascimento;
    }
}
