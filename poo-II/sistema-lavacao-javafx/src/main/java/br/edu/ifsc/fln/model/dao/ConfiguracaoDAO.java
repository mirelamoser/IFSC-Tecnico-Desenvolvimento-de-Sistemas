
package br.edu.ifsc.fln.model.dao;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.logging.Level;
import java.util.logging.Logger;

public class ConfiguracaoDAO {
    private Connection connection;

    public Connection getConnection() {
        return connection;
    }

    public void setConnection(Connection connection) {
        this.connection = connection;
    }

    //Metodo para buscar o valor global de pontos
    public int buscarPontosServico() {
        String sql = "SELECT pontos_servico FROM configuracoes WHERE id = 1";
        int pontos = 0; // valor padrão caso dê erro

        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            ResultSet resultado = stmt.executeQuery();

            if (resultado.next()) {
                pontos = resultado.getInt("pontos_servico");
            }
        } catch (SQLException ex) {
            Logger.getLogger(ConfiguracaoDAO.class.getName()).log(Level.SEVERE, null, ex);
        }

        return pontos;
    }

    //Metodo extra caso você queira alterar os pontos futuramente via sistema
    public boolean atualizarPontosServico(int novosPontos) {
        String sql = "UPDATE configuracoes SET pontos_servico = ? WHERE id = 1";

        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setInt(1, novosPontos);
            stmt.execute();
            return true;
        } catch (SQLException ex) {
            Logger.getLogger(ConfiguracaoDAO.class.getName()).log(Level.SEVERE, null, ex);
            return false;
        }
    }
}
