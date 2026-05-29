package br.edu.ifsc.fln.model.dao;

import br.edu.ifsc.fln.model.domain.Cliente;
import br.edu.ifsc.fln.model.domain.Cor;
import br.edu.ifsc.fln.model.domain.Modelo;
import br.edu.ifsc.fln.model.domain.Veiculo;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;
import java.util.logging.Level;
import java.util.logging.Logger;

/**
 * Classe DAO para gerenciar a persistência de Veículos no banco de dados.
 */
public class VeiculoDAO {

    // Gerencia a conexão ativa com o banco de dados
    private Connection connection;

    public Connection getConnection() {
        return connection;
    }

    public void setConnection(Connection connection) {
        this.connection = connection;
    }

    /**
     * Insere um novo veículo no banco de dados.
     */
    public boolean inserir(Veiculo veiculo) {
        String sql = "INSERT INTO veiculo(placa, observacoes, id_modelo, id_cor, id_cliente) VALUES(?, ?, ?, ?, ?)";
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setString(1, veiculo.getPlaca());
            stmt.setString(2, veiculo.getObservacoes());
            stmt.setInt(3, veiculo.getModelo().getId());
            stmt.setInt(4, veiculo.getCor().getId());
            stmt.setInt(5, veiculo.getCliente().getId());
            stmt.execute();
            return true;
        } catch (SQLException ex) {
            Logger.getLogger(VeiculoDAO.class.getName()).log(Level.SEVERE, null, ex);
            return false;
        }
    }

    /**
     * Altera os dados de um veículo existente.
     */
    public boolean alterar(Veiculo veiculo) {
        String sql = "UPDATE veiculo SET placa=?, observacoes=?, id_modelo=?, id_cor=?, id_cliente=? WHERE id=?";
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setString(1, veiculo.getPlaca());
            stmt.setString(2, veiculo.getObservacoes());
            stmt.setInt(3, veiculo.getModelo().getId());
            stmt.setInt(4, veiculo.getCor().getId());
            stmt.setInt(5, veiculo.getCliente().getId());
            stmt.setInt(6, veiculo.getId());
            stmt.execute();
            return true;
        } catch (SQLException ex) {
            Logger.getLogger(VeiculoDAO.class.getName()).log(Level.SEVERE, null, ex);
            return false;
        }
    }

    /**
     * Remove um veículo baseado no seu ID.
     */
    public boolean remover(Veiculo veiculo) {
        String sql = "DELETE FROM veiculo WHERE id=?";
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setInt(1, veiculo.getId());
            stmt.execute();
            return true;
        } catch (SQLException ex) {
            Logger.getLogger(VeiculoDAO.class.getName()).log(Level.SEVERE, null, ex);
            return false;
        }
    }

    /**
     * Lista todos os veículos cadastrados no sistema.
     */
    public List<Veiculo> listar() {
        String sql = "SELECT * FROM veiculo";
        List<Veiculo> retorno = new ArrayList<>();
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            ResultSet resultado = stmt.executeQuery();
            while (resultado.next()) {
                Veiculo veiculo = populateVO(resultado);
                retorno.add(veiculo);
            }
        } catch (SQLException ex) {
            Logger.getLogger(VeiculoDAO.class.getName()).log(Level.SEVERE, null, ex);
        }
        return retorno;
    }

    /**
     * Lista os veículos vinculados a um cliente específico (evita loops infinitos).
     */
    public List<Veiculo> listarPorCliente(Cliente cliente) {
        String sql = "SELECT * FROM veiculo WHERE id_cliente = ?";
        List<Veiculo> retorno = new ArrayList<>();
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setInt(1, cliente.getId());
            ResultSet resultado = stmt.executeQuery();
            while (resultado.next()) {
                // Passa o cliente já instanciado por parâmetro para o preenchimento do objeto
                Veiculo veiculo = populateVO(resultado, cliente);
                retorno.add(veiculo);
            }
        } catch (SQLException ex) {
            Logger.getLogger(VeiculoDAO.class.getName()).log(Level.SEVERE, null, ex);
        }
        return retorno;
    }

    /**
     * Busca um veículo específico filtrando pelo seu ID.
     */
    public Veiculo buscar(int id) {
        String sql = "SELECT * FROM veiculo WHERE id = ?";
        Veiculo retorno = new Veiculo();
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setInt(1, id);
            ResultSet resultado = stmt.executeQuery();
            if (resultado.next()) {
                retorno = populateVO(resultado);
            }
        } catch (SQLException ex) {
            Logger.getLogger(VeiculoDAO.class.getName()).log(Level.SEVERE, null, ex);
        }
        return retorno;
    }

    public Veiculo buscar(Veiculo veiculo) {
        return buscar(veiculo.getId());
    }

    /**
     * CORREÇÃO: Busca um veículo no banco utilizando a placa textual.
     * Essencial para validar e impedir cadastros de placas duplicadas.
     */
    public Veiculo buscarPorPlaca(String placa) {
        String sql = "SELECT * FROM veiculo WHERE placa = ?";
        Veiculo retorno = null; // Retorna nulo se a placa estiver livre
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setString(1, placa);
            ResultSet resultado = stmt.executeQuery();
            if (resultado.next()) {
                retorno = populateVO(resultado); // Se encontrou, monta o veículo completo
            }
        } catch (SQLException ex) {
            Logger.getLogger(VeiculoDAO.class.getName()).log(Level.SEVERE, null, ex);
        }
        return retorno;
    }

    // --- Métodos Privados Auxiliares (Mapeamento Relacional) ---

    private Veiculo populateVO(ResultSet rs) throws SQLException {
        return populateVO(rs, null);
    }

    /**
     * Transforma os dados brutos da linha do banco de dados em um objeto Java Veiculo.
     */
    private Veiculo populateVO(ResultSet rs, Cliente cliente) throws SQLException {
        Veiculo veiculo = new Veiculo();

        veiculo.setId(rs.getInt("id"));
        veiculo.setPlaca(rs.getString("placa"));
        veiculo.setObservacoes(rs.getString("observacoes"));

        // Estratégia de corte de loop: Se o cliente já veio pronto por parâmetro, só o associa
        if (cliente != null) {
            veiculo.setCliente(cliente);
        } else {
            // Se veio null, busca o cliente de forma tradicional usando o ClienteDAO
            ClienteDAO clienteDAO = new ClienteDAO();
            clienteDAO.setConnection(connection);
            veiculo.setCliente(clienteDAO.buscar(rs.getInt("id_cliente")));
        }

        // Recupera e associa recursivamente o Modelo do veículo
        ModeloDAO modeloDAO = new ModeloDAO();
        modeloDAO.setConnection(connection);
        veiculo.setModelo(modeloDAO.buscar(rs.getInt("id_modelo")));

        // Recupera e associa a Cor do veículo
        CorDAO corDAO = new CorDAO();
        corDAO.setConnection(connection);
        veiculo.setCor(corDAO.buscar(rs.getInt("id_cor")));

        return veiculo;
    }
}