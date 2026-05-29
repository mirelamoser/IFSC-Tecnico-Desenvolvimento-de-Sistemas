package br.edu.ifsc.fln.controller;

import br.edu.ifsc.fln.model.dao.ModeloDAO;
import br.edu.ifsc.fln.model.database.Database;
import br.edu.ifsc.fln.model.database.DatabaseFactory;
import br.edu.ifsc.fln.model.domain.ETipoCombustivel;
import br.edu.ifsc.fln.model.domain.Marca;
import br.edu.ifsc.fln.model.domain.Modelo;
import br.edu.ifsc.fln.model.domain.Motor;
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

public class FXMLAnchorPaneCadastroModeloController implements Initializable {

    @FXML
    private TableView<Modelo> tableViewModelos;
    @FXML
    private TableColumn<Modelo, String> tableColumnModeloDescricao;
    @FXML
    private TableColumn<Modelo, String> tableColumnModeloMarca;

    @FXML
    private Label labelModeloId;
    @FXML
    private Label labelModeloDescricao;
    @FXML
    private Label labelModeloMarca;
    // INCLUSÃO: Novas labels solicitadas pelo professor para exibir os detalhes
    @FXML
    private Label labelModeloCategoria;
    @FXML
    private Label labelModeloPotencia;
    @FXML
    private Label labelModeloCombustivel;

    @FXML
    private Button btInserir;
    @FXML
    private Button btAlterar;
    @FXML
    private Button btExcluir;

    private List<Modelo> listModelos;
    private ObservableList<Modelo> observableListModelos;

    private final Database database = DatabaseFactory.getDatabase("mysql");
    private final Connection connection = database.conectar();
    private final ModeloDAO modeloDAO = new ModeloDAO();

    @Override
    public void initialize(URL url, ResourceBundle rb) {
        modeloDAO.setConnection(connection);
        carregarTableViewModelos();
        selecionarItemTableView(null);

        tableViewModelos.getSelectionModel().selectedItemProperty().addListener(
                (observable, oldValue, newValue) -> selecionarItemTableView(newValue));
    }

    public void carregarTableViewModelos() {
        tableColumnModeloDescricao.setCellValueFactory(new PropertyValueFactory<>("descricao"));
        tableColumnModeloMarca.setCellValueFactory(cellData ->
                new SimpleStringProperty(cellData.getValue().getMarca().getNome())
        );

        listModelos = modeloDAO.listar();
        observableListModelos = FXCollections.observableArrayList(listModelos);
        tableViewModelos.setItems(observableListModelos);
    }

    // EXPLICAÇÃO DA ALTERAÇÃO: Adicionamos o preenchimento das novas labels puxando os dados do objeto Modelo
    public void selecionarItemTableView(Modelo modelo) {
        if (modelo != null) {
            labelModeloId.setText(String.valueOf(modelo.getId()));
            labelModeloDescricao.setText(modelo.getDescricao());
            labelModeloMarca.setText(modelo.getMarca().getNome());

            // Tratamento da Categoria (Verifica se não está nula para evitar erros e pega a descrição amigável)
            if (modelo.getCategoria() != null) {
                labelModeloCategoria.setText(modelo.getCategoria().getDescricao());
            } else {
                labelModeloCategoria.setText("");
            }

            // Tratamento do Motor (Busca no objeto composto Motor)
            if (modelo.getMotor() != null) {
                labelModeloPotencia.setText(String.valueOf(modelo.getMotor().getPotencia()) + " cv");
                if (modelo.getMotor().getTipoCombustivel() != null) {
                    labelModeloCombustivel.setText(modelo.getMotor().getTipoCombustivel().getDescricao());
                } else {
                    labelModeloCombustivel.setText("");
                }
            } else {
                labelModeloPotencia.setText("");
                labelModeloCombustivel.setText("");
            }

        } else {
            // Se nenhum item estiver selecionado, limpa tudo
            labelModeloId.setText("");
            labelModeloDescricao.setText("");
            labelModeloMarca.setText("");
            labelModeloCategoria.setText("");
            labelModeloPotencia.setText("");
            labelModeloCombustivel.setText("");
        }
    }

    @FXML
    public void handleBtInserir() throws IOException {
        Modelo modelo = new Modelo();
        modelo.setMarca(new Marca());
        modelo.setMotor(new Motor(0, ETipoCombustivel.GASOLINA));

        boolean buttonConfirmarClicked = showFXMLCadastroModeloDialog(modelo);
        if (buttonConfirmarClicked) {
            modeloDAO.inserir(modelo);
            carregarTableViewModelos();
        }
    }

    @FXML
    public void handleBtAlterar() throws IOException {
        Modelo modelo = tableViewModelos.getSelectionModel().getSelectedItem();
        if (modelo != null) {
            boolean buttonConfirmarClicked = showFXMLCadastroModeloDialog(modelo);
            if (buttonConfirmarClicked) {
                modeloDAO.alterar(modelo);
                carregarTableViewModelos();
            }
        } else {
            Alert alert = new Alert(Alert.AlertType.ERROR);
            alert.setContentText("Por favor, escolha um modelo na Tabela!");
            alert.show();
        }
    }

    @FXML
    public void handleBtExcluir() throws IOException {
        Modelo modelo = tableViewModelos.getSelectionModel().getSelectedItem();
        if (modelo != null) {
            modeloDAO.remover(modelo);
            carregarTableViewModelos();
        } else {
            Alert alert = new Alert(Alert.AlertType.ERROR);
            alert.setContentText("Por favor, escolha um modelo na Tabela!");
            alert.show();
        }
    }

    private boolean showFXMLCadastroModeloDialog(Modelo modelo) throws IOException {
        FXMLLoader loader = new FXMLLoader();
        loader.setLocation(FXMLAnchorPaneCadastroModeloController.class.getResource("/view/FXMLAnchorPaneCadastroModeloDialog.fxml"));
        AnchorPane page = (AnchorPane) loader.load();

        Stage dialogStage = new Stage();
        dialogStage.setTitle("Cadastro de Modelo");
        Scene scene = new Scene(page);
        dialogStage.setScene(scene);

        FXMLAnchorPaneCadastroModeloDialogController controller = loader.getController();
        controller.setDialogStage(dialogStage);
        controller.setModelo(modelo);

        dialogStage.showAndWait();

        return controller.isButtonConfirmarClicked();
    }
}