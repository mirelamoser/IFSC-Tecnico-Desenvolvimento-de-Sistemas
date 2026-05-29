<?php

declare(strict_types=1);

namespace PhiloQuest\Repositories;

use PhiloQuest\Entidades\Usuario;
use PhiloQuest\Entidades\Aluno;
use PhiloQuest\Entidades\Professor;
use PhiloQuest\Entidades\Administrador;
use PDO;
use PDOException;

class UsuarioRepository
{
    private PDO $conexao;

    public function __construct()
    {
        $this->conexao = ConexaoBanco::getInstancia()->getConexao();
    }

    public function buscarPorMatricula(string $matricula): ?Usuario
    {
        $sql = "SELECT matricula, nome, senha_hash, tipo_usuario, xp_acumulado, turma_id 
                FROM usuarios 
                WHERE matricula = :matricula LIMIT 1";

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindParam(':matricula', $matricula);
        $stmt->execute();

        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dados) {
            return null;
        }

        if ($dados['tipo_usuario'] === 'ALUNO') {
            $aluno = new Aluno($dados['matricula'], $dados['nome'], $dados['senha_hash']);
            if ($dados['xp_acumulado'] > 0) {
                $aluno->adicionarXp((int) $dados['xp_acumulado']);
            }
            return $aluno;
        }

        if ($dados['tipo_usuario'] === 'PROFESSOR') {
            return new Professor($dados['matricula'], $dados['nome'], $dados['senha_hash']);
        }

        if ($dados['tipo_usuario'] === 'ADMIN') {
            return new Administrador($dados['matricula'], $dados['nome'], $dados['senha_hash']);
        }

        return null;
    }

    public function cadastrarAlunoComTurma(Aluno $aluno, string $codigoTurma): bool
    {
        try {
            $sqlTurma = "SELECT id FROM turmas WHERE codigo_turma = :codigo_turma LIMIT 1";
            $stmtTurma = $this->conexao->prepare($sqlTurma);
            $stmtTurma->bindValue(':codigo_turma', strtoupper($codigoTurma));
            $stmtTurma->execute();

            $turma = $stmtTurma->fetch(PDO::FETCH_ASSOC);

            if (!$turma) {
                return false;
            }

            $sql = "INSERT INTO usuarios (matricula, nome, senha_hash, tipo_usuario, turma_id) 
                    VALUES (:matricula, :nome, :senha_hash, 'ALUNO', :turma_id)";

            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':matricula', $aluno->getMatricula());
            $stmt->bindValue(':nome', $aluno->getNome());
            $stmt->bindValue(':senha_hash', $aluno->getSenhaHash());
            $stmt->bindValue(':turma_id', $turma['id']);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('UsuarioRepository::cadastrarAlunoComTurma: ' . $e->getMessage());
            return false;
        }
    }

    public function cadastrarProfessor(Professor $professor): bool
    {
        try {
            $sql = "INSERT INTO usuarios (matricula, nome, senha_hash, tipo_usuario, turma_id) 
                    VALUES (:matricula, :nome, :senha_hash, 'PROFESSOR', NULL)";

            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':matricula', $professor->getMatricula());
            $stmt->bindValue(':nome', $professor->getNome());
            $stmt->bindValue(':senha_hash', $professor->getSenhaHash());

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('UsuarioRepository::cadastrarProfessor: ' . $e->getMessage());
            return false;
        }
    }
}
