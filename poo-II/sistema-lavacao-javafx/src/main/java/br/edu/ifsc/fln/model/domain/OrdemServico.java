package br.edu.ifsc.fln.model.domain;

import java.time.LocalDate;
import java.util.ArrayList;
import java.util.List;

public class OrdemServico {
    private long numero;
    private double total;
    private LocalDate agenda;
    private double desconto;
    private List<ItemOS> itemOSList; // ASSOCIAÇÃO CLASSE (1 O.S. TEM VÁRIOS ITENS)
    private Veiculo veiculo;
    private EStatus status;

    // Construtor
    public OrdemServico(long numero, LocalDate agenda, double desconto, Veiculo veiculo, EStatus status) {
        this.numero = numero;
        this.agenda = agenda;
        this.desconto = desconto;
        this.veiculo = veiculo;
        this.status = status;
        this.total = 0.0; // Começa zerado
        this.itemOSList = new ArrayList<>(); // Inicializada lista vazia
    }

    // Getters
    public long getNumero() {
        return numero;
    }

    public double getTotal() {
        return total;
    }

    public LocalDate getAgenda() {
        return agenda;
    }

    public double getDesconto() {
        return desconto;
    }

    public List<ItemOS> getItemOSList() {
        return itemOSList;
    }

    public Veiculo getVeiculo() {
        return veiculo;
    }

    public EStatus getStatus() {
        return status;
    }

    // Setters
    public void setNumero(long numero) {
        this.numero = numero;
    }

    public void setTotal(double total) {
        this.total = total;
    }

    public void setAgenda(LocalDate agenda) {
        this.agenda = agenda;
    }

    public void setDesconto(double desconto) {
        this.desconto = desconto;
    }

    public void setVeiculo(Veiculo veiculo) {
        this.veiculo = veiculo;
    }

    public void setStatus(EStatus status) {
        this.status = status;
    }

    // MeTODO - CALCULAR SERVIÇO
    // COMENTADO: "throws ExceptionLavacao" removido temporariamente para o sistema compilar
    public double calcularServico() {

        // COMENTADO: Validação com a exceção comentada até a próxima aula do professor
        /*
        if (this.itemOSList.isEmpty()) {
            throw new ExceptionLavacao("não há serviços na lista para serem calculados");
        }
        */

        // Percorre a lista e calcula normalmente
        double somaItens = 0;
        for (ItemOS item : itemOSList) {
            somaItens = somaItens + item.getValorServico();
        }

        // Aplica o desconto
        this.total = somaItens - (somaItens * this.desconto);
        return this.total;
    }

    // MÉTODOS - ADICIONAR E REMOVER
    public void addItemOS(ItemOS item) {
        this.itemOSList.add(item);
    }

    public void removeItemOS(ItemOS item) {
        this.itemOSList.remove(item);
    }
}