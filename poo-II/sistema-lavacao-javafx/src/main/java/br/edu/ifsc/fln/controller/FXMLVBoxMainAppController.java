    /*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/javafx/FXMLController.java to edit this template
 */
package br.edu.ifsc.fln.controller;

    import br.edu.ifsc.fln.model.dao.ConfiguracaoDAO;
    import br.edu.ifsc.fln.model.database.Database;
    import br.edu.ifsc.fln.model.database.DatabaseFactory;
    import br.edu.ifsc.fln.model.domain.Servico;
    import java.sql.Connection;

    import java.io.IOException;
    import java.net.URL;
    import java.util.ResourceBundle;

    import javafx.fxml.FXML;
    import javafx.fxml.FXMLLoader;
    import javafx.fxml.Initializable;
    import javafx.scene.control.Menu;
    import javafx.scene.control.MenuItem;
    import javafx.scene.layout.AnchorPane;

/**
 * FXML Controller class
 *
 * @author mpisc
 */
public class FXMLVBoxMainAppController implements Initializable {

    @FXML
    private MenuItem menuItemCadastroCor;

    @FXML
    private MenuItem menuItemCadastroMarca;

    @FXML
    private MenuItem menuItemCadastroServico;

    @FXML
    private MenuItem menuItemCadastroModelo;

    @FXML
    private MenuItem menuItemCadastroVeiculo;

    @FXML
    private MenuItem menuItemCadastroCliente;

    @FXML
    private AnchorPane anchorPane;
    /**
     * Initializes the controller class.
     */
    @Override
    public void initialize(URL url, ResourceBundle rb) {
        Database database = DatabaseFactory.getDatabase("mysql");
        Connection connection = database.conectar();

        ConfiguracaoDAO configuracaoDAO = new ConfiguracaoDAO();
        configuracaoDAO.setConnection(connection);

        int pontosGlobais = configuracaoDAO.buscarPontosServico();
        Servico.setPontos(pontosGlobais);

        System.out.println("Sistema iniciado. Pontos por serviço: " + Servico.getPontos());
    }  
    
    @FXML
    public void handleMenuItemCadastroCor() throws IOException {
        AnchorPane a = (AnchorPane) FXMLLoader.load(getClass().getResource("/view/FXMLAnchorPaneCadastroCor.fxml"));
        anchorPane.getChildren().setAll(a);
    }

    @FXML
    public void handleMenuItemCadastroMarca() throws IOException {
        AnchorPane a = (AnchorPane) FXMLLoader.load(getClass().getResource("/view/FXMLAnchorPaneCadastroMarca.fxml"));
        anchorPane.getChildren().setAll(a);
    }

    @FXML
    public void handleMenuItemCadastroServico() throws IOException {
        AnchorPane a = (AnchorPane) FXMLLoader.load(getClass().getResource("/view/FXMLAnchorPaneCadastroServico.fxml"));
        anchorPane.getChildren().setAll(a);
    }

    @FXML
    public void handleMenuItemCadastroModelo() throws IOException {
        AnchorPane a = (AnchorPane) FXMLLoader.load(getClass().getResource("/view/FXMLAnchorPaneCadastroModelo.fxml"));
        anchorPane.getChildren().setAll(a);
    }

    @FXML
    public void handleMenuItemCadastroVeiculo() throws IOException {
        AnchorPane a = (AnchorPane) FXMLLoader.load(getClass().getResource("/view/FXMLAnchorPaneCadastroVeiculo.fxml"));
        anchorPane.getChildren().setAll(a);
    }


    @FXML
    public void handleMenuItemCadastroCliente() throws IOException {
                AnchorPane a = (AnchorPane) FXMLLoader.load(getClass().getResource("/view/FXMLAnchorPaneCadastroCliente.fxml"));
        anchorPane.getChildren().setAll(a);
    }
}
