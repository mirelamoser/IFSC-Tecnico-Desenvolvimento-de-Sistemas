<?php

declare(strict_types=1);

namespace PhiloQuest\Services;

use PhiloQuest\Repositories\ConexaoBanco;
use InvalidArgumentException;
use PDO;
use PDOException;
class MatriculaService
{
    private PDO $conexao;

    public function __construct()
    {
        $this->conexao = ConexaoBanco::getInstancia()->getConexao();
    }

    public function obterEstatisticasAlunos(): array
    {
        $stmt = $this->conexao->query(
            "SELECT COUNT(*) as total,
                    SUM(CASE WHEN status = 'DISPONIVEL' THEN 1 ELSE 0 END) as disponiveis
             FROM matriculas_autorizadas WHERE tipo_usuario = 'ALUNO'"
        );
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'disponiveis' => 0];
    }

    public function listarMatriculasAlunos(int $limite = 50): array
    {
        $stmt = $this->conexao->prepare(
            "SELECT matricula, status, data_cadastro, turma_id
             FROM matriculas_autorizadas
             WHERE tipo_usuario = 'ALUNO'
             ORDER BY data_cadastro DESC
             LIMIT :limite"
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function adicionarMatriculaAluno(string $matricula, ?string $turma): bool
    {
        $matricula = trim($matricula);
        if ($matricula === '') {
            throw new InvalidArgumentException('A matrícula é obrigatória.');
        }
        $turma = $this->normalizarTurma($turma);
        $this->validarTurmaObrigatoriaAluno($turma);

        return $this->inserirSeNaoExistir($matricula, $turma);
    }

    /** @return array{inseridos: int, duplicados: int} */
    public function importarCsvAlunos(string $caminhoArquivo): array
    {
        $conteudo = file_get_contents($caminhoArquivo);
        if ($conteudo === false || $conteudo === '') {
            return ['inseridos' => 0, 'duplicados' => 0];
        }

        $conteudo = preg_replace('/^\xEF\xBB\xBF/', '', $conteudo) ?? $conteudo;
        $linhas = preg_split('/\r\n|\n|\r/', $conteudo) ?: [];
        $delimitador = $this->detectarDelimitadorCsv($linhas);

        $inseridos = 0;
        $duplicados = 0;

        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if ($linha === '') {
                continue;
            }

            if ($this->ehCabecalhoCsv($linha)) {
                continue;
            }

            $dados = str_getcsv($linha, $delimitador);
            $matricula = $this->normalizarMatricula($dados[0] ?? '');
            $turma = $this->normalizarTurma($dados[1] ?? null);

            if ($matricula === '') {
                continue;
            }

            if ($turma === null) {
                continue;
            }

            if ($this->inserirSeNaoExistir($matricula, $turma)) {
                $inseridos++;
            } else {
                $duplicados++;
            }
        }

        return ['inseridos' => $inseridos, 'duplicados' => $duplicados];
    }

    /** @param array<int, string> $linhas */
    private function detectarDelimitadorCsv(array $linhas): string
    {
        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if ($linha === '') {
                continue;
            }
            if (str_contains($linha, ';')) {
                return ';';
            }
            if (str_contains($linha, ',')) {
                return ',';
            }
            break;
        }
        return ';';
    }

    private function ehCabecalhoCsv(string $linha): bool
    {
        $lower = mb_strtolower($linha);
        return str_contains($lower, 'matricula') || str_contains($lower, 'matrícula');
    }

    private function normalizarMatricula(string $valor): string
    {
        return preg_replace('/\D/', '', trim($valor)) ?? '';
    }

    private function normalizarTurma(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }
        $turma = strtoupper(trim($valor));
        return $turma !== '' ? $turma : null;
    }

    public function excluirMatriculaAluno(string $matricula): bool
    {
        $stmt = $this->conexao->prepare(
            "DELETE FROM matriculas_autorizadas WHERE matricula = :m AND tipo_usuario = 'ALUNO'"
        );
        $stmt->execute([':m' => trim($matricula)]);
        return $stmt->rowCount() > 0;
    }

    public function limparMatriculasAlunos(): void
    {
        $this->conexao->exec("DELETE FROM matriculas_autorizadas WHERE tipo_usuario = 'ALUNO'");
    }

    public function listarProfessoresAutorizados(): array
    {
        $stmt = $this->conexao->query(
            "SELECT matricula, status, data_cadastro
             FROM matriculas_autorizadas
             WHERE tipo_usuario = 'PROFESSOR'
             ORDER BY data_cadastro DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function excluirAutorizacaoProfessor(string $matricula): bool
    {
        $stmt = $this->conexao->prepare(
            "DELETE FROM matriculas_autorizadas WHERE matricula = :m AND tipo_usuario = 'PROFESSOR'"
        );
        $stmt->execute([':m' => trim($matricula)]);
        return $stmt->rowCount() > 0;
    }

    private function validarTurmaObrigatoriaAluno(?string $turma): void
    {
        if ($turma === null || $turma === '') {
            throw new InvalidArgumentException(
                'A turma é obrigatória para cadastrar matrícula de aluno.'
            );
        }
    }

    private function inserirSeNaoExistir(string $matricula, ?string $turma): bool
    {
        $this->validarTurmaObrigatoriaAluno($turma);

        if ($turma !== null && $turma !== '') {
            $stmtCheck = $this->conexao->prepare('SELECT codigo_turma FROM turmas WHERE codigo_turma = ?');
            $stmtCheck->execute([$turma]);
            if (!$stmtCheck->fetch()) {
                $stmtIns = $this->conexao->prepare('INSERT INTO turmas (codigo_turma) VALUES (?)');
                $stmtIns->execute([$turma]);
            }
        }

        $stmtMat = $this->conexao->prepare(
            'SELECT matricula FROM matriculas_autorizadas WHERE matricula = ?'
        );
        $stmtMat->execute([$matricula]);
        if ($stmtMat->fetch()) {
            return false;
        }

        $stmtIns = $this->conexao->prepare(
            "INSERT INTO matriculas_autorizadas (matricula, tipo_usuario, status, data_cadastro, turma_id)
             VALUES (?, 'ALUNO', 'DISPONIVEL', NOW(), ?)"
        );
        return $stmtIns->execute([$matricula, $turma]);
    }
}
