package br.edu.ifsc.fln.model.dao;

import br.edu.ifsc.fln.model.domain.Cliente;
import br.edu.ifsc.fln.model.domain.PessoaFisica;
import br.edu.ifsc.fln.model.domain.PessoaJuridica;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;
import java.util.logging.Level;
import java.util.logging.Logger;

public class ClienteDAO {

    private Connection connection;

    public Connection getConnection() {
        return connection;
    }

    public void setConnection(Connection connection) {
        this.connection = connection;
    }

    public boolean inserir(Cliente cliente) {
        String sql = "INSERT INTO cliente(nome, celular, email, data_cadastro) VALUES(?, ?, ?, ?)";
        String sqlPF = "INSERT INTO pessoa_fisica(id_cliente, cpf, data_nascimento) VALUES((SELECT max(id) FROM cliente), ?, ?)";
        String sqlPJ = "INSERT INTO pessoa_juridica(id_cliente, cnpj, inscricao_estadual) VALUES((SELECT max(id) FROM cliente), ?, ?)";
        String sqlPT = "INSERT INTO pontuacao(id_cliente, quantidade) VALUES((SELECT max(id) FROM cliente), ?)";
        try {
            connection.setAutoCommit(false);
            //armazena os dados da superclasse
            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setString(1, cliente.getNome());
            stmt.setString(2, cliente.getCelular());
            stmt.setString(3, cliente.getEmail());
            stmt.setDate(4, Date.valueOf(cliente.getDataCadastro()));
            stmt.execute();
            //armazena os dados da subclasse
            if (cliente instanceof PessoaFisica) {
                stmt = connection.prepareStatement(sqlPF);
                stmt.setString(1, ((PessoaFisica) cliente).getCpf());
                stmt.setDate(2, Date.valueOf(((PessoaFisica) cliente).getDataNascimento()));
                stmt.execute();
            } else {
                stmt = connection.prepareStatement(sqlPJ);
                stmt.setString(1, ((PessoaJuridica) cliente).getCnpj());
                stmt.setString(2, ((PessoaJuridica) cliente).getInscricaoEstadual());
                stmt.execute();
            }
            //pontuação
            PreparedStatement stmtPT = connection.prepareStatement(sqlPT);
            connection.setAutoCommit(false);
            stmtPT.setInt(1, cliente.getPontuacao().getQuantidade());
            stmtPT.execute();
            connection.commit();
            return true;
        } catch (SQLException ex) {
            Logger.getLogger(ClienteDAO.class.getName()).log(Level.SEVERE, null, ex);
            try {
                connection.rollback();
                System.out.println("Rollback executado com sucesso!!!");
            } catch (SQLException e) {
                System.out.println("Falha na operação roolback");
                throw new RuntimeException(e);
            }
            return false;
        } finally {
            try {
                connection.setAutoCommit(true);
            } catch (SQLException e) {
                throw new RuntimeException(e);
            }
        }
    }

