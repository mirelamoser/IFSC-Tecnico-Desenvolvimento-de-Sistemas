<?php

declare(strict_types=1);

namespace PhiloQuest\Services;

use PhiloQuest\Repositories\ConexaoBanco;
use InvalidArgumentException;
use PDO;
use PDOException;

class MatriculaService
{
    private const TAMANHO_LOTE = 200;

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
        $registros = $this->parseCsvMatriculas($caminhoArquivo);
        if ($registros === []) {
            return ['inseridos' => 0, 'duplicados' => 0];
        }

        $mapMatriculaTurma = [];
        $duplicadosNoArquivo = 0;

        foreach ($registros as $registro) {
            if (isset($mapMatriculaTurma[$registro['matricula']])) {
                $duplicadosNoArquivo++;
            }
            $mapMatriculaTurma[$registro['matricula']] = $registro['turma'];
        }

        $matriculas = array_keys($mapMatriculaTurma);
        $turmas = array_values(array_unique(array_values($mapMatriculaTurma)));

        try {
            $this->conexao->beginTransaction();

            $this->garantirTurmasExistem($turmas);

            $existentes = $this->buscarMatriculasExistentes($matriculas);
            $novas = array_values(array_diff($matriculas, $existentes));
            $inseridos = $this->inserirMatriculasEmLote($novas, $mapMatriculaTurma);

            $this->conexao->commit();

            $duplicados = count($matriculas) - $inseridos + $duplicadosNoArquivo;

            return [
                'inseridos' => $inseridos,
                'duplicados' => $duplicados,
            ];
        } catch (PDOException $e) {
            if ($this->conexao->inTransaction()) {
                $this->conexao->rollBack();
            }
            error_log('MatriculaService::importarCsvAlunos: ' . $e->getMessage());
            throw new InvalidArgumentException('Erro ao importar o CSV. Verifique o formato e tente novamente.');
        }
    }

    /**
     * @return list<array{matricula: string, turma: string}>
     */
    private function parseCsvMatriculas(string $caminhoArquivo): array
    {
        $conteudo = file_get_contents($caminhoArquivo);
        if ($conteudo === false || $conteudo === '') {
            return [];
        }

        $conteudo = preg_replace('/^\xEF\xBB\xBF/', '', $conteudo) ?? $conteudo;
        $linhas = preg_split('/\r\n|\n|\r/', $conteudo) ?: [];
        $delimitador = $this->detectarDelimitadorCsv($linhas);
        $registros = [];

        foreach ($linhas as $linha) {
            $linha = trim($linha);
            if ($linha === '' || $this->ehCabecalhoCsv($linha)) {
                continue;
            }

            $dados = str_getcsv($linha, $delimitador);
            $matricula = $this->normalizarMatricula($dados[0] ?? '');
            $turma = $this->normalizarTurma($dados[1] ?? null);

            if ($matricula === '' || $turma === null) {
                continue;
            }

            $registros[] = [
                'matricula' => $matricula,
                'turma' => $turma,
            ];
        }

        return $registros;
    }

    /** @param list<string> $codigosTurma */
    private function garantirTurmasExistem(array $codigosTurma): void
    {
        if ($codigosTurma === []) {
            return;
        }

        $faltantes = array_values(array_diff($codigosTurma, $this->buscarTurmasExistentes($codigosTurma)));
        if ($faltantes === []) {
            return;
        }

        foreach (array_chunk($faltantes, self::TAMANHO_LOTE) as $lote) {
            $placeholders = implode(',', array_fill(0, count($lote), '(?)'));
            $sql = "INSERT IGNORE INTO turmas (codigo_turma) VALUES {$placeholders}";
            $this->conexao->prepare($sql)->execute($lote);
        }
    }

    /**
     * @param list<string> $codigosTurma
     * @return list<string>
     */
    private function buscarTurmasExistentes(array $codigosTurma): array
    {
        $encontradas = [];

        foreach (array_chunk($codigosTurma, self::TAMANHO_LOTE) as $lote) {
            $placeholders = implode(',', array_fill(0, count($lote), '?'));
            $stmt = $this->conexao->prepare(
                "SELECT codigo_turma FROM turmas WHERE codigo_turma IN ({$placeholders})"
            );
            $stmt->execute($lote);
            $encontradas = array_merge($encontradas, $stmt->fetchAll(PDO::FETCH_COLUMN));
        }

        return $encontradas;
    }

    /**
     * @param list<string> $matriculas
     * @return list<string>
     */
    private function buscarMatriculasExistentes(array $matriculas): array
    {
        $encontradas = [];

        foreach (array_chunk($matriculas, self::TAMANHO_LOTE) as $lote) {
            $placeholders = implode(',', array_fill(0, count($lote), '?'));
            $stmt = $this->conexao->prepare(
                "SELECT matricula FROM matriculas_autorizadas WHERE matricula IN ({$placeholders})"
            );
            $stmt->execute($lote);
            $encontradas = array_merge($encontradas, $stmt->fetchAll(PDO::FETCH_COLUMN));
        }

        return $encontradas;
    }

    /**
     * @param list<string> $matriculas
     * @param array<string, string> $mapMatriculaTurma
     */
    private function inserirMatriculasEmLote(array $matriculas, array $mapMatriculaTurma): int
    {
        if ($matriculas === []) {
            return 0;
        }

        $inseridos = 0;

        foreach (array_chunk($matriculas, self::TAMANHO_LOTE) as $lote) {
            $values = [];
            $params = [];

            foreach ($lote as $matricula) {
                $values[] = "(?, 'ALUNO', 'DISPONIVEL', NOW(), ?)";
                $params[] = $matricula;
                $params[] = $mapMatriculaTurma[$matricula];
            }

            $sql = 'INSERT IGNORE INTO matriculas_autorizadas
                    (matricula, tipo_usuario, status, data_cadastro, turma_id)
                    VALUES ' . implode(', ', $values);

            $stmt = $this->conexao->prepare($sql);
            $stmt->execute($params);
            $inseridos += $stmt->rowCount();
        }

        return $inseridos;
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
        return strtoupper(trim($valor));
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
            $this->garantirTurmasExistem([$turma]);
        }

        if ($this->buscarMatriculasExistentes([$matricula]) !== []) {
            return false;
        }

        $stmtIns = $this->conexao->prepare(
            "INSERT INTO matriculas_autorizadas (matricula, tipo_usuario, status, data_cadastro, turma_id)
             VALUES (?, 'ALUNO', 'DISPONIVEL', NOW(), ?)"
        );
        return $stmtIns->execute([$matricula, $turma]);
    }
}
