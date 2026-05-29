<?php

declare(strict_types=1);

namespace PhiloQuest\Services;

use PhiloQuest\Repositories\ConexaoBanco;
use PDO;
use Exception;
use PDOException;

class AdminService
{
    private PDO $conexao;

    public function __construct()
    {
        $this->conexao = ConexaoBanco::getInstancia()->getConexao();
    }

    public function listarUsuariosGestao(): array
    {
        $sql = "
            SELECT id, nome_completo, matricula, tipo_usuario, ativo
            FROM usuarios
            WHERE tipo_usuario != 'ADMIN'
            UNION ALL
            SELECT NULL as id, 'Aguardando Cadastro...' as nome_completo, matricula,
                   'ALUNO' as tipo_usuario, 2 as ativo
            FROM matriculas_autorizadas
            WHERE status = 'DISPONIVEL' AND tipo_usuario = 'ALUNO'
            ORDER BY ativo ASC, nome_completo ASC
        ";
        $stmt = $this->conexao->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function alterarStatusAcesso(int $idUsuario, bool $ativo): bool
    {
        $stmt = $this->conexao->prepare(
            "UPDATE usuarios SET ativo = :status
             WHERE id = :id AND tipo_usuario IN ('ALUNO', 'PROFESSOR')"
        );
        $stmt->execute([
            ':status' => $ativo ? 1 : 0,
            ':id' => $idUsuario,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Retorna um array com as estatísticas gerais do sistema
     */
    public function obterEstatisticasGerais(): array {
        // 1. Total de Usuários
        $stmt = $this->conexao->query("SELECT COUNT(*) FROM usuarios");
        $totalUsuarios = $stmt->fetchColumn();

        // 2. Total de Professores
        $stmt = $this->conexao->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'PROFESSOR'");
        $totalProfessores = $stmt->fetchColumn();

        // 3. Total de Alunos (Esses são os que já acessaram/cadastraram)
        $stmt = $this->conexao->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'ALUNO'");
        $totalAlunos = $stmt->fetchColumn();

        // 4. CÁLCULO DE ADESÃO (apenas matrículas autorizadas de alunos)
        $stmt = $this->conexao->query(
            "SELECT COUNT(*) FROM matriculas_autorizadas WHERE tipo_usuario = 'ALUNO'"
        );
        $totalMatriculas = $stmt->fetchColumn();

        // Calcula a porcentagem (evitando erro de divisão por zero caso o banco esteja vazio)
        $taxaAdesao = 0;
        if ($totalMatriculas > 0) {
            $taxaAdesao = round(($totalAlunos / $totalMatriculas) * 100);
        }

        return [
            'total_usuarios' => $totalUsuarios,
            'total_professores' => $totalProfessores,
            'total_alunos' => $totalAlunos,
            'total_matriculas' => $totalMatriculas,
            'taxa_adesao' => $taxaAdesao
        ];
    }

    public function autorizarMatriculaProfessor(string $matricula, string $nomeCompleto): bool
    {
        try {
            // CORREÇÃO: Trocado 'id' por 'matricula' no SELECT, pois a tabela não tem coluna id
            $stmt = $this->conexao->prepare("SELECT matricula, status FROM matriculas_autorizadas WHERE matricula = :matricula");
            $stmt->bindValue(':matricula', $matricula);
            $stmt->execute();
            $existente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existente) {
                if ($existente['status'] === 'DISPONIVEL') {
                    throw new Exception("Esta matrícula já está autorizada e aguardando o professor criar a conta.");
                } else {
                    throw new Exception("Esta matrícula já foi utilizada por um professor ativo.");
                }
            }

            // 2. Dupla checagem: Verifica se o professor já existe na tabela final de usuários (aqui tem id)
            $stmtUser = $this->conexao->prepare("SELECT id FROM usuarios WHERE matricula = :matricula");
            $stmtUser->bindValue(':matricula', $matricula);
            $stmtUser->execute();
            if ($stmtUser->fetch()) {
                throw new Exception("Esta matrícula já possui um cadastro definitivo e ativo no sistema.");
            }

            // 3. Insere a autorização
            $query = "INSERT INTO matriculas_autorizadas (matricula, tipo_usuario, status) 
                      VALUES (:matricula, 'PROFESSOR', 'DISPONIVEL')";
            $stmtInsert = $this->conexao->prepare($query);
            $stmtInsert->bindValue(':matricula', $matricula);
            
            return $stmtInsert->execute();

        } catch (PDOException $e) {
            // DICA DE STARTUP: Se der erro de novo, descomente a linha abaixo para vermos o erro real do banco na tela
            // throw new Exception("Erro SQL: " . $e->getMessage()); 
            throw new Exception("Erro de banco de dados ao tentar autorizar a matrícula.");
        } catch (Exception $e) {
            throw $e; 
        }
    }
}