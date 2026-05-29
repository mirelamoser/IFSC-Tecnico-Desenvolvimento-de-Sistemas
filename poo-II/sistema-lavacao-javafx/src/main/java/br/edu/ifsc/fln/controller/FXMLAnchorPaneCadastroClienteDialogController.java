package br.edu.ifsc.fln.controller;

import br.edu.ifsc.fln.model.domain.Cliente;
import br.edu.ifsc.fln.model.domain.PessoaFisica;
import br.edu.ifsc.fln.model.domain.PessoaJuridica;
import java.net.URL;
import java.util.ResourceBundle;
import javafx.fxml.FXML;
import javafx.fxml.Initializable;
import javafx.scene.control.Alert;
import javafx.scene.control.Button;
import javafx.scene.control.DatePicker;
import javafx.scene.control.RadioButton;
import javafx.scene.control.TextField;
import javafx.scene.control.ToggleGroup;
import javafx.stage.Stage;

public class FXMLAnchorPaneCadastroClienteDialogController implements Initializable {

    @FXML private TextField tfNome;
    @FXML private TextField tfCelular;
    @FXML private TextField tfEmail;

    // Campos específicos Pessoa Física
    @FXML private TextField tfCpf;
    @FXML private DatePicker dpDataNascimento;

    // Campos específicos Pessoa Jurídica
    @FXML private TextField tfCnpj;
    @FXML private TextField tfInscricaoEstadual;

    // Seleção de Tipo (Utiliza ToggleGroup no FXML)
    @FXML private RadioButton rbPessoaFisica;
    @FXML private RadioButton rbPessoaJuridica;
    @FXML private ToggleGroup tgTipo;

    @FXML private Button btConfirmar;
    @FXML private Button btCancelar;

    private Stage dialogStage;
    private boolean buttonConfirmarClicked = false;
    private Cliente cliente;

    @Override
    public void initialize(URL url, ResourceBundle rb) {
        // Lógica para alternar visibilidade de campos conforme o RadioButton selecionado
        tgTipo.selectedToggleProperty().addListener((observable, oldValue, newValue) -> {
            boolean isFisica = rbPessoaFisica.isSelected();
            configurarCamposPorTipo(isFisica);
        });
    }

    private void configurarCamposPorTipo(boolean isFisica) {
        // Habilita/Desabilita conforme o tipo
        tfCpf.setDisable(!isFisica);
        dpDataNascimento.setDisable(!isFisica);

        tfCnpj.setDisable(isFisica);
        tfInscricaoEstadual.setDisable(isFisica);
    }

    public void setDialogStage(Stage dialogStage) {
        this.dialogStage = dialogStage;
    }

    public boolean isButtonConfirmarClicked() {
        return buttonConfirmarClicked;
    }

    public void setCliente(Cliente cliente) {
        this.cliente = cliente;

        if (cliente != null) {
            // Se o ID for > 0, é uma alteração. Bloqueamos a troca de tipo (Regra de Negócio)
            if (cliente.getId() > 0) {
                rbPessoaFisica.setDisable(true);
                rbPessoaJuridica.setDisable(true);
            }

            this.tfNome.setText(cliente.getNome());
            this.tfCelular.setText(cliente.getCelular());
            this.tfEmail.setText(cliente.getEmail());

            // Uso do instanceof para tratar especializações de forma polimórfica
            if (cliente instanceof PessoaFisica pf) {
                rbPessoaFisica.setSelected(true);
                this.tfCpf.setText(pf.getCpf());
                this.dpDataNascimento.setValue(pf.getDataNascimento());
                configurarCamposPorTipo(true);
            } else if (cliente instanceof PessoaJuridica pj) {
                rbPessoaJuridica.setSelected(true);
                this.tfCnpj.setText(pj.getCnpj());
                this.tfInscricaoEstadual.setText(pj.getInscricaoEstadual());
                configurarCamposPorTipo(false);
            }
        }
    }

    @FXML
    public void handleBtConfirmar() {
        if (validarEntradaDeDados()) {
            // Se for um novo cliente (Insert), precisamos instanciar a classe correta
            if (cliente.getId() == 0) {
                if (rbPessoaFisica.isSelected()) {
                    cliente = new PessoaFisica();
                } else {
                    cliente = new PessoaJuridica();
                }
            }

            cliente.setNome(tfNome.getText());
            cliente.setCelular(tfCelular.getText());
            cliente.setEmail(tfEmail.getText());

            if (cliente instanceof PessoaFisica pf) {
                pf.setCpf(tfCpf.getText());
                pf.setDataNascimento(dpDataNascimento.getValue());
            } else if (cliente instanceof PessoaJuridica pj) {
                pj.setCnpj(tfCnpj.getText());
                pj.setInscricaoEstadual(tfInscricaoEstadual.getText());
            }

            buttonConfirmarClicked = true;
            dialogStage.close();
        }
    }

    @FXML
    public void handleBtCancelar() {
        dialogStage.close();
    }

    // Metodo para atualizar o objeto cliente no controller principal após a criação
    public Cliente getCliente() {
        return this.cliente;
    }

    private boolean validarEntradaDeDados() {
        String errorMessage = "";
        if (tfNome.getText() == null || tfNome.getText().isEmpty()) errorMessage += "Nome inválido!\n";
        // Adicione outras validações conforme necessário...

        if (errorMessage.isEmpty()) {
            return true;
        } else {
            Alert alert = new Alert(Alert.AlertType.ERROR);
            alert.setTitle("Campos Inválidos");
            alert.setHeaderText("Por favor, corrija os campos abaixo:");
            alert.setContentText(errorMessage);
            alert.show();
            return false;
        }
    }
}