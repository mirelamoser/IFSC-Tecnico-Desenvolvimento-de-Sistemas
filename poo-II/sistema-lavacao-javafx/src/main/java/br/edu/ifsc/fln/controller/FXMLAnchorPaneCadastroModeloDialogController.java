package br.edu.ifsc.fln.controller;

import br.edu.ifsc.fln.model.dao.MarcaDAO;
import br.edu.ifsc.fln.model.database.Database;
import br.edu.ifsc.fln.model.database.DatabaseFactory;
import br.edu.ifsc.fln.model.domain.ECategoria;
import br.edu.ifsc.fln.model.domain.ETipoCombustivel;
import br.edu.ifsc.fln.model.domain.Marca;
import br.edu.ifsc.fln.model.domain.Modelo;
import br.edu.ifsc.fln.model.domain.Motor; // Adicionado import para segurança
import java.net.URL;
import java.sql.Connection;
import java.util.List;
import java.util.ResourceBundle;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.fxml.Initializable;
import javafx.scene.control.Alert;
import javafx.scene.control.Button;
import javafx.scene.control.ComboBox;
import javafx.scene.control.TextField;
import javafx.stage.Stage;

public class FXMLAnchorPaneCadastroModeloDialogController implements Initializable {

    @FXML
    private TextField textFieldDescricao;

    @FXML
    private ComboBox<Marca> comboBoxMarca;

    @FXML
    private ComboBox<ECategoria> comboBoxCategoria;

    @FXML
    private TextField textFieldPotencia;

    @FXML
    private ComboBox<ETipoCombustivel> comboBoxCombustivel;

    @FXML
    private Button buttonConfirmar;

    @FXML
    private Button buttonCancelar;

    private Stage dialogStage;
    private boolean buttonConfirmarClicked = false;
    private Modelo modelo;

    private final Database database = DatabaseFactory.getDatabase("mysql");
    private final Connection connection = database.conectar();
    private final MarcaDAO marcaDAO = new MarcaDAO();

    @Override
    public void initialize(URL url, ResourceBundle rb) {
        marcaDAO.setConnection(connection);
        carregarComboBoxMarcas();
        carregarComboBoxCategoria();
        carregarComboBoxCombustivel();

        // ALTERAÇÃO DIDÁTICA: Garante que o foco comece na Marca (UX solicitada pelo professor)
        // Usamos o runLater para garantir que o JavaFX aplique o foco assim que a janela renderizar
        javafx.application.Platform.runLater(() -> comboBoxMarca.requestFocus());
    }

    private void carregarComboBoxMarcas() {
        List<Marca> listMarcas = marcaDAO.listar();
        ObservableList<Marca> observableListMarcas = FXCollections.observableArrayList(listMarcas);
        comboBoxMarca.setItems(observableListMarcas);
    }

    private void carregarComboBoxCategoria() {
        comboBoxCategoria.setItems(FXCollections.observableArrayList(ECategoria.values()));
    }

    private void carregarComboBoxCombustivel() {
        comboBoxCombustivel.setItems(FXCollections.observableArrayList(ETipoCombustivel.values()));
    }

    public Stage getDialogStage() {
        return dialogStage;
    }

    public void setDialogStage(Stage dialogStage) {
        this.dialogStage = dialogStage;
    }

    public boolean isButtonConfirmarClicked() {
        return buttonConfirmarClicked;
    }

    public void setModelo(Modelo modelo) {
        this.modelo = modelo;
        this.textFieldDescricao.setText(modelo.getDescricao());

        if (modelo.getMarca() != null) {
            for (Marca marca : comboBoxMarca.getItems()) {
                if (marca.getId() == modelo.getMarca().getId()) {
                    comboBoxMarca.getSelectionModel().select(marca);
                    break;
                }
            }
        }

        if (modelo.getCategoria() != null) {
            comboBoxCategoria.getSelectionModel().select(modelo.getCategoria());
        }

        if (modelo.getMotor() != null) {
            if(modelo.getMotor().getPotencia() > 0) {
                this.textFieldPotencia.setText(String.valueOf(modelo.getMotor().getPotencia()));
            }
            if (modelo.getMotor().getTipoCombustivel() != null) {
                comboBoxCombustivel.getSelectionModel().select(modelo.getMotor().getTipoCombustivel());
            }
        }
    }

    @FXML
    public void handleButtonConfirmar() {
        if (validarEntradaDeDados()) {
            modelo.setDescricao(textFieldDescricao.getText());
            modelo.setMarca(comboBoxMarca.getSelectionModel().getSelectedItem());
            modelo.setCategoria(comboBoxCategoria.getSelectionModel().getSelectedItem());

            // AJUSTE DE SEGURANÇA: Previne NullPointerException caso o objeto interno motor perca a referência
            if (modelo.getMotor() == null) {
                modelo.setMotor(new Motor(0, ETipoCombustivel.GASOLINA));
            }

            modelo.getMotor().setPotencia(Integer.parseInt(textFieldPotencia.getText()));
            modelo.getMotor().setTipoCombustivel(comboBoxCombustivel.getSelectionModel().getSelectedItem());

            buttonConfirmarClicked = true;
            dialogStage.close();
        }
    }

    @FXML
    public void handleButtonCancelar() {
        dialogStage.close();
    }

    private boolean validarEntradaDeDados() {
        String errorMessage = "";

        if (textFieldDescricao.getText() == null || textFieldDescricao.getText().isEmpty()) {
            errorMessage += "Descrição inválida!\n";
        }
        if (comboBoxMarca.getSelectionModel().getSelectedItem() == null) {
            errorMessage += "Selecione uma Marca!\n";
        }
        if (comboBoxCategoria.getSelectionModel().getSelectedItem() == null) {
            errorMessage += "Selecione uma Categoria!\n";
        }
        if (comboBoxCombustivel.getSelectionModel().getSelectedItem() == null) {
            errorMessage += "Selecione um Tipo de Combustível!\n";
        }

        if (textFieldPotencia.getText() == null || textFieldPotencia.getText().isEmpty()) {
            errorMessage += "Informe a Potência do Motor!\n";
        } else {
            try {
                Integer.parseInt(textFieldPotencia.getText());
            } catch (NumberFormatException e) {
                errorMessage += "A Potência deve ser um número inteiro (Ex: 100, 150)!\n";
            }
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