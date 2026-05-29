package br.edu.ifsc.fln.model.dao;

import br.edu.ifsc.fln.model.domain.ECategoria;
import br.edu.ifsc.fln.model.domain.Servico;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;
import java.util.logging.Level;
import java.util.logging.Logger;

/**
 * Classe DAO para a entidade Servico.
 * Atualizada para persistir o Enum ECategoria como String no Banco de Dados.
 */
public class ServicoDAO {

    private Connection connection;

    public Connection getConnection() {
        return connection;
    }

    public void setConnection(Connection connection) {
        this.connection = connection;
    }

    /**
     * Insere um novo serviço associando-o ao nome do Enum da categoria.
     */
    public boolean inserir(Servico servico) {
        // AJUSTE: Incluída a coluna 'categoria' na query SQL
        String sql = "INSERT INTO servico(descricao, valor, categoria) VALUES(?,?,?)";
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setString(1, servico.getDescricao());
            stmt.setDouble(2, servico.getValor());

            // AJUSTE: Convertemos o valor do Enum para String (.name()) para salvar no varchar do banco
            stmt.setString(3, servico.getCategoria().name());

            stmt.execute();
            return true;
        } catch (SQLException ex) {
            Logger.getLogger(ServicoDAO.class.getName()).log(Level.SEVERE, null, ex);
            return false;
        }
    }

    /**
     * Atualiza os dados do serviço, incluindo a alteração de sua categoria.
     */
    public boolean alterar(Servico servico) {
        // AJUSTE: Incluída a atualização do campo 'categoria'
        String sql = "UPDATE servico SET descricao=?, valor=?, categoria=? WHERE id=?";
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setString(1, servico.getDescricao());
            stmt.setDouble(2, servico.getValor());

            // AJUSTE: Atualiza a String correspondente ao Enum
            stmt.setString(3, servico.getCategoria().name());
            stmt.setInt(4, servico.getId());

            stmt.execute();
            return true;
        } catch (SQLException ex) {
            Logger.getLogger(ServicoDAO.class.getName()).log(Level.SEVERE, null, ex);
            return false;
        }
    }

    /**
     * Remove um serviço baseado no ID. (Permanece inalterado)
     */
    public boolean remover(Servico servico) {
        String sql = "DELETE FROM servico WHERE id=?";
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setInt(1, servico.getId());

            stmt.execute();
            return true;
        } catch (SQLException ex) {
            Logger.getLogger(ServicoDAO.class.getName()).log(Level.SEVERE, null, ex);
            return false;
        }
    }

    /**
     * Recupera todos os serviços do banco de dados e reconstrói o Enum correspondente.
     */
    public List<Servico> listar() {
        String sql = "SELECT * FROM servico";
        List<Servico> retorno = new ArrayList<>();
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            ResultSet resultado = stmt.executeQuery();
            while (resultado.next()) {
                Servico servico = new Servico();
                servico.setId(resultado.getInt("id"));
                servico.setDescricao(resultado.getString("descricao"));
                servico.setValor(resultado.getDouble("valor"));

                // AJUSTE: Lemos a String gravada na coluna 'categoria' e a convertemos
                // de volta para o tipo Enum mapeado (ECategoria) utilizando o método .valueOf()
                String categoriaBanco = resultado.getString("categoria");
                if (categoriaBanco != null) {
                    servico.setCategoria(ECategoria.valueOf(categoriaBanco));
                }

                retorno.add(servico);
            }
        } catch (SQLException ex) {
            Logger.getLogger(ServicoDAO.class.getName()).log(Level.SEVERE, null, ex);
        }
        return retorno;
    }

    /**
     * Sobrecarga do metodo buscar.
     */
    public Servico buscar(Servico servico) {
        return buscar(servico.getId());
    }

    /**
     * Busca um único serviço pelo ID e reconstrói seu Enum de categoria.
     */
    public Servico buscar(int id) {
        String sql = "SELECT * FROM servico WHERE id=?";
        Servico retorno = new Servico();
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setInt(1, id);
            ResultSet resultado = stmt.executeQuery();
            if (resultado.next()) {
                retorno.setId(resultado.getInt("id"));
                retorno.setDescricao(resultado.getString("descricao"));
                retorno.setValor(resultado.getDouble("valor"));

                // AJUSTE: Reconstroi o Enum a partir da String recuperada
                String categoriaBanco = resultado.getString("categoria");
                if (categoriaBanco != null) {
                    retorno.setCategoria(ECategoria.valueOf(categoriaBanco));
                }
            }
        } catch (SQLException ex) {
            Logger.getLogger(ServicoDAO.class.getName()).log(Level.SEVERE, null, ex);
        }
        return retorno;
    }
}