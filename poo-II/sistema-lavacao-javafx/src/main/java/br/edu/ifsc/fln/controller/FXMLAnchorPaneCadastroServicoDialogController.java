package br.edu.ifsc.fln.controller;

// Importações necessárias das classes de domínio (Model)
import br.edu.ifsc.fln.model.domain.ECategoria;
import br.edu.ifsc.fln.model.domain.Servico;

// Importações dos componentes do JavaFX
import javafx.fxml.FXML;
import javafx.fxml.Initializable;
import javafx.scene.control.Alert;
import javafx.scene.control.Button;
import javafx.scene.control.TextField;
import javafx.stage.Stage;

import java.net.URL;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.ResourceBundle;

/**
 * Classe Controladora (Controller) da tela de diálogo de Serviços.
 * Responsável por fazer a ponte entre a View (FXML) e as regras de negócio do Model.
 * * ATUALIZAÇÃO: Adaptada para gerenciar múltiplos valores por categoria simultaneamente
 * mantendo compatibilidade total de nomenclatura com as demais entidades.
 * * @author mpisc (Modificado pelo Assistente)
 */
public class FXMLAnchorPaneCadastroServicoDialogController implements Initializable {

    // --- Componentes Visuais Injetados do arquivo FXML ---
    @FXML
    private Button btCancelar;

    @FXML
    private Button btConfirmar;

    @FXML
    private TextField tfDescricao;

    // ATUALIZAÇÃO: Substituição do tfValor único pelos 5 campos específicos da nova UI
    @FXML
    private TextField tfValorPequeno;

    @FXML
    private TextField tfValorMedio;

    @FXML
    private TextField tfValorGrande;

    @FXML
    private TextField tfValorMoto;

    @FXML
    private TextField tfValorPadrao;

    // --- Atributos de Controle da Janela ---
    private Stage dialogStage; // Guarda a janela (palco) atual para permitir fechá-la por código
    private boolean btConfirmarClicked = false; // Flag para rastrear se o usuário salvou ou cancelou
    private Servico servico; // O objeto de modelo principal que está sendo criado ou editado

    // Coleção interna para gerenciar os 5 serviços correspondentes a cada categoria na mesma tela
    private Map<ECategoria, Servico> mapaServicos = new HashMap<>();

    /**
     * Método de Inicialização (Interface Initializable).
     * Executado automaticamente assim que o arquivo FXML termina de ser carregado.
     */
    @Override
    public void initialize(URL url, ResourceBundle rb) {
        // O ComboBox foi removido da interface, portanto este método permanece limpo.
    }

    // --- Métodos Getters e Setters de Controle de Fluxo (Mantidos idênticos) ---

    public boolean isBtConfirmarClicked() {
        return btConfirmarClicked;
    }

    public void setBtConfirmarClicked(boolean btConfirmarClicked) {
        this.btConfirmarClicked = btConfirmarClicked;
    }

    public Stage getDialogStage() {
        return dialogStage;
    }

    public void setDialogStage(Stage dialogStage) {
        this.dialogStage = dialogStage;
    }

    public Servico getServico() {
        return servico;
    }

    /**
     * Método fundamental que recebe o objeto Serviço vindo da tela principal.
     * Preservado sem alterar a assinatura para não quebrar o vínculo com outras telas.
     */
    public void setServico(Servico servico) {
        this.servico = servico;
        this.mapaServicos.clear();

        // Se o ID for zero, é uma inclusão nova
        if (servico.getId() == 0) {
            this.tfDescricao.setText("");

            // Instancia antecipadamente os 5 serviços (um para cada categoria do Enum)
            for (ECategoria cat : ECategoria.values()) {
                Servico s = new Servico();
                s.setCategoria(cat);
                mapaServicos.put(cat, s);
            }
        } else {
            // Operação de Alteração: O registro já existe no banco.
            this.tfDescricao.setText(servico.getDescricao());

            // O 'servico' recebido preenche a sua própria categoria nativa no mapa
            mapaServicos.put(servico.getCategoria(), servico);

            /* * DICA ARQUITETURAL: Como a tela agora exibe 5 valores, ao editar, o ideal é carregar
             * os outros 4 valores que compartilham a mesma descrição no banco de dados.
             * Você pode descomentar e adaptar a lógica do seu DAO abaixo se necessário:
             * * ServicoDAO servicoDAO = new ServicoDAO();
             * List<Servico> listaIrmaos = servicoDAO.buscarPorDescricao(servico.getDescricao());
             * for(Servico s : listaIrmaos) {
             * mapaServicos.put(s.getCategoria(), s);
             * }
             */

            // Alimenta os text fields com os valores atualmente carregados no mapa (se existirem)
            if (mapaServicos.get(ECategoria.PEQUENO) != null) {
                tfValorPequeno.setText(String.valueOf(mapaServicos.get(ECategoria.PEQUENO).getValor()));
            }
            if (mapaServicos.get(ECategoria.MEDIO) != null) {
                tfValorMedio.setText(String.valueOf(mapaServicos.get(ECategoria.MEDIO).getValor()));
            }
            if (mapaServicos.get(ECategoria.GRANDE) != null) {
                tfValorGrande.setText(String.valueOf(mapaServicos.get(ECategoria.GRANDE).getValor()));
            }
            if (mapaServicos.get(ECategoria.MOTO) != null) {
                tfValorMoto.setText(String.valueOf(mapaServicos.get(ECategoria.MOTO).getValor()));
            }
            if (mapaServicos.get(ECategoria.PADRAO) != null) {
                tfValorPadrao.setText(String.valueOf(mapaServicos.get(ECategoria.PADRAO).getValor()));
            }
        }
    }

