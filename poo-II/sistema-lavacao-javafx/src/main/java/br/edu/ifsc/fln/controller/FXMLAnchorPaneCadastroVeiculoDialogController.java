package br.edu.ifsc.fln.controller;

import br.edu.ifsc.fln.model.dao.ClienteDAO;
import br.edu.ifsc.fln.model.dao.CorDAO;
import br.edu.ifsc.fln.model.dao.MarcaDAO;
import br.edu.ifsc.fln.model.dao.ModeloDAO;
import br.edu.ifsc.fln.model.dao.VeiculoDAO;
import br.edu.ifsc.fln.model.database.Database;
import br.edu.ifsc.fln.model.database.DatabaseFactory;
import br.edu.ifsc.fln.model.domain.Cliente;
import br.edu.ifsc.fln.model.domain.Cor;
import br.edu.ifsc.fln.model.domain.Marca;
import br.edu.ifsc.fln.model.domain.Modelo;
import br.edu.ifsc.fln.model.domain.Veiculo;
import java.net.URL;
import java.sql.Connection;
import java.util.ArrayList;
import java.util.List;
import java.util.ResourceBundle;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.fxml.Initializable;
import javafx.scene.control.Alert;
import javafx.scene.control.Button;
import javafx.scene.control.ComboBox;
import javafx.scene.control.TextField;
import javafx.stage.Stage;
import javafx.util.StringConverter;

/**
 * Classe Controladora da janela pop-up de inserção e edição de Veículos.
 */
public class FXMLAnchorPaneCadastroVeiculoDialogController implements Initializable {

    @FXML
    private ComboBox<Cliente> comboBoxCliente;
    @FXML
    private TextField textFieldPlaca;
    @FXML
    private TextField textFieldObservacoes;

    // CORREÇÃO: Novo ComboBox de Marca injetado do FXML
    @FXML
    private ComboBox<Marca> comboBoxMarca;
    @FXML
    private ComboBox<Modelo> comboBoxModelo;
    @FXML
    private ComboBox<Cor> comboBoxCor;
    @FXML
    private Button buttonConfirmar;
    @FXML
    private Button buttonCancelar;

    private Stage dialogStage;
    private boolean buttonConfirmarClicked = false;
    private Veiculo veiculo;

    // Gerenciadores de conexões e objetos DAO de apoio
    private final Database database = DatabaseFactory.getDatabase("mysql");
    private final Connection connection = database.conectar();
    private final ModeloDAO modeloDAO = new ModeloDAO();
    private final CorDAO corDAO = new CorDAO();
    private final ClienteDAO clienteDAO = new ClienteDAO();
    private final MarcaDAO marcaDAO = new MarcaDAO(); // Novo DAO acoplado
    private final VeiculoDAO veiculoDAO = new VeiculoDAO(); // Novo DAO acoplado para validação

    @Override
    public void initialize(URL url, ResourceBundle rb) {
        // Injeta a conexão ativa compartilhada em todas as camadas de persistência desta tela
        modeloDAO.setConnection(connection);
        corDAO.setConnection(connection);
        clienteDAO.setConnection(connection);
        marcaDAO.setConnection(connection);
        veiculoDAO.setConnection(connection);

        carregarComboBoxMarcas(); // Carrega as opções iniciais de Marca
        carregarComboBoxCores();
        carregarComboBoxClientes();

        // Bloqueia o ComboBox de Modelo até que o usuário tome a decisão de escolher uma Marca
        comboBoxModelo.setDisable(true);

        // FLUXO CASCATA: Listener monitora qual Marca foi selecionada pelo usuário
        comboBoxMarca.getSelectionModel().selectedItemProperty().addListener(
                (observable, oldValue, newValue) -> {
                    if (newValue != null) {
                        // Se houver uma marca válida selecionada, filtra os modelos dela e destrava o combo
                        carregarComboBoxModelosPorMarca(newValue);
                        comboBoxModelo.setDisable(false);
                    } else {
                        // Limpa o combo caso a seleção da marca seja anulada
                        comboBoxModelo.setItems(FXCollections.observableArrayList());
                        comboBoxModelo.setDisable(true);
                    }
                }
        );
    }

    /**
     * Busca e insere todas as marcas cadastradas no ComboBox correspondente.
     */
    private void carregarComboBoxMarcas() {
        List<Marca> listMarcas = marcaDAO.listar();
        comboBoxMarca.setItems(FXCollections.observableArrayList(listMarcas));
    }

    /**
     * CORREÇÃO: Aplica um filtro em tempo de execução para exibir somente
     * os modelos que fazem parte da Marca selecionada pelo usuário.
     */
    private void carregarComboBoxModelosPorMarca(Marca marca) {
        List<Modelo> listTodosModelos = modeloDAO.listar();
        List<Modelo> listModelosFiltrados = new ArrayList<>();

        for (Modelo m : listTodosModelos) {
            if (m.getMarca().getId() == marca.getId()) {
                listModelosFiltrados.add(m); // Filtra os modelos compatíveis com o ID da Marca
            }
        }
        comboBoxModelo.setItems(FXCollections.observableArrayList(listModelosFiltrados));
    }

    private void carregarComboBoxCores() {
        List<Cor> listCores = corDAO.listar();
        comboBoxCor.setItems(FXCollections.observableArrayList(listCores));
    }

