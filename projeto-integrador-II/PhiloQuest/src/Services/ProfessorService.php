<?php

declare(strict_types=1);

namespace PhiloQuest\Services;

use PhiloQuest\ExceptionHelper;
use PhiloQuest\Enum\StatusSubmissao;
use PhiloQuest\Repositories\ConexaoBanco;
use InvalidArgumentException;
use PDO;
use Exception;
use PDOException;

class ProfessorService
{
    private PDO $conexao;

    public function __construct()
    {
        $this->conexao = ConexaoBanco::getInstancia()->getConexao();
    }

    public function obterEstatisticasProf(int $professorId): array
    {
        try {
            $stmt = $this->conexao->prepare('SELECT COUNT(*) FROM turmas WHERE professor_id = :id');
            $stmt->execute([':id' => $professorId]);
            $totalTurmas = (int) ($stmt->fetchColumn() ?: 0);

            $stmt = $this->conexao->prepare(
                "SELECT COUNT(DISTINCT u.id) FROM usuarios u
                 INNER JOIN turmas t ON u.turma_id = t.id
                 WHERE t.professor_id = :id AND u.tipo_usuario = 'ALUNO'"
            );
            $stmt->execute([':id' => $professorId]);
            $totalAlunos = (int) ($stmt->fetchColumn() ?: 0);

            $stmt = $this->conexao->prepare(
                "SELECT COUNT(*) FROM submissoes_etapa se
                 INNER JOIN usuarios u ON se.aluno_id = u.id
                 INNER JOIN turmas t ON u.turma_id = t.id
                 WHERE t.professor_id = :id AND se.status = 'AGUARDANDO_VALIDACAO' AND se.etapa_id < 5"
            );
            $stmt->execute([':id' => $professorId]);
            $validacoesPendentes = (int) ($stmt->fetchColumn() ?: 0);

            $stmt = $this->conexao->prepare(
                "SELECT COUNT(*) FROM submissoes_etapa se
                 INNER JOIN usuarios u ON se.aluno_id = u.id
                 INNER JOIN turmas t ON u.turma_id = t.id
                 WHERE t.professor_id = :id AND se.status = 'AGUARDANDO_VALIDACAO' AND se.etapa_id = 5"
            );
            $stmt->execute([':id' => $professorId]);
            $trabalhosParaAvaliar = (int) ($stmt->fetchColumn() ?: 0);

            $stmt = $this->conexao->prepare(
                "SELECT COUNT(*) FROM (
                    SELECT se.aluno_id
                    FROM submissoes_etapa se
                    INNER JOIN usuarios u ON se.aluno_id = u.id
                    INNER JOIN turmas t ON u.turma_id = t.id
                    WHERE t.professor_id = :id
                      AND u.tipo_usuario = 'ALUNO'
                      AND se.status IN ('APROVADO', 'APROVADO_BEM_FEITO', 'APROVADO_EXCELENTE')
                    GROUP BY se.aluno_id
                    HAVING COUNT(DISTINCT se.etapa_id) >= 5
                ) AS ciclos_completos"
            );
            $stmt->execute([':id' => $professorId]);
            $ciclosCompletados = (int) ($stmt->fetchColumn() ?: 0);

            return [
                'total_turmas' => $totalTurmas,
                'total_alunos' => $totalAlunos,
                'validacoes_pendentes' => $validacoesPendentes,
                'trabalhos_para_avaliar' => $trabalhosParaAvaliar,
                'ciclos_completados' => $ciclosCompletados,
            ];
        } catch (PDOException $e) {
            throw ExceptionHelper::fromPdo($e, 'Erro ao obter estatísticas.');
        }
    }

    public function listarTurmasComContagemAlunos(int $professorId): array
    {
        $stmt = $this->conexao->prepare(
            "SELECT t.id, t.codigo_turma, t.criacao, COUNT(u.id) AS total_alunos
             FROM turmas t
             LEFT JOIN usuarios u ON u.turma_id = t.id AND u.tipo_usuario = 'ALUNO'
             WHERE t.professor_id = :id
             GROUP BY t.id, t.codigo_turma, t.criacao
             ORDER BY t.criacao DESC"
        );
        $stmt->execute([':id' => $professorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna a lista de etapas aguardando validação, com filtro opcional por turma
     */
    public function listarValidacoesPendentes(int $professorId, ?int $turmaId = null): array {
        try {
            $sql = "SELECT se.id, u.nome_completo AS aluno_nome, u.matricula AS aluno_matricula,
                           se.etapa_id AS numero_etapa, se.data_submissao AS data,
                           se.titulo_submissao AS titulo, se.descricao_submissao AS texto,
                           t.codigo_turma
                    FROM submissoes_etapa se
                    INNER JOIN usuarios u ON se.aluno_id = u.id
                    INNER JOIN turmas t ON t.id = COALESCE(se.turma_id, u.turma_id)
                    WHERE t.professor_id = :prof_id AND se.status = 'AGUARDANDO_VALIDACAO' AND se.etapa_id < 5";
            
            // Adiciona o filtro de turma se o ID for passado
            if ($turmaId) {
                $sql .= " AND t.id = :turma_id";
            }

            $sql .= " ORDER BY se.data_submissao ASC";
                
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':prof_id', $professorId, PDO::PARAM_INT);
            
            // Faz o bind dinâmico do ID da turma
            if ($turmaId) {
                $stmt->bindValue(':turma_id', $turmaId, PDO::PARAM_INT);
            }
            
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            if ($this->conexao->inTransaction()) {
                $this->conexao->rollBack();
            }
            error_log('ProfessorService::listarValidacoesPendentes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Trabalhos finais (Etapa 5) aguardando validação.
     */
    public function listarTrabalhosPendentes(int $professorId, ?int $turmaId = null): array
    {
        try {
            $sql = "SELECT se.id, u.nome_completo AS aluno_nome, u.matricula AS aluno_matricula,
                           se.etapa_id AS numero_etapa, se.data_submissao AS data,
                           se.titulo_submissao AS titulo, se.descricao_submissao AS texto,
                           t.codigo_turma
                    FROM submissoes_etapa se
                    INNER JOIN usuarios u ON se.aluno_id = u.id
                    INNER JOIN turmas t ON t.id = COALESCE(se.turma_id, u.turma_id)
                    WHERE t.professor_id = :prof_id AND se.status = 'AGUARDANDO_VALIDACAO' AND se.etapa_id = 5";

            if ($turmaId) {
                $sql .= " AND t.id = :turma_id";
            }

            $sql .= " ORDER BY se.data_submissao ASC";

            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':prof_id', $professorId, PDO::PARAM_INT);

            if ($turmaId) {
                $stmt->bindValue(':turma_id', $turmaId, PDO::PARAM_INT);
            }

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function listarTurmasProf(int $professorId): array
    {
        return $this->listarTurmasComContagemAlunos($professorId);
    }

    public function listarAlunosDaTurma(int $turmId): array {
        try {
            $stmt = $this->conexao->prepare("SELECT u.id, u.matricula, u.nome_completo, u.criacao FROM usuarios u WHERE u.turma_id = :turma_id AND u.tipo_usuario = 'ALUNO' ORDER BY u.nome_completo ASC");
            $stmt->bindValue(':turma_id', $turmId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (PDOException $e) { 
            throw ExceptionHelper::fromPdo($e, 'Erro ao listar alunos.');
        }
    }

    public function obterAtividadeRecente(int $professorId, int $limite = 5): array { 
        return []; 
    }

    public function vincularTurma(int $professorId, string $codigoTurma): bool {
        try {
            $stmt = $this->conexao->prepare("SELECT id, professor_id FROM turmas WHERE codigo_turma = :codigo");
            $stmt->bindValue(':codigo', strtoupper($codigoTurma));
            $stmt->execute();
            $turma = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$turma) throw new Exception("Turma não encontrada.");
            if ($turma['professor_id'] !== null) {
                if ((int)$turma['professor_id'] === $professorId) return true;
                throw new Exception("Turma vinculada a outro professor.");
            }
            
            $stmtUpdate = $this->conexao->prepare("UPDATE turmas SET professor_id = :prof_id WHERE id = :turma_id");
            $stmtUpdate->bindValue(':prof_id', $professorId, PDO::PARAM_INT);
            $stmtUpdate->bindValue(':turma_id', $turma['id'], PDO::PARAM_INT);
            $stmtUpdate->execute();
            return true;
        } catch (PDOException $e) { 
            throw ExceptionHelper::fromPdo($e, 'Erro ao processar a operação.');
        }
    }

    public function obterTurmaPorId(int $turmaId): array {
        try {
            $stmt = $this->conexao->prepare("SELECT id, codigo_turma, professor_id, criacao FROM turmas WHERE id = :turma_id");
            $stmt->bindValue(':turma_id', $turmaId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
        } catch (PDOException $e) { 
            throw ExceptionHelper::fromPdo($e, 'Erro ao processar a operação.');
        }
    }

    public function obterProgressoAluno(int $alunoId): array {
        $lote = $this->obterProgressoAlunosEmLote([$alunoId]);
        return $lote[$alunoId] ?? [
            'nivel' => null,
            'percentual_progresso' => 0,
            'etapa_atual' => 0,
            'total_etapas' => $this->obterTotalEtapasSistema(),
        ];
    }

    /**
     * @param list<int> $alunoIds
     * @return array<int, array{nivel: ?int, percentual_progresso: float|int, etapa_atual: int, total_etapas: int}>
     */
    public function obterProgressoAlunosEmLote(array $alunoIds): array
    {
        $alunoIds = array_values(array_unique(array_filter(
            array_map(static fn ($id) => (int) $id, $alunoIds),
            static fn (int $id) => $id > 0
        )));

        if ($alunoIds === []) {
            return [];
        }

        $totalEtapas = $this->obterTotalEtapasSistema();
        $resultado = [];

        foreach ($alunoIds as $id) {
            $resultado[$id] = [
                'nivel' => null,
                'percentual_progresso' => 0,
                'etapa_atual' => 0,
                'total_etapas' => $totalEtapas,
            ];
        }

        $placeholders = implode(',', array_fill(0, count($alunoIds), '?'));
        $sql = "SELECT aluno_id, MAX(etapa_id) AS ultima_etapa
                FROM submissoes_etapa
                WHERE aluno_id IN ({$placeholders}) AND status LIKE 'APROVADO%'
                GROUP BY aluno_id";

        $stmt = $this->conexao->prepare($sql);
        $stmt->execute($alunoIds);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $id = (int) $row['aluno_id'];
            $ultimaEtapaAprovada = (int) $row['ultima_etapa'];
            if ($ultimaEtapaAprovada === 0) {
                continue;
            }

            $percentual = ($ultimaEtapaAprovada / $totalEtapas) * 100;
            if ($percentual > 100) {
                $percentual = 100;
            }

            $resultado[$id] = [
                'nivel' => $ultimaEtapaAprovada,
                'percentual_progresso' => round($percentual),
                'etapa_atual' => $ultimaEtapaAprovada,
                'total_etapas' => $totalEtapas,
            ];
        }

        return $resultado;
    }

    private function obterTotalEtapasSistema(): int
    {
        try {
            $stmtTotal = $this->conexao->query('SELECT COUNT(*) FROM etapas');
            $totalEtapas = (int) $stmtTotal->fetchColumn();
            return $totalEtapas > 0 ? $totalEtapas : 5;
        } catch (PDOException $e) {
            return 5;
        }
    }

    public function listarTurmasDisponiveis(): array {
        try {
            $stmt = $this->conexao->prepare("SELECT id, codigo_turma, criacao FROM turmas WHERE professor_id IS NULL ORDER BY criacao DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
        } catch (PDOException $e) { 
            throw ExceptionHelper::fromPdo($e, 'Erro ao processar a operação.');
        }
    }

    /**
     * Busca todos os detalhes de uma submissão específica (uso interno).
     *
     * @return array<string, mixed>|false
     */
    public function obterSubmissaoPorId(int $submissaoId): array|false
    {
        $sql = "SELECT se.*, u.nome_completo AS aluno_nome, 
                       COALESCE(e.titulo, 'Atividade do Ciclo') AS etapa_titulo, 
                       se.etapa_id AS numero_etapa
                FROM submissoes_etapa se
                INNER JOIN usuarios u ON se.aluno_id = u.id
                LEFT JOIN etapas e ON se.etapa_id = e.id
                WHERE se.id = :id";

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':id', $submissaoId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : false;
    }

    /**
     * Submissão acessível apenas se o aluno pertence a turma do professor logado (anti-IDOR).
     *
     * @return array<string, mixed>|null
     */
    /**
     * Conteúdo estruturado da Etapa 3 para avaliação pelo professor.
     *
     * @return array{pergunta_numero: int, pergunta_texto: string, resposta_conceitual: string, conceitos: list<string>}|null
     */
    public function obterConteudoEtapa3PorSubmissao(int $submissaoId): ?array
    {
        try {
            $stmt = $this->conexao->prepare(
                "SELECT r.id AS resposta_id, r.pergunta_numero, r.pergunta_texto, r.resposta_conceitual
                 FROM respostas_etapa3 r
                 WHERE r.submissao_etapa_id = :submissao_id
                 LIMIT 1"
            );
            $stmt->bindValue(':submissao_id', $submissaoId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($row === false) {
                return null;
            }

            $stmtConceitos = $this->conexao->prepare(
                "SELECT termo FROM conceitos_chave
                 WHERE resposta_etapa3_id = :id
                 ORDER BY ordem ASC"
            );
            $stmtConceitos->bindValue(':id', (int) $row['resposta_id'], PDO::PARAM_INT);
            $stmtConceitos->execute();
            $conceitos = array_column($stmtConceitos->fetchAll(PDO::FETCH_ASSOC), 'termo');
            $stmtConceitos->closeCursor();

            return [
                'pergunta_numero' => (int) $row['pergunta_numero'],
                'pergunta_texto' => (string) $row['pergunta_texto'],
                'resposta_conceitual' => (string) $row['resposta_conceitual'],
                'conceitos' => $conceitos,
            ];
        } catch (PDOException $e) {
            error_log('ProfessorService::obterConteudoEtapa3PorSubmissao: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Conceitos da Etapa 3 com filósofos associados na submissão da Etapa 4.
     *
     * @return list<array{conceito_id: int, termo: string, filosofos: list<array{nome: string, epoca: string, epoca_rotulo: string, linha_pensamento: string, ideias_principais: string, citacao: string}>}>
     */
    public function obterConteudoEtapa4PorSubmissao(int $submissaoId): array
    {
        try {
            $sql = "SELECT ck.id AS conceito_id, ck.termo, ck.ordem,
                           f.nome, f.epoca, f.linha_pensamento, f.ideias_principais, f.citacao, f.id AS filosofo_id
                    FROM conceito_filosofo cf
                    INNER JOIN conceitos_chave ck ON ck.id = cf.conceito_chave_id
                    INNER JOIN filosofos_etapa4 f ON f.id = cf.filosofo_id
                    WHERE f.submissao_etapa_id = :sid
                    ORDER BY ck.ordem ASC, f.id ASC";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':sid', $submissaoId, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $agrupado = [];
            foreach ($rows as $row) {
                $conceitoId = (int) $row['conceito_id'];
                if (!isset($agrupado[$conceitoId])) {
                    $agrupado[$conceitoId] = [
                        'conceito_id' => $conceitoId,
                        'termo' => (string) $row['termo'],
                        'filosofos' => [],
                    ];
                }

                $epoca = (string) $row['epoca'];
                $agrupado[$conceitoId]['filosofos'][] = [
                    'nome' => (string) $row['nome'],
                    'epoca' => $epoca,
                    'epoca_rotulo' => \PhiloQuest\Etapa4FilosofoHelper::rotuloEpoca($epoca),
                    'linha_pensamento' => (string) $row['linha_pensamento'],
                    'ideias_principais' => (string) $row['ideias_principais'],
                    'citacao' => (string) $row['citacao'],
                ];
            }

            return array_values($agrupado);
        } catch (PDOException $e) {
            error_log('ProfessorService::obterConteudoEtapa4PorSubmissao: ' . $e->getMessage());
            return [];
        }
    }

    public function obterSubmissaoDoProfessor(int $submissaoId, int $professorId): ?array
    {
        $sql = "SELECT se.*, u.nome_completo AS aluno_nome, u.matricula AS aluno_matricula,
                       COALESCE(e.titulo, 'Atividade do Ciclo') AS etapa_titulo,
                       se.etapa_id AS numero_etapa
                FROM submissoes_etapa se
                INNER JOIN usuarios u ON se.aluno_id = u.id
                INNER JOIN turmas t ON t.id = COALESCE(se.turma_id, u.turma_id)
                LEFT JOIN etapas e ON se.etapa_id = e.id
                WHERE se.id = :id AND t.professor_id = :prof_id";

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':id', $submissaoId, PDO::PARAM_INT);
        $stmt->bindValue(':prof_id', $professorId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * Atualiza o status, salva feedback, nota (Etapa 5) e distribui o XP do Enum
     *
     * @param float|null $nota Nota de 0 a 10 — obrigatória ao aprovar Etapa 5; ignorada nas demais etapas
     */
    public function avaliarSubmissao(
        int $submissaoId,
        int $professorId,
        string $status,
        string $feedback,
        ?float $nota = null
    ): bool {
        $enumStatus = StatusSubmissao::tryFrom($status);
        if ($enumStatus === null) {
            throw new InvalidArgumentException('Status de avaliação inválido.');
        }

        if ($this->obterSubmissaoDoProfessor($submissaoId, $professorId) === null) {
            throw new InvalidArgumentException('Sem permissão para avaliar esta submissão.');
        }

        $subInfo = $this->obterSubmissaoPorId($submissaoId);
        if ($subInfo === false) {
            throw new InvalidArgumentException('Submissão não encontrada.');
        }

        $etapaId = (int) ($subInfo['etapa_id'] ?? 0);
        $statusGravado = $enumStatus->value;
        $xpGanho = $enumStatus->obterXP();

        $notaGravar = null;
        if ($etapaId === 5) {
            if ($xpGanho > 0) {
                if ($nota === null) {
                    throw new InvalidArgumentException('Informe a nota do Trabalho Final (0 a 10).');
                }
                if ($nota < 0 || $nota > 10) {
                    throw new InvalidArgumentException('A nota deve estar entre 0 e 10.');
                }
                $notaGravar = round($nota, 1);
            }
        }

        try {
            $this->conexao->beginTransaction();

            $sql = "UPDATE submissoes_etapa 
                    SET status = :status, feedback = :feedback, nota = :nota,
                        data_validacao = NOW(), validado_por = :prof_id
                    WHERE id = :id";
            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':status', $statusGravado);
            $stmt->bindValue(':feedback', $feedback);
            $stmt->bindValue(':nota', $notaGravar, $notaGravar === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':prof_id', $professorId, PDO::PARAM_INT);
            $stmt->bindValue(':id', $submissaoId, PDO::PARAM_INT);
            $stmt->execute();

            $this->conexao->commit();

            if ($xpGanho > 0) {
                try {
                    $this->conexao->beginTransaction();

                    $sqlXp = "INSERT INTO historico_xp (aluno_id, xp_ganho, status_submissao, etapa_id) 
                              VALUES (:aluno_id, :xp, :status, :etapa_id)";
                    $stmtXp = $this->conexao->prepare($sqlXp);
                    $stmtXp->execute([
                        ':aluno_id' => $subInfo['aluno_id'],
                        ':xp' => $xpGanho,
                        ':status' => $statusGravado,
                        ':etapa_id' => $subInfo['etapa_id'],
                    ]);

                    $sqlUserXp = "UPDATE usuarios SET experiencia_total = experiencia_total + :xp WHERE id = :aluno_id";
                    $stmtUserXp = $this->conexao->prepare($sqlUserXp);
                    $stmtUserXp->execute([
                        ':xp' => $xpGanho,
                        ':aluno_id' => $subInfo['aluno_id'],
                    ]);

                    $this->conexao->commit();
                } catch (PDOException $e) {
                    if ($this->conexao->inTransaction()) {
                        $this->conexao->rollBack();
                    }
                    error_log('ProfessorService::avaliarSubmissao XP: ' . $e->getMessage());
                }
            }

            return true;

        } catch (PDOException $e) {
            if ($this->conexao->inTransaction()) {
                $this->conexao->rollBack();
            }
            throw ExceptionHelper::fromPdo($e, 'Erro ao avaliar.');
        }
    }
}