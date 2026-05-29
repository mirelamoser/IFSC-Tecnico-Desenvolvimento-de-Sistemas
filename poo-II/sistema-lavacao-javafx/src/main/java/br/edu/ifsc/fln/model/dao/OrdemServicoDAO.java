package br.edu.ifsc.fln.model.dao;

import br.edu.ifsc.fln.model.domain.EStatus;
import br.edu.ifsc.fln.model.domain.ItemOS;
import br.edu.ifsc.fln.model.domain.OrdemServico;
import br.edu.ifsc.fln.model.domain.Servico;
import br.edu.ifsc.fln.model.domain.Veiculo;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.sql.Date;
import java.util.ArrayList;
import java.util.List;
import java.util.logging.Level;
import java.util.logging.Logger;

/**
 * Classe DAO para a entidade OrdemServico.
 * Concentra as operações da OS e de seus respectivos Itens (ItemOS).
 */
public class OrdemServicoDAO {

    private Connection connection;

    public Connection getConnection() {
        return connection;
    }

    public void setConnection(Connection connection) {
        this.connection = connection;
    }

    /**
     * Insere uma Ordem de Serviço e, logo em seguida, seus itens vinculados.
     */
    public boolean inserir(OrdemServico os) {
        String sqlOS = "INSERT INTO ordem_servico(total, agenda, desconto, status, id_veiculo) VALUES(?,?,?,?,?)";
        String sqlItem = "INSERT INTO item_os(id_ordem_servico, id_servico, valor_servico, observacoes) VALUES(?,?,?,?)";

        try {
            // Precisamos passar 'Statement.RETURN_GENERATED_KEYS' porque o ID (numero) da OS
            // é gerado pelo banco (AUTO_INCREMENT). Precisamos desse número para inserir os itens depois
            PreparedStatement stmtOS = connection.prepareStatement(sqlOS, Statement.RETURN_GENERATED_KEYS);
            stmtOS.setDouble(1, os.getTotal());
            stmtOS.setDate(2, Date.valueOf(os.getAgenda()));
            stmtOS.setDouble(3, os.getDesconto());
            stmtOS.setString(4, os.getStatus().name());
            stmtOS.setInt(5, os.getVeiculo().getId());
            stmtOS.execute();

            // Recupera o ID gerado para a Ordem de Serviço
            ResultSet rsKeys = stmtOS.getGeneratedKeys();
            if (rsKeys.next()) {
                long numeroGerado = rsKeys.getLong(1);
                os.setNumero(numeroGerado); // Atualiza o objeto Java com o número do banco

                // Agora que temos o número da OS, inserimos os itens da lista
                for (ItemOS item : os.getItemOSList()) {
                    PreparedStatement stmtItem = connection.prepareStatement(sqlItem);
                    stmtItem.setLong(1, os.getNumero());
                    stmtItem.setInt(2, item.getServico().getId());
                    stmtItem.setDouble(3, item.getValorServico());
                    stmtItem.setString(4, item.getObservacoes());
                    stmtItem.execute();
                }
            }
            return true;
        } catch (SQLException ex) {
            Logger.getLogger(OrdemServicoDAO.class.getName()).log(Level.SEVERE, null, ex);
            return false;
        }
    }

    /**
     * Atualiza os dados da OS. Para os itens, remove os antigos e insere os atuais.
     */
    public boolean alterar(OrdemServico os) {
        String sqlOS = "UPDATE ordem_servico SET total=?, agenda=?, desconto=?, status=?, id_veiculo=? WHERE numero=?";
        String sqlDeleteItens = "DELETE FROM item_os WHERE id_ordem_servico=?";
        String sqlInsertItem = "INSERT INTO item_os(id_ordem_servico, id_servico, valor_servico, observacoes) VALUES(?,?,?,?)";

        try {
            // 1. Atualiza os dados principais da Ordem de Serviço
            PreparedStatement stmtOS = connection.prepareStatement(sqlOS);
            stmtOS.setDouble(1, os.getTotal());
            stmtOS.setDate(2, Date.valueOf(os.getAgenda()));
            stmtOS.setDouble(3, os.getDesconto());
            stmtOS.setString(4, os.getStatus().name());
            stmtOS.setInt(5, os.getVeiculo().getId());
            stmtOS.setLong(6, os.getNumero());
            stmtOS.execute();

            // 2. Limpa os itens antigos dessa OS no banco para evitar duplicidade ou conflitos
            PreparedStatement stmtDel = connection.prepareStatement(sqlDeleteItens);
            stmtDel.setLong(1, os.getNumero());
            stmtDel.execute();

            // 3. Reinsere os itens que estão atualmente na lista da OS
            for (ItemOS item : os.getItemOSList()) {
                PreparedStatement stmtItem = connection.prepareStatement(sqlInsertItem);
                stmtItem.setLong(1, os.getNumero());
                stmtItem.setInt(2, item.getServico().getId());
                stmtItem.setDouble(3, item.getValorServico());
                stmtItem.setString(4, item.getObservacoes());
                stmtItem.execute();
            }
            return true;
        } catch (SQLException ex) {
            Logger.getLogger(OrdemServicoDAO.class.getName()).log(Level.SEVERE, null, ex);
            return false;
        }
    }