    private void carregarComboBoxClientes() {
        List<Cliente> listClientes = clienteDAO.listar();
        comboBoxCliente.setItems(FXCollections.observableArrayList(listClientes));
        comboBoxCliente.setConverter(new StringConverter<Cliente>() {
            @Override
            public String toString(Cliente cliente) {
                return cliente == null ? "" : cliente.getNome();
            }
            @Override
            public Cliente fromString(String string) { return null; }
        });
    }

    public Stage getDialogStage() { return dialogStage; }
    public void setDialogStage(Stage dialogStage) { this.dialogStage = dialogStage; }
    public boolean isButtonConfirmarClicked() { return buttonConfirmarClicked; }

    /**
     * Recebe o objeto Veículo vindo da tela principal (carrega dados se for Edição).
     */
    public void setVeiculo(Veiculo veiculo) {
        this.veiculo = veiculo;
        this.textFieldPlaca.setText(veiculo.getPlaca());
        this.textFieldObservacoes.setText(veiculo.getObservacoes());

        // Lógica de restrição se a janela for aberta a partir do contexto de um Cliente específico
        if (veiculo.getCliente() != null) {
            for (Cliente cliente : comboBoxCliente.getItems()) {
                if (cliente.getId() == veiculo.getCliente().getId()) {
                    comboBoxCliente.getSelectionModel().select(cliente);
                    break;
                }
            }
            comboBoxCliente.setDisable(true); // Trava seleção do dono
        } else {
            comboBoxCliente.setDisable(false);
        }

        // FLUXO DE EDIÇÃO: Se o veículo já possui modelo cadastrado
        if (veiculo.getModelo() != null) {
            // Trava o campo placa na edição para manter a rastreabilidade segura dos dados
            textFieldPlaca.setEditable(false);

            // 1. Encontra e pré-seleciona a Marca dona daquele modelo
            for (Marca marca : comboBoxMarca.getItems()) {
                if (marca.getId() == veiculo.getModelo().getMarca().getId()) {
                    comboBoxMarca.getSelectionModel().select(marca);
                    break;
                }
            }
            // 2. Encontra e pré-seleciona o Modelo correto (que agora já está carregado pelo Listener)
            for (Modelo modelo : comboBoxModelo.getItems()) {
                if (modelo.getId() == veiculo.getModelo().getId()) {
                    comboBoxModelo.getSelectionModel().select(modelo);
                    break;
                }
            }
        } else {
            textFieldPlaca.setEditable(true); // Permite digitação se for cadastro novo
        }

        if (veiculo.getCor() != null) {
            for (Cor cor : comboBoxCor.getItems()) {
                if (cor.getId() == veiculo.getCor().getId()) {
                    comboBoxCor.getSelectionModel().select(cor);
                    break;
                }
            }
        }
    }

    /**
     * Evento acionado ao confirmar o formulário.
     */
    @FXML
    public void handleButtonConfirmar() {
        if (validarEntradaDeDados()) {
            veiculo.setCliente(comboBoxCliente.getSelectionModel().getSelectedItem());
            veiculo.setPlaca(textFieldPlaca.getText().toUpperCase().trim()); // Normaliza em caixa alta
            veiculo.setObservacoes(textFieldObservacoes.getText());
            veiculo.setModelo(comboBoxModelo.getSelectionModel().getSelectedItem());
            veiculo.setCor(comboBoxCor.getSelectionModel().getSelectedItem());

            buttonConfirmarClicked = true;
            dialogStage.close();
        }
    }

    @FXML
    public void handleButtonCancelar() { dialogStage.close(); }

    /**
     * Valida os campos da tela e bloqueia a criação de placas duplicadas no sistema.
     */
    private boolean validarEntradaDeDados() {
        String errorMessage = "";

        if (comboBoxCliente.getSelectionModel().getSelectedItem() == null) {
            errorMessage += "Selecione o Cliente dono do veículo!\n";
        }

        String placaDigitada = textFieldPlaca.getText();
        if (placaDigitada == null || placaDigitada.trim().isEmpty()) {
            errorMessage += "Placa inválida!\n";
        } else {
            // CORREÇÃO: Validação de Duplicidade. Só roda se for uma inserção nova (id == 0)
            if (veiculo.getId() == 0) {
                Veiculo veiculoExistente = veiculoDAO.buscarPorPlaca(placaDigitada.toUpperCase().trim());
                if (veiculoExistente != null && veiculoExistente.getId() != 0) {
                    errorMessage += "Esta placa já está cadastrada para outro veículo no sistema!\n";
                }
            }
        }

        if (comboBoxMarca.getSelectionModel().getSelectedItem() == null) {
            errorMessage += "Selecione uma Marca!\n";
        }
        if (comboBoxModelo.getSelectionModel().getSelectedItem() == null) {
            errorMessage += "Selecione um Modelo!\n";
        }
        if (comboBoxCor.getSelectionModel().getSelectedItem() == null) {
            errorMessage += "Selecione uma Cor!\n";
        }

        if (errorMessage.isEmpty()) {
            return true;
        } else {
            Alert alert = new Alert(Alert.AlertType.ERROR);
            alert.setTitle("Erro no Cadastro");
            alert.setHeaderText("Campos inválidos, por favor, corrija.");
            alert.setContentText(errorMessage);
            alert.show();
            return false;
        }
    }
}