    /**
     * Retorna a lista completa com os 5 objetos estruturados para o controller pai,
     * permitindo salvar todos em lote sem quebrar a tipagem estrutural do sistema.
     */
    public List<Servico> getListaServicos() {
        return new ArrayList<>(this.mapaServicos.values());
    }

    /**
     * Evento acionado ao clicar no botão Confirmar.
     * Valida os campos, atualiza as instâncias internas e fecha a janela.
     */
    @FXML
    public void handleBtConfirmar() {
        // Só avança se todas as regras de validação visual forem obedecidas
        if (validarEntradaDeDados()) {

            String descricaoInformada = tfDescricao.getText();

            // Sincroniza e atualiza os dados de todos os 5 serviços mapeados de acordo com os inputs
            atualizarObjetoServico(ECategoria.PEQUENO, tfValorPequeno, descricaoInformada);
            atualizarObjetoServico(ECategoria.MEDIO, tfValorMedio, descricaoInformada);
            atualizarObjetoServico(ECategoria.GRANDE, tfValorGrande, descricaoInformada);
            atualizarObjetoServico(ECategoria.MOTO, tfValorMoto, descricaoInformada);
            atualizarObjetoServico(ECategoria.PADRAO, tfValorPadrao, descricaoInformada);

            // Garante que a referência do objeto principal 'servico' não fique nula e retroalimente o fluxo padrão
            if (this.servico == null || this.servico.getCategoria() == null) {
                this.servico = mapaServicos.get(ECategoria.PADRAO); // Define a categoria PADRAO como fallback principal
            }

            // Define que a operação foi confirmada com sucesso pelo usuário
            btConfirmarClicked = true;

            // Fecha a janela pop-up
            dialogStage.close();
        }
    }

    /**
     * Evento acionado ao clicar no botão Cancelar.
     * Fecha a janela descartando qualquer alteração feita nos campos.
     */
    @FXML
    public void handleBtCancelar() {
        dialogStage.close();
    }

    /**
     * Método auxiliar interno para encapsular a inserção de valores e descrições nos objetos do mapa.
     */
    private void atualizarObjetoServico(ECategoria categoria, TextField campoValor, String descricao) {
        Servico s = mapaServicos.get(categoria);
        if (s == null) {
            s = new Servico();
            s.setCategoria(categoria);
            mapaServicos.put(categoria, s);
        }
        s.setDescricao(descricao);
        s.setValor(Double.parseDouble(campoValor.getText()));
    }

    /**
     * Método privado encarregado de validar se o usuário preencheu a tela corretamente.
     * Impede falhas de execução de forma idêntica ao padrão original das suas views.
     * * @return true se os dados forem válidos; false caso haja erros.
     */
    private boolean validarEntradaDeDados() {
        String errorMessage = ""; // Acumulador de mensagens de erro

        // 1. Validação do campo Descrição
        if (this.tfDescricao.getText() == null || this.tfDescricao.getText().trim().isEmpty()) {
            errorMessage += "Descrição inválida.\nVocê deve preencher a descrição do serviço.\n";
        }

        // 2. Validação individual de cada um dos 5 novos campos de preço da interface
        errorMessage += validarCampoPreco(tfValorPequeno, "Pequeno");
        errorMessage += validarCampoPreco(tfValorMedio, "Médio");
        errorMessage += validarCampoPreco(tfValorGrande, "Grande");
        errorMessage += validarCampoPreco(tfValorMoto, "Moto");
        errorMessage += validarCampoPreco(tfValorPadrao, "Padrão");

        // Se a string de erro continuar vazia, significa que passou em todos os testes de validação
        if (errorMessage.isEmpty()) {
            return true;
        } else {
            // Se houver algum erro acumulado, monta uma caixa de alerta na tela informando o usuário
            Alert alert = new Alert(Alert.AlertType.ERROR);
            alert.setTitle("Erro no cadastro");
            alert.setHeaderText("Corrija os campos inválidos!");
            alert.setContentText(errorMessage);
            alert.show(); // Exibe o alerta visual de erro
            return false;
        }
    }

    /**
     * Método auxiliar de validação para evitar repetição massiva de blocos try-catch.
     */
    private String validarCampoPreco(TextField campo, String nomeCategoria) {
        if (campo.getText() == null || campo.getText().trim().isEmpty()) {
            return "Valor inválido para a categoria [" + nomeCategoria + "].\nO campo não pode estar em branco.\n";
        } else {
            try {
                Double.parseDouble(campo.getText());
            } catch (NumberFormatException e) {
                return "O valor para [" + nomeCategoria + "] deve ser um número válido (ex: 50.0 ou 120.50).\n";
            }
        }
        return "";
    }
}