<?php

declare(strict_types=1);

namespace PhiloQuest\Services;

use PhiloQuest\ExceptionHelper;
use PhiloQuest\Repositories\ConexaoBanco;
use PDO;
use Exception;
use PDOException; 

class AuthService {
    private $conexao;

    public function __construct() {
        $this->conexao = ConexaoBanco::getInstancia()->getConexao();
    }

    /**
     * Métodos Privados de Auxílio
     */
    private function obterDadosMatriculaAutorizada(string $matricula, string $tipoUsuario) {
        $query = "SELECT status, turma_id FROM matriculas_autorizadas WHERE matricula = :matricula AND tipo_usuario = :tipo";
        $stmt = $this->conexao->prepare($query);
        $stmt->bindValue(':matricula', $matricula);
        $stmt->bindValue(':tipo', $tipoUsuario);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function marcarMatriculaComoUtilizada(string $matricula): void {
        $query = "UPDATE matriculas_autorizadas SET status = 'UTILIZADA' WHERE matricula = :matricula";
        $stmt = $this->conexao->prepare($query);
        $stmt->bindValue(':matricula', $matricula);
        $stmt->execute();
    }

    /**
     * Registo de Alunos
     */
    public function registrarAluno(string $matricula, string $nomeCompleto, string $senha, string $codigoTurma): bool {
        try {
            $dadosMatricula = $this->obterDadosMatriculaAutorizada($matricula, 'ALUNO');
            if (!$dadosMatricula || $dadosMatricula['status'] !== 'DISPONIVEL') {
                throw new Exception("Erro: Matrícula não autorizada, já utilizada ou Turma não encontrada.");
            }

            $turmaBanco = trim($dadosMatricula['turma_id'] ?? '');
            $codigoTurmaInput = trim($codigoTurma);
            if ($turmaBanco !== '' && strcasecmp($turmaBanco, $codigoTurmaInput) !== 0) {
                throw new Exception("Erro: Matrícula não autorizada, já utilizada ou Turma não encontrada.");
            }

            $stmtTurma = $this->conexao->prepare("SELECT id FROM turmas WHERE codigo_turma = :codigo");
            $stmtTurma->bindValue(':codigo', $codigoTurmaInput);
            $stmtTurma->execute();
            $turma = $stmtTurma->fetch(PDO::FETCH_ASSOC);

            if (!$turma) {
                throw new Exception("Erro: Matrícula não autorizada, já utilizada ou Turma não encontrada.");
            }

            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $queryInsert = "INSERT INTO usuarios (matricula, nome_completo, senha_hash, tipo_usuario, turma_id) 
                            VALUES (:matricula, :nome, :senha, 'ALUNO', :turma_id)";
            
            $stmtInsert = $this->conexao->prepare($queryInsert);
            $stmtInsert->bindValue(':matricula', $matricula);
            $stmtInsert->bindValue(':nome', $nomeCompleto);
            $stmtInsert->bindValue(':senha', $senhaHash);
            $stmtInsert->bindValue(':turma_id', $turma['id']);
            $stmtInsert->execute();

            $this->marcarMatriculaComoUtilizada($matricula);
            return true;

        } catch (PDOException $e) {
            error_log('AuthService::registrarAluno: ' . $e->getMessage());
            if ((int) $e->getCode() === 23000) {
                throw new Exception('Erro: Esta matrícula já possui um cadastro ativo.');
            }
            throw new Exception('Erro interno ao processar o cadastro. Tente novamente.');
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Registo de Professores
     */
    public function registrarProfessor(string $matricula, string $nomeCompleto, string $senha): bool {
        try {
            $dadosMatricula = $this->obterDadosMatriculaAutorizada($matricula, 'PROFESSOR');
            
            if (!$dadosMatricula) {
                throw new Exception("Erro: Matrícula não encontrada. Solicite ao Administrador a liberação.");
            } elseif ($dadosMatricula['status'] !== 'DISPONIVEL') {
                throw new Exception("Erro: Esta matrícula já foi utilizada.");
            }

            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $queryInsert = "INSERT INTO usuarios (matricula, nome_completo, senha_hash, tipo_usuario) 
                            VALUES (:matricula, :nome, :senha, 'PROFESSOR')";
            
            $stmtInsert = $this->conexao->prepare($queryInsert);
            $stmtInsert->bindValue(':matricula', $matricula);
            $stmtInsert->bindValue(':nome', $nomeCompleto);
            $stmtInsert->bindValue(':senha', $senhaHash);
            $stmtInsert->execute();

            $this->marcarMatriculaComoUtilizada($matricula);
            return true;
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new Exception("Erro: Esta matrícula já possui um cadastro ativo.");
            }
            throw new Exception("Erro interno ao processar o banco de dados.");
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Login do Sistema
     * Ajustado para PhiloQuest - Entrega 21/06
     */
    public function login(string $matricula, string $senha) {
        // Selecionamos também o campo forcar_troca_senha
        $query = "SELECT id, matricula, nome_completo, senha_hash, tipo_usuario, turma_id, forcar_troca_senha 
                  FROM usuarios WHERE matricula = :matricula AND ativo = 1";
        $stmt = $this->conexao->prepare($query);
        $stmt->bindValue(':matricula', $matricula);
        $stmt->execute();
        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            unset($usuario['senha_hash']); // Segurança: nunca trafegar o hash
            return $usuario; 
        }

        return false; 
    }

    /**
     * Regenera o ID da sessão e persiste os dados do utilizador após login válido (anti fixação de sessão).
     *
     * @param array<string, mixed> $usuario
     */
    public function iniciarSessaoPosLogin(array $usuario): void
    {
        session_regenerate_id(true);

        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome_completo'];
        $_SESSION['usuario_matricula'] = $usuario['matricula'] ?? '';
        $_SESSION['usuario_tipo'] = $usuario['tipo_usuario'];
        $_SESSION['usuario_turma'] = $usuario['turma_id'] ?? null;

        unset($_SESSION['usuario_id_reset']);
    }

    /**
     * Regenera o ID da sessão para o fluxo obrigatório de troca de senha.
     */
    public function iniciarSessaoRedefinicaoSenha(int $usuarioId): void
    {
        session_regenerate_id(true);

        unset(
            $_SESSION['usuario_id'],
            $_SESSION['usuario_nome'],
            $_SESSION['usuario_matricula'],
            $_SESSION['usuario_tipo'],
            $_SESSION['usuario_turma']
        );

        $_SESSION['usuario_id_reset'] = $usuarioId;
    }

    /**
     * Ação do Utilizador: Define uma nova senha definitiva
     */
    public function atualizarSenha(int $idUsuario, string $novaSenha): bool {
        try {
            $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            
            // Grava a senha e desativa a flag de troca obrigatória
            $query = "UPDATE usuarios SET senha_hash = :senha, forcar_troca_senha = 0 WHERE id = :id";
            $stmt = $this->conexao->prepare($query);
            $stmt->bindValue(':senha', $senhaHash);
            $stmt->bindValue(':id', $idUsuario, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao atualizar senha: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ação do Administrador: Reseta para a matrícula e obriga troca no login
     */
    public function resetarSenhaParaMatricula(int $idUsuario, string $matricula): bool {
        try {
            // A senha provisória passa a ser a matrícula
            $senhaProvisoriaHash = password_hash($matricula, PASSWORD_DEFAULT);
            
            // Ativa a flag forcar_troca_senha = 1
            $query = "UPDATE usuarios SET senha_hash = :senha, forcar_troca_senha = 1 WHERE id = :id";
            $stmt = $this->conexao->prepare($query);
            $stmt->bindValue(':senha', $senhaProvisoriaHash);
            $stmt->bindValue(':id', $idUsuario, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao resetar senha: " . $e->getMessage());
            return false;
        }
    }
}