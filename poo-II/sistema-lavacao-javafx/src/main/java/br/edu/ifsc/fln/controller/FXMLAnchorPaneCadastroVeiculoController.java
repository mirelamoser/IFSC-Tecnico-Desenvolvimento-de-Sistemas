package br.edu.ifsc.fln.controller;

import br.edu.ifsc.fln.model.dao.VeiculoDAO;
import br.edu.ifsc.fln.model.database.Database;
import br.edu.ifsc.fln.model.database.DatabaseFactory;
import br.edu.ifsc.fln.model.domain.Veiculo;
import java.io.IOException;
import java.net.URL;
import java.sql.Connection;
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

public class FXMLAnchorPaneCadastroVeiculoController implements Initializable {

    @FXML
    private TableView<Veiculo> tableViewVeiculos;
    @FXML
    private TableColumn<Veiculo, String> tableColumnVeiculoPlaca;
    @FXML
    private TableColumn<Veiculo, String> tableColumnVeiculoModelo;
    @FXML
    private TableColumn<Veiculo, String> tableColumnVeiculoMarca;
    @FXML
    private TableColumn<Veiculo, String> tableColumnVeiculoCor;

    // NOVO: Coluna do Cliente
    @FXML
    private TableColumn<Veiculo, String> tableColumnVeiculoCliente;

    @FXML
    private Label labelVeiculoId;
    @FXML
    private Label labelVeiculoPlaca;
    @FXML
    private Label labelVeiculoModelo;
    @FXML
    private Label labelVeiculoMarca;
    @FXML
    private Label labelVeiculoCor;
    @FXML
    private Label labelVeiculoObservacoes;

    // NOVO: Label do Cliente nos detalhes
    @FXML
    private Label labelVeiculoCliente;

    @FXML
    private Button btInserir;
    @FXML
    private Button btAlterar;
    @FXML
    private Button btExcluir;

    private List<Veiculo> listVeiculos;
    private ObservableList<Veiculo> observableListVeiculos;

    private final Database database = DatabaseFactory.getDatabase("mysql");
    private final Connection connection = database.conectar();
    private final VeiculoDAO veiculoDAO = new VeiculoDAO();

    @Override
    public void initialize(URL url, ResourceBundle rb) {
        veiculoDAO.setConnection(connection);
        carregarTableViewVeiculos();

        selecionarItemTableView(null);

        tableViewVeiculos.getSelectionModel().selectedItemProperty().addListener(
                (observable, oldValue, newValue) -> selecionarItemTableView(newValue));
    }

    public void carregarTableViewVeiculos() {
        tableColumnVeiculoPlaca.setCellValueFactory(new PropertyValueFactory<>("placa"));

        tableColumnVeiculoModelo.setCellValueFactory(cellData ->
                new SimpleStringProperty(cellData.getValue().getModelo().getDescricao())
        );
        // Puxando a Marca através do Modelo do Veículo
        tableColumnVeiculoMarca.setCellValueFactory(cellData ->
                new SimpleStringProperty(cellData.getValue().getModelo().getMarca().getNome())
        );
        tableColumnVeiculoCor.setCellValueFactory(cellData ->
                new SimpleStringProperty(cellData.getValue().getCor().getNome())
        );

        // NOVO: Puxando o Nome do Cliente através do Veículo
        tableColumnVeiculoCliente.setCellValueFactory(cellData -> {
            if (cellData.getValue().getCliente() != null) {
                return new SimpleStringProperty(cellData.getValue().getCliente().getNome());
            } else {
                return new SimpleStringProperty("Sem Cliente");
            }
        });

        listVeiculos = veiculoDAO.listar();
        observableListVeiculos = FXCollections.observableArrayList(listVeiculos);
        tableViewVeiculos.setItems(observableListVeiculos);
    }

    public void selecionarItemTableView(Veiculo veiculo) {
        if (veiculo != null) {
            labelVeiculoId.setText(String.valueOf(veiculo.getId()));
            labelVeiculoPlaca.setText(veiculo.getPlaca());
            labelVeiculoModelo.setText(veiculo.getModelo().getDescricao());
            labelVeiculoMarca.setText(veiculo.getModelo().getMarca().getNome());
            labelVeiculoCor.setText(veiculo.getCor().getNome());
            labelVeiculoObservacoes.setText(veiculo.getObservacoes());

            // NOVO: Preenchendo o Label do Cliente
            if (veiculo.getCliente() != null) {
                labelVeiculoCliente.setText(veiculo.getCliente().getNome());
            } else {
                labelVeiculoCliente.setText("Sem Cliente");
            }
        } else {
            labelVeiculoId.setText("");
            labelVeiculoPlaca.setText("");
            labelVeiculoModelo.setText("");
            labelVeiculoMarca.setText("");
            labelVeiculoCor.setText("");
            labelVeiculoObservacoes.setText("");

            // NOVO: Limpando o Label do Cliente
            labelVeiculoCliente.setText("");
        }
    }

    @FXML
    public void handleBtInserir() throws IOException {
        Veiculo veiculo = new Veiculo();
        boolean buttonConfirmarClicked = showFXMLCadastroVeiculoDialog(veiculo);
        if (buttonConfirmarClicked) {
            veiculoDAO.inserir(veiculo);
            carregarTableViewVeiculos();
        }
    }

    @FXML
    public void handleBtAlterar() throws IOException {
        Veiculo veiculo = tableViewVeiculos.getSelectionModel().getSelectedItem();
        if (veiculo != null) {
            boolean buttonConfirmarClicked = showFXMLCadastroVeiculoDialog(veiculo);
            if (buttonConfirmarClicked) {
                veiculoDAO.alterar(veiculo);
                carregarTableViewVeiculos();
            }
        } else {
            Alert alert = new Alert(Alert.AlertType.ERROR);
            alert.setContentText("Por favor, escolha um veículo na Tabela");
            alert.show();
        }
    }

    @FXML
    public void handleBtExcluir() throws IOException {
        Veiculo veiculo = tableViewVeiculos.getSelectionModel().getSelectedItem();
        if (veiculo != null) {
            veiculoDAO.remover(veiculo);
            carregarTableViewVeiculos();
        } else {
            Alert alert = new Alert(Alert.AlertType.ERROR);
            alert.setContentText("Por favor, escolha um veículo na Tabela");
            alert.show();
        }
    }

    private boolean showFXMLCadastroVeiculoDialog(Veiculo veiculo) throws IOException {
        FXMLLoader loader = new FXMLLoader();
        // ATENÇÃO: Certifique-se de que este caminho está correto no seu projeto
        loader.setLocation(FXMLAnchorPaneCadastroVeiculoController.class.getResource("/view/FXMLAnchorPaneCadastroVeiculoDialog.fxml"));
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