    public boolean alterar(Cliente cliente) {
        String sql = "UPDATE cliente SET nome=?, celular=?, email=?, data_cadastro=?  WHERE id=?";
        String sqlPF = "UPDATE pessoa_fisica SET cpf=?, data_nascimento=? WHERE id_cliente = ?";
        String sqlPJ = "UPDATE pessoa_juridica SET cnpj=?, inscricao_estadual=? WHERE id_cliente = ?";
        String sqlPT = "UPDATE pontuacao SET quantidade=? WHERE id_cliente=?";
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setString(1, cliente.getNome());
            stmt.setString(2, cliente.getCelular());
            stmt.setString(3, cliente.getEmail());
            stmt.setDate(4, Date.valueOf(cliente.getDataCadastro()));
            stmt.setInt(5, cliente.getId());
            stmt.execute();
            if (cliente instanceof PessoaFisica) {
                stmt = connection.prepareStatement(sqlPF);
                stmt.setString(1, ((PessoaFisica) cliente).getCpf());
                stmt.setDate(2, Date.valueOf(((PessoaFisica) cliente).getDataNascimento()));
                stmt.setInt(3, cliente.getId());
                stmt.execute();
            } else {
                stmt = connection.prepareStatement(sqlPJ);
                stmt.setString(1, ((PessoaJuridica) cliente).getCnpj());
                stmt.setString(2, ((PessoaJuridica) cliente).getInscricaoEstadual());
                stmt.setInt(3, cliente.getId());
                stmt.execute();
            }
            //pontuação
            PreparedStatement stmtPT = connection.prepareStatement(sqlPT);
            stmtPT.setInt(1, cliente.getPontuacao().getQuantidade());
            stmtPT.setInt(2, cliente.getId());
            stmtPT.execute();
            return true;
        } catch (SQLException ex) {
            Logger.getLogger(ClienteDAO.class.getName()).log(Level.SEVERE, null, ex);
            return false;
        }
    }

    public boolean remover(Cliente cliente) {
        String sql = "DELETE FROM cliente WHERE id=?";
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setInt(1, cliente.getId());
            stmt.execute();
            return true;
        } catch (SQLException ex) {
            Logger.getLogger(ClienteDAO.class.getName()).log(Level.SEVERE, null, ex);
            return false;
        }
    }

    public List<Cliente> listar() {
        String sql = "SELECT * FROM cliente c "
                + "LEFT JOIN pessoa_fisica pf on pf.id_cliente = c.id "
                + "LEFT JOIN pessoa_juridica pj on pj.id_cliente = c.id "
                + "LEFT JOIN pontuacao pt on pt.id_cliente = c.id;";
        List<Cliente> retorno = new ArrayList<>();
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            ResultSet resultado = stmt.executeQuery();
            while (resultado.next()) {
                Cliente cliente = populateVO(resultado);
                retorno.add(cliente);
            }
        } catch (SQLException ex) {
            Logger.getLogger(ClienteDAO.class.getName()).log(Level.SEVERE, null, ex);
        }
        return retorno;
    }

    public Cliente buscar(Cliente cliente) {
        String sql = "SELECT * FROM cliente c "
                + "LEFT JOIN pessoa_fisica pf on pf.id_cliente = c.id "
                + "LEFT JOIN pessoa_juridica pj on pj.id_cliente = c.id "
                + "LEFT JOIN pontuacao pt on pt.id_cliente = c.id "
                + "WHERE c.id=?";
        Cliente retorno = null;
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setInt(1, cliente.getId());
            ResultSet resultado = stmt.executeQuery();
            if (resultado.next()) {
                retorno = populateVO(resultado);
            }
        } catch (SQLException ex) {
            Logger.getLogger(ClienteDAO.class.getName()).log(Level.SEVERE, null, ex);
        }
        return retorno;
    }

    public Cliente buscar(int id) {
        String sql = "SELECT * FROM cliente c "
                + "LEFT JOIN pessoa_fisica pf on pf.id_cliente = c.id "
                + "LEFT JOIN pessoa_juridica pj on pj.id_cliente = c.id "
                + "LEFT JOIN pontuacao pt on pt.id_cliente = c.id "
                + "WHERE c.id=?;";
        Cliente retorno = null;
        try {
            PreparedStatement stmt = connection.prepareStatement(sql);
            stmt.setInt(1, id);
            ResultSet resultado = stmt.executeQuery();
            if (resultado.next()) {
                retorno = populateVO(resultado);
            }
        } catch (SQLException ex) {
            Logger.getLogger(ClienteDAO.class.getName()).log(Level.SEVERE, null, ex);
        }
        return retorno;
    }

    private Cliente populateVO(ResultSet rs) throws SQLException {
        Cliente cliente;
        // Verifica se é PF ou PJ
        if (rs.getString("cnpj") == null || rs.getString("cnpj").isEmpty()) {
            // É um cliente pessoa_fisica
            cliente = new PessoaFisica();
            ((PessoaFisica) cliente).setCpf(rs.getString("cpf"));
            // Extração segura da Data de Nascimento
            Date dataNascimentoDB = rs.getDate("data_nascimento");
            if (dataNascimentoDB != null) {
                ((PessoaFisica) cliente).setDataNascimento(dataNascimentoDB.toLocalDate());
            }
        } else {
            // É um cliente pessoa_juridica
            cliente = new PessoaJuridica();
            ((PessoaJuridica) cliente).setCnpj(rs.getString("cnpj"));
            ((PessoaJuridica) cliente).setInscricaoEstadual(rs.getString("inscricao_estadual"));
        }
        // Dados genéricos da Superclasse (Cliente)
        cliente.setId(rs.getInt("id"));
        cliente.setNome(rs.getString("nome"));
        cliente.setCelular(rs.getString("celular"));
        cliente.setEmail(rs.getString("email"));
        // Extração segura da Data de Cadastro
        Date dataCadastroDB = rs.getDate("data_cadastro");
        if (dataCadastroDB != null) {
            cliente.setDataCadastro(dataCadastroDB.toLocalDate());
        }
        // Pontuação
        cliente.getPontuacao().adicionar(rs.getInt("quantidade"));
        // Veículos
        if (this.connection != null) {
            VeiculoDAO veiculoDAO = new VeiculoDAO();
            veiculoDAO.setConnection(this.connection);
            cliente.setVeiculos(veiculoDAO.listarPorCliente(cliente));
        }
        return cliente;
    }
}
