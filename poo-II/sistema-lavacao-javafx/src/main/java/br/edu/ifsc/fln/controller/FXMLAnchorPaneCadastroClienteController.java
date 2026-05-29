package br.edu.ifsc.fln.controller;

import br.edu.ifsc.fln.model.dao.ClienteDAO;
import br.edu.ifsc.fln.model.dao.VeiculoDAO;
import br.edu.ifsc.fln.model.database.Database;
import br.edu.ifsc.fln.model.database.DatabaseFactory;
import br.edu.ifsc.fln.model.domain.Cliente;
import br.edu.ifsc.fln.model.domain.PessoaFisica;
import br.edu.ifsc.fln.model.domain.Veiculo;
import java.io.IOException;
import java.net.URL;
import java.sql.Connection;
import java.time.LocalDate;
import java.util.List;
import java.util.ResourceBundle;
import javafx.beans.property.SimpleStringProperty;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.fxml.Initializable;
import javafx.scene.Scene;
import javafx.scene.control.Alert;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.control.TableColumn;
import javafx.scene.control.TableView;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.AnchorPane;
import javafx.stage.Stage;

public class FXMLAnchorPaneCadastroClienteController implements Initializable {

    // Componentes da Tabela Principal (Esquerda)
    @FXML
    private TableView<Cliente> tableViewClientes;
    @FXML
    private TableColumn<Cliente, String> tableColumnClienteNome;
    @FXML
    private TableColumn<Cliente, String> tableColumnClienteTipo;

    // Componentes de Detalhes (Direita - Labels)
    @FXML
    private Label labelClienteId;
    @FXML
    private Label labelClienteNome;
    @FXML
    private Label labelClientePontos;
    @FXML
    private Label labelClienteCelular;
    @FXML
    private Label labelClienteEmail;
    @FXML
    private Label labelClienteDataCadastro;

    // Componentes da Tabela de Veículos do Cliente (Relacionamento 1:N)
    @FXML
    private TableView<Veiculo> tableViewVeiculosCliente;
    @FXML
    private TableColumn<Veiculo, String> tableColumnVeiculoPlaca;
    @FXML
    private TableColumn<Veiculo, String> tableColumnVeiculoModelo;

    @FXML
    private Button btInserir;
    @FXML
    private Button btAlterar;
    @FXML
    private Button btExcluir;
    @FXML
    private Button btAdicionarVeiculo;

    private List<Cliente> listClientes;
    private ObservableList<Cliente> observableListClientes;
    private ObservableList<Veiculo> observableListVeiculosCliente;

    // Configuração do Banco de Dados e DAOs
    private final Database database = DatabaseFactory.getDatabase("mysql");
    private final Connection connection = database.conectar();
    private final ClienteDAO clienteDAO = new ClienteDAO();
    private final VeiculoDAO veiculoDAO = new VeiculoDAO(); // DAO de veículo instanciado!

    @Override
    public void initialize(URL url, ResourceBundle rb) {
        // Passando a conexão para os DAOs
        clienteDAO.setConnection(connection);
        veiculoDAO.setConnection(connection);

        carregarTableViewClientes();

        // Inicializa os detalhes vazios
        selecionarItemTableView(null);

        // Listener para detectar clique na tabela de clientes e atualizar a tela da direita
        tableViewClientes.getSelectionModel().selectedItemProperty().addListener(
                (observable, oldValue, newValue) -> selecionarItemTableView(newValue));
    }

    public void carregarTableViewClientes() {
        tableColumnClienteNome.setCellValueFactory(new PropertyValueFactory<>("nome"));

        // Verificamos se o cliente é PF ou PJ para exibir na coluna Tipo
        tableColumnClienteTipo.setCellValueFactory(cellData -> {
            String tipo = cellData.getValue().getClass().getSimpleName();
            return new SimpleStringProperty(tipo);
        });

        listClientes = clienteDAO.listar();
        observableListClientes = FXCollections.observableArrayList(listClientes);
        tableViewClientes.setItems(observableListClientes);
    }

    public void selecionarItemTableView(Cliente cliente) {
        if (cliente != null) {
            labelClienteId.setText(String.valueOf(cliente.getId()));
            labelClienteNome.setText(cliente.getNome());

            // Tratamento caso a pontuação seja nula (evita NullPointerException)
            if (cliente.getPontuacao() != null) {
                labelClientePontos.setText(String.valueOf(cliente.getPontuacao().getQuantidade()));
            } else {
                labelClientePontos.setText("0");
            }

            labelClienteCelular.setText(cliente.getCelular());
            labelClienteEmail.setText(cliente.getEmail());
            labelClienteDataCadastro.setText(cliente.getDataCadastro() != null ? cliente.getDataCadastro().toString() : "");

            // Carregando a tabela de veículos associada a este cliente
            if (cliente.getVeiculos() != null) {
                tableColumnVeiculoPlaca.setCellValueFactory(new PropertyValueFactory<>("placa"));
                tableColumnVeiculoModelo.setCellValueFactory(cellData -> {
                    if (cellData.getValue().getModelo() != null) {
                        return new SimpleStringProperty(cellData.getValue().getModelo().getDescricao());
                    }
                    return new SimpleStringProperty("");
                });

                observableListVeiculosCliente = FXCollections.observableArrayList(cliente.getVeiculos());
                tableViewVeiculosCliente.setItems(observableListVeiculosCliente);
            } else {
                tableViewVeiculosCliente.setItems(null);
            }
        } else {
            limparDetalhes();
        }
    }