    /**
     * Remove uma ordem de serviço.
     * Graças ao ON DELETE CASCADE que colocamos no banco, os itens serão apagados automaticamente!
     */
    public boolean remover(OrdemServico os) {
        String sql = "DELETE FROM ordem_servico WHERE numero=?";
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setLong(1, os.getNumero());
            stmt.execute();
            return true;
        } catch (SQLException ex) {
            Logger.getLogger(OrdemServicoDAO.class.getName()).log(Level.SEVERE, null, ex);
            return false;
        }
    }

    /**
     * Lista todas as ordens de serviço, trazendo também os seus respectivos itens preenchidos.
     */
    public List<OrdemServico> listar() {
        String sqlOS = "SELECT * FROM ordem_servico";
        List<OrdemServico> retorno = new ArrayList<>();
        try {
            PreparedStatement stmtOS = connection.prepareStatement(sqlOS);
            ResultSet resultadoOS = stmtOS.executeQuery();
            while (resultadoOS.next()) {
                // Instancia a OS e define os atributos básicos
                // Passamos os parâmetros do nosso construtor aqui
                Veiculo veiculoShell = new Veiculo();
                veiculoShell.setId(resultadoOS.getInt("id_veiculo")); // Objeto temporário apenas com o ID

                OrdemServico os = new OrdemServico(
                        resultadoOS.getLong("numero"),
                        resultadoOS.getDate("agenda").toLocalDate(),
                        resultadoOS.getDouble("desconto"),
                        veiculoShell,
                        EStatus.valueOf(resultadoOS.getString("status"))
                );
                os.setTotal(resultadoOS.getDouble("total"));

                // Para cada OS encontrada, vamos carregar os itens dela
                carregarItensDaOrdem(os);

                retorno.add(os);
            }
        } catch (SQLException ex) {
            Logger.getLogger(OrdemServicoDAO.class.getName()).log(Level.SEVERE, null, ex);
        }
        return retorno;
    }

    /**
     * Busca uma única OS através de seu número identificador.
     */
    public OrdemServico buscar(long numero) {
        String sql = "SELECT * FROM ordem_servico WHERE numero=?";
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setLong(1, numero);
            ResultSet resultadoOS = stmt.executeQuery();
            if (resultadoOS.next()) {
                Veiculo veiculoShell = new Veiculo();
                veiculoShell.setId(resultadoOS.getInt("id_veiculo"));

                OrdemServico os = new OrdemServico(
                        resultadoOS.getLong("numero"),
                        resultadoOS.getDate("agenda").toLocalDate(),
                        resultadoOS.getDouble("desconto"),
                        veiculoShell,
                        EStatus.valueOf(resultadoOS.getString("status"))
                );
                os.setTotal(resultadoOS.getDouble("total"));

                carregarItensDaOrdem(os);
                return os;
            }
        } catch (SQLException ex) {
            Logger.getLogger(OrdemServicoDAO.class.getName()).log(Level.SEVERE, null, ex);
        }
        return null;
    }

    /**
     * Metodo auxiliar interno para buscar os itens de uma OS e acoplá-los ao objeto.
     */
    private void carregarItensDaOrdem(OrdemServico os) throws SQLException {
        // Fazemos um JOIN com a tabela de serviços para já trazer os dados do serviço executado
        String sqlItens = "SELECT ios.*, s.descricao, s.valor, s.categoria " +
                "FROM item_os ios " +
                "INNER JOIN servico s ON s.id = ios.id_servico " +
                "WHERE ios.id_ordem_servico = ?";

        PreparedStatement stmtItem = connection.prepareStatement(sqlItens);
        stmtItem.setLong(1, os.getNumero());
        ResultSet rsItem = stmtItem.executeQuery();

        while (rsItem.next()) {
            // Recria o objeto Servico que estava dentro do item
            Servico servico = new Servico();
            servico.setId(rsItem.getInt("id_servico"));
            servico.setDescricao(rsItem.getString("descricao"));
            servico.setValor(rsItem.getDouble("valor"));
            // (Se houver categoria mapeada no enum, você pode tratar aqui como no ServicoDAO)

            // Instancia o ItemOS ligando-o à Ordem de Serviço atual e ao Serviço encontrado
            ItemOS item = new ItemOS(
                    rsItem.getDouble("valor_servico"),
                    rsItem.getString("observacoes"),
                    os,
                    servico
            );

            // Adiciona o item à lista interna da OS usando o metodo que criamos
            os.addItemOS(item);
        }
    }
}