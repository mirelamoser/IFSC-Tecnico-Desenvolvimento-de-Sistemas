package br.edu.ifsc.fln.model.domain;

public class ItemOS {
    private double valorServico;
    private String observacoes;
    //ASSOCIAÇÕES - CLASSE ASSOCIATIVA
    private OrdemServico ordemServico;
    private Servico servico;

    //Construtor
    public ItemOS(double valorServico, String observacoes, OrdemServico ordemServico, Servico servico) {
        this.valorServico = valorServico;
        this.observacoes = observacoes;
        this.ordemServico = ordemServico;
        this.servico = servico;
    }

    //Getters
    public double getValorServico() {
        return valorServico;
    }

    public String getObservacoes() {
        return observacoes;
    }

    public OrdemServico getOrdemServico() {
        return ordemServico;
    }

    public Servico getServico() {
        return servico;
    }


    //Setters
    public void setValorServico(double valorServico) {
        this.valorServico = valorServico;
    }

    public void setObservacoes(String observacoes) {
        this.observacoes = observacoes;
    }

    public void setOrdemServico(OrdemServico ordemServico) {
        this.ordemServico = ordemServico;
    }

    public void setServico(Servico servico) {
        this.servico = servico;
    }
}