    private void limparDetalhes() {
        labelClienteId.setText("");
        labelClienteNome.setText("");
        labelClientePontos.setText("");
        labelClienteCelular.setText("");
        labelClienteEmail.setText("");
        labelClienteDataCadastro.setText("");
        tableViewVeiculosCliente.setItems(null);
    }

    @FXML
    public void handleBtInserir() throws IOException {
        FXMLLoader loader = new FXMLLoader();
        loader.setLocation(FXMLAnchorPaneCadastroClienteController.class.getResource("/view/FXMLAnchorPaneCadastroClienteDialog.fxml"));
        AnchorPane page = (AnchorPane) loader.load();

        Stage dialogStage = new Stage();
        dialogStage.setTitle("Cadastro de Cliente");
        Scene scene = new Scene(page);
        dialogStage.setScene(scene);

        FXMLAnchorPaneCadastroClienteDialogController controller = loader.getController();
        controller.setDialogStage(dialogStage);

        Cliente cliente = new PessoaFisica();
        cliente.setId(0);
        controller.setCliente(cliente);

        dialogStage.showAndWait();

        if (controller.isButtonConfirmarClicked()) {
            Cliente clienteParaSalvar = controller.getCliente();
            clienteParaSalvar.setDataCadastro(LocalDate.now());

            clienteDAO.inserir(clienteParaSalvar);
            carregarTableViewClientes();
        }
    }

    @FXML
    public void handleBtAlterar() throws IOException {
        Cliente cliente = tableViewClientes.getSelectionModel().getSelectedItem();
        if (cliente != null) {
            boolean buttonConfirmarClicked = showFXMLCadastroClienteDialog(cliente);
            if (buttonConfirmarClicked) {
                clienteDAO.alterar(cliente);
                carregarTableViewClientes();
                selecionarItemTableView(cliente); // Atualiza os detalhes também
            }
        } else {
            exibirAlertaSelecao();
        }
    }

    @FXML
    public void handleBtExcluir() throws IOException {
        Cliente cliente = tableViewClientes.getSelectionModel().getSelectedItem();
        if (cliente != null) {
            clienteDAO.remover(cliente);
            carregarTableViewClientes();
            limparDetalhes();
        } else {
            exibirAlertaSelecao();
        }
    }

    @FXML
    public void handleBtAdicionarVeiculo() throws IOException {
        // 1. Pega o cliente que o usuário clicou na tabela
        Cliente clienteSelecionado = tableViewClientes.getSelectionModel().getSelectedItem();

        if (clienteSelecionado != null) {
            // 2. Cria um veículo vazio e vincula a este cliente (Associação Bidirecional)
            Veiculo novoVeiculo = new Veiculo();
            novoVeiculo.setCliente(clienteSelecionado);

            // 3. Chama o método que vai abrir a tela de Diálogo do Veículo
            boolean buttonConfirmarClicked = showFXMLCadastroVeiculoDialog(novoVeiculo);

            if (buttonConfirmarClicked) {
                // 4. Salva no banco de dados através do VeiculoDAO
                veiculoDAO.inserir(novoVeiculo);

                // 5. Adiciona o veículo à lista do cliente na memória
                if (clienteSelecionado.getVeiculos() != null) {
                    clienteSelecionado.getVeiculos().add(novoVeiculo);
                }

                // 6. Recarrega os dados da tela para o veículo aparecer na tabelinha
                selecionarItemTableView(clienteSelecionado);
            }
        } else {
            exibirAlertaSelecao();
        }
    }

    private void exibirAlertaSelecao() {
        Alert alert = new Alert(Alert.AlertType.WARNING);
        alert.setTitle("Atenção");
        alert.setHeaderText("Nenhum cliente selecionado");
        alert.setContentText("Por favor, escolha um cliente na Tabela antes de realizar esta ação.");
        alert.show();
    }

    private boolean showFXMLCadastroClienteDialog(Cliente cliente) throws IOException {
        FXMLLoader loader = new FXMLLoader();
        loader.setLocation(FXMLAnchorPaneCadastroClienteController.class.getResource("/view/FXMLAnchorPaneCadastroClienteDialog.fxml"));
        AnchorPane page = (AnchorPane) loader.load();

        Stage dialogStage = new Stage();
        dialogStage.setTitle("Cadastro de Cliente");
        Scene scene = new Scene(page);
        dialogStage.setScene(scene);

        FXMLAnchorPaneCadastroClienteDialogController controller = loader.getController();
        controller.setDialogStage(dialogStage);
        controller.setCliente(cliente);

        dialogStage.showAndWait();

        return controller.isButtonConfirmarClicked();
    }

    private boolean showFXMLCadastroVeiculoDialog(Veiculo veiculo) throws IOException {
        FXMLLoader loader = new FXMLLoader();
        loader.setLocation(FXMLAnchorPaneCadastroClienteController.class.getResource("/view/FXMLAnchorPaneCadastroVeiculoDialog.fxml"));
        AnchorPane page = (AnchorPane) loader.load();

        Stage dialogStage = new Stage();
        dialogStage.setTitle("Cadastro de Veículo");
        Scene scene = new Scene(page);
        dialogStage.setScene(scene);

        FXMLAnchorPaneCadastroVeiculoDialogController controller = loader.getController();
        controller.setDialogStage(dialogStage);
        controller.setVeiculo(veiculo);

        dialogStage.showAndWait();

        return controller.isButtonConfirmarClicked();
    }
}