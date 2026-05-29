package br.edu.ifsc.fln.model.dao;

import br.edu.ifsc.fln.model.domain.ECategoria;
import br.edu.ifsc.fln.model.domain.ETipoCombustivel;
import br.edu.ifsc.fln.model.domain.Marca;
import br.edu.ifsc.fln.model.domain.Modelo;
import br.edu.ifsc.fln.model.domain.Motor;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.List;
import java.util.logging.Level;
import java.util.logging.Logger;

public class ModeloDAO {

    private Connection connection;

    public Connection getConnection() {
        return connection;
    }

    public void setConnection(Connection connection) {
        this.connection = connection;
    }

    public boolean inserir(Modelo modelo) {
        final String sql = "INSERT INTO modelo(descricao, id_marca, categoria) VALUES(?, ?, ?)";
        final String sqlMotor = "INSERT INTO motor(id_modelo, potencia, tipoCombustivel) VALUES(?, ?, ?)";
        try {
            // Gerenciamento de Transação: Garante a consistência entre tabelas associadas (Modelo e Motor)
            connection.setAutoCommit(false);

            PreparedStatement stmt = connection.prepareStatement(sql, Statement.RETURN_GENERATED_KEYS);
            stmt.setString(1, modelo.getDescricao());
            stmt.setInt(2, modelo.getMarca().getId());
            stmt.setString(3, modelo.getCategoria().name());
            stmt.execute();

            ResultSet rs = stmt.getGeneratedKeys();
            if (rs.next()) {
                modelo.setId(rs.getInt(1));
            }

            PreparedStatement stmtMotor = connection.prepareStatement(sqlMotor);
            stmtMotor.setInt(1, modelo.getId());
            stmtMotor.setInt(2, modelo.getMotor().getPotencia());
            stmtMotor.setString(3, modelo.getMotor().getTipoCombustivel().name());
            stmtMotor.execute();

            connection.commit();
            return true;
        } catch (SQLException ex) {
            Logger.getLogger(ModeloDAO.class.getName()).log(Level.SEVERE, null, ex);
            try {
                connection.rollback();
            } catch (SQLException ex1) {
                throw new RuntimeException(ex1);
            }
            return false;
        }
    }

    public boolean alterar(Modelo modelo) {
        String sql = "UPDATE modelo SET descricao=?, id_marca=?, categoria=? WHERE id=?";
        String sqlMotor = "UPDATE motor SET potencia=?, tipoCombustivel=? WHERE id_modelo=?";

        try {
            connection.setAutoCommit(false);

            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setString(1, modelo.getDescricao());
            stmt.setInt(2, modelo.getMarca().getId());
            stmt.setString(3, modelo.getCategoria().name());
            stmt.setInt(4, modelo.getId());
            stmt.execute();

            PreparedStatement stmtMotor = connection.prepareStatement(sqlMotor);
            stmtMotor.setInt(1, modelo.getMotor().getPotencia());
            stmtMotor.setString(2, modelo.getMotor().getTipoCombustivel().name());
            stmtMotor.setInt(3, modelo.getId());
            stmtMotor.execute();

            connection.commit();
            return true;
        } catch (SQLException ex) {
            Logger.getLogger(ModeloDAO.class.getName()).log(Level.SEVERE, null, ex);
            try { connection.rollback(); } catch (SQLException ex1) { }
            return false;
        }
    }

    public boolean remover(Modelo modelo) {
        String sqlMotor = "DELETE FROM motor WHERE id_modelo=?";
        String sql = "DELETE FROM modelo WHERE id=?";

        try {
            connection.setAutoCommit(false);

            PreparedStatement stmtMotor = connection.prepareStatement(sqlMotor);
            stmtMotor.setInt(1, modelo.getId());
            stmtMotor.execute();

            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setInt(1, modelo.getId());
            stmt.execute();

            connection.commit();
            return true;
        } catch (SQLException ex) {
            Logger.getLogger(ModeloDAO.class.getName()).log(Level.SEVERE, null, ex);
            try { connection.rollback(); } catch (SQLException ex1) { }
            return false;
        }
    }

    public List<Modelo> listar() {
        String sql = "SELECT * FROM modelo";
        List<Modelo> retorno = new ArrayList<>();
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            ResultSet resultado = stmt.executeQuery();
            while (resultado.next()) {
                Modelo modelo = populateVO(resultado);
                retorno.add(modelo);
            }
        } catch (SQLException ex) {
            Logger.getLogger(ModeloDAO.class.getName()).log(Level.SEVERE, null, ex);
        }
        return retorno;
    }

    public Modelo buscar(Modelo modelo) {
        return buscar(modelo.getId());
    }

    public Modelo buscar(int id) {
        String sql = "SELECT * FROM modelo WHERE id = ?";
        Modelo retorno = new Modelo();
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setInt(1, id);
            ResultSet resultado = stmt.executeQuery();
            if (resultado.next()) {
                retorno = populateVO(resultado);
            }
        } catch (SQLException ex) {
            Logger.getLogger(ModeloDAO.class.getName()).log(Level.SEVERE, null, ex);
        }
        return retorno;
    }

    /**
     * Mapeia os dados relacionais do ResultSet (Banco de Dados) para o Objeto de Domínio.
     */
    private Modelo populateVO(ResultSet rs) throws SQLException {
        Modelo modelo = new Modelo();

        modelo.setId(rs.getInt("id"));
        modelo.setDescricao(rs.getString("descricao"));
        modelo.setCategoria(ECategoria.valueOf(rs.getString("categoria")));

        Marca marca = new Marca();
        marca.setId(rs.getInt("id_marca"));
        MarcaDAO marcaDAO = new MarcaDAO();
        marcaDAO.setConnection(connection);
        marca = marcaDAO.buscar(marca);
        modelo.setMarca(marca);

        // ANOTAÇÃO EXPLICATIVA / ANÁLISE DE FLUXO:
        // Agora que corrigimos o método setMotor(Motor motor) na classe de domínio Modelo,
        // a linha abaixo funciona perfeitamente! O objeto retornado por buscarMotor() é
        // efetivamente guardado dentro do atributo privado da classe Modelo. Sem a correção
        // do setter, a consulta acontecia, mas o resultado era descartado silenciosamente.
        modelo.setMotor(buscarMotor(modelo.getId()));

        return modelo;
    }

    private Motor buscarMotor(int idModelo) throws SQLException {
        String sql = "SELECT * FROM motor WHERE id_modelo = ?";
        PreparedStatement stmt = connection.prepareStatement(sql);
        stmt.setInt(1, idModelo);
        ResultSet rs = stmt.executeQuery();

        if (rs.next()) {
            int potenciaBanco = rs.getInt("potencia");
            ETipoCombustivel combustivelBanco = ETipoCombustivel.valueOf(rs.getString("tipoCombustivel"));

            // Construtor do objeto de composição Motor sendo alimentado com dados persistidos
            Motor motor = new Motor(potenciaBanco, combustivelBanco);
            return motor;
        }
        return null;
    }
}