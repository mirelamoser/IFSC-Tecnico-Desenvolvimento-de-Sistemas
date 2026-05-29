<?php

declare(strict_types=1);

namespace PhiloQuest\Services;

use PhiloQuest\Enum\StatusEntregaMissao;
use PhiloQuest\Enum\StatusSubmissao;
use PhiloQuest\Repositories\ConexaoBanco;
use InvalidArgumentException;
use PDO;
use PDOException;
use Exception;

class MissaoExtraService
{
    private PDO $conexao;

    public function __construct()
    {
        $this->conexao = ConexaoBanco::getInstancia()->getConexao();
    }

    public function criarMissao(
        int $professorId,
        int $turmaId,
        string $titulo,
        string $descricao,
        ?string $linkReferencia,
        int $xpRecompensa = 80
    ): int {
        $titulo = trim($titulo);
        $descricao = trim($descricao);
        $linkReferencia = $linkReferencia !== null ? trim($linkReferencia) : null;

        if ($titulo === '' || $descricao === '') {
            throw new InvalidArgumentException('Título e descrição da missão são obrigatórios.');
        }

        if (!$this->turmaPertenceAoProfessor($turmaId, $professorId)) {
            throw new InvalidArgumentException('Turma inválida ou não vinculada a este professor.');
        }

        if ($xpRecompensa < 0) {
            $xpRecompensa = 80;
        }

        try {
            $stmt = $this->conexao->prepare(
                "INSERT INTO missoes_extras (titulo, descricao, link_referencia, turma_id, professor_id, xp_recompensa)
                 VALUES (:titulo, :descricao, :link, :turma_id, :prof_id, :xp)"
            );
            $stmt->execute([
                ':titulo' => $titulo,
                ':descricao' => $descricao,
                ':link' => $linkReferencia !== '' ? $linkReferencia : null,
                ':turma_id' => $turmaId,
                ':prof_id' => $professorId,
                ':xp' => $xpRecompensa,
            ]);

            return (int) $this->conexao->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception('Erro ao criar missão: ' . $e->getMessage());
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarMissoesDoProfessor(int $professorId): array
    {
        try {
            $sql = "SELECT m.id, m.titulo, m.descricao, m.link_referencia, m.data_criacao, m.xp_recompensa,
                           t.codigo_turma, t.id AS turma_id,
                           (SELECT COUNT(*) FROM usuarios u WHERE u.turma_id = m.turma_id AND u.tipo_usuario = 'ALUNO') AS total_alunos,
                           (SELECT COUNT(*) FROM entregas_missoes em WHERE em.missao_id = m.id) AS total_entregas,
                           (SELECT COUNT(*) FROM entregas_missoes em WHERE em.missao_id = m.id AND em.status = 'PENDENTE') AS pendentes_avaliacao
                    FROM missoes_extras m
                    INNER JOIN turmas t ON t.id = m.turma_id
                    WHERE m.professor_id = :prof_id
                    ORDER BY m.data_criacao DESC";
            $stmt = $this->conexao->prepare($sql);
            $stmt->execute([':prof_id' => $professorId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function obterMissaoDoProfessor(int $missaoId, int $professorId): ?array
    {
        try {
            $stmt = $this->conexao->prepare(
                "SELECT m.*, t.codigo_turma, u.nome_completo AS professor_nome
                 FROM missoes_extras m
                 INNER JOIN turmas t ON t.id = m.turma_id
                 INNER JOIN usuarios u ON u.id = m.professor_id
                 WHERE m.id = :id AND m.professor_id = :prof_id"
            );
            $stmt->execute([':id' => $missaoId, ':prof_id' => $professorId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row !== false ? $row : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarEntregasDaMissao(int $missaoId, int $professorId): array
    {
        $missao = $this->obterMissaoDoProfessor($missaoId, $professorId);
        if ($missao === null) {
            return [];
        }

        try {
            $stmt = $this->conexao->prepare(
                "SELECT em.id, em.resposta_texto, em.status, em.feedback_professor, em.data_entrega,
                        em.xp_atribuido, u.nome_completo AS aluno_nome, u.id AS aluno_id
                 FROM entregas_missoes em
                 INNER JOIN usuarios u ON u.id = em.aluno_id
                 WHERE em.missao_id = :missao_id
                 ORDER BY em.data_entrega ASC"
            );
            $stmt->execute([':missao_id' => $missaoId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function aprovarEntrega(
        int $entregaId,
        int $professorId,
        string $statusXp,
        string $feedback = ''
    ): bool {
        $enumXp = StatusSubmissao::tryFrom($statusXp);
        if ($enumXp === null || $enumXp->obterXP() === 0) {
            throw new InvalidArgumentException('Selecione um nível de aprovação válido com XP.');
        }

        $entrega = $this->obterEntregaParaProfessor($entregaId, $professorId);
        if ($entrega === null) {
            throw new InvalidArgumentException('Entrega não encontrada ou sem permissão.');
        }

        if ($entrega['status'] !== StatusEntregaMissao::PENDENTE->value) {
            throw new InvalidArgumentException('Esta entrega já foi avaliada.');
        }

        $xpGanho = $enumXp->obterXP();

        try {
            $this->conexao->beginTransaction();

            $stmt = $this->conexao->prepare(
                "UPDATE entregas_missoes
                 SET status = 'APROVADO', feedback_professor = :feedback, xp_atribuido = :xp,
                     data_avaliacao = NOW(), avaliado_por = :prof_id
                 WHERE id = :id"
            );
            $stmt->execute([
                ':feedback' => $feedback,
                ':xp' => $xpGanho,
                ':prof_id' => $professorId,
                ':id' => $entregaId,
            ]);

            $stmtXp = $this->conexao->prepare(
                "INSERT INTO historico_xp (aluno_id, xp_ganho, status_submissao, etapa_id)
                 VALUES (:aluno_id, :xp, :status, NULL)"
            );
            $stmtXp->execute([
                ':aluno_id' => (int) $entrega['aluno_id'],
                ':xp' => $xpGanho,
                ':status' => 'MISSAO_' . $enumXp->value,
            ]);

            $stmtUser = $this->conexao->prepare(
                "UPDATE usuarios SET experiencia_total = experiencia_total + :xp WHERE id = :aluno_id"
            );
            $stmtUser->execute([
                ':xp' => $xpGanho,
                ':aluno_id' => (int) $entrega['aluno_id'],
            ]);

            $this->conexao->commit();
            return true;
        } catch (PDOException $e) {
            $this->conexao->rollBack();
            throw new Exception('Erro ao aprovar entrega: ' . $e->getMessage());
        }
    }

    public function solicitarRevisaoEntrega(int $entregaId, int $professorId, string $feedback): bool
    {
        if (trim($feedback) === '') {
            throw new InvalidArgumentException('Informe um feedback para o aluno revisar a entrega.');
        }

        $entrega = $this->obterEntregaParaProfessor($entregaId, $professorId);
        if ($entrega === null) {
            throw new InvalidArgumentException('Entrega não encontrada ou sem permissão.');
        }

        if ($entrega['status'] !== StatusEntregaMissao::PENDENTE->value) {
            throw new InvalidArgumentException('Esta entrega já foi avaliada.');
        }

        try {
            $stmt = $this->conexao->prepare(
                "UPDATE entregas_missoes
                 SET status = 'REVISAR', feedback_professor = :feedback,
                     data_avaliacao = NOW(), avaliado_por = :prof_id, xp_atribuido = 0
                 WHERE id = :id"
            );
            $stmt->execute([
                ':feedback' => trim($feedback),
                ':prof_id' => $professorId,
                ':id' => $entregaId,
            ]);

            return true;
        } catch (PDOException $e) {
            throw new Exception('Erro ao solicitar revisão: ' . $e->getMessage());
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarMissoesDaTurmaDoAluno(int $alunoId): array
    {
        try {
            $stmt = $this->conexao->prepare(
                "SELECT m.id, m.titulo, m.descricao, m.link_referencia, m.data_criacao, m.xp_recompensa,
                        t.codigo_turma, u.nome_completo AS professor_nome,
                        em.id AS entrega_id, em.resposta_texto, em.status AS status_entrega,
                        em.feedback_professor, em.data_entrega, em.xp_atribuido
                 FROM missoes_extras m
                 INNER JOIN turmas t ON t.id = m.turma_id
                 INNER JOIN usuarios u ON u.id = m.professor_id
                 INNER JOIN usuarios al ON al.turma_id = m.turma_id AND al.id = :aluno_id
                 LEFT JOIN entregas_missoes em ON em.missao_id = m.id AND em.aluno_id = :aluno_id2
                 WHERE al.tipo_usuario = 'ALUNO'
                 ORDER BY m.data_criacao DESC"
            );
            $stmt->execute([':aluno_id' => $alunoId, ':aluno_id2' => $alunoId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function submeterResposta(int $alunoId, int $missaoId, string $resposta): bool
    {
        $resposta = trim($resposta);
        if ($resposta === '') {
            throw new InvalidArgumentException('Escreva sua resposta antes de enviar.');
        }

        $missao = $this->obterMissaoParaAluno($missaoId, $alunoId);
        if ($missao === null) {
            throw new InvalidArgumentException('Missão não encontrada ou não disponível para sua turma.');
        }

        try {
            $stmtCheck = $this->conexao->prepare(
                "SELECT id, status FROM entregas_missoes WHERE missao_id = :missao_id AND aluno_id = :aluno_id"
            );
            $stmtCheck->execute([':missao_id' => $missaoId, ':aluno_id' => $alunoId]);
            $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existente) {
                $status = (string) $existente['status'];
                if ($status === StatusEntregaMissao::PENDENTE->value) {
                    throw new InvalidArgumentException('Sua resposta já foi enviada. Aguarde a avaliação do professor.');
                }
                if ($status === StatusEntregaMissao::APROVADO->value) {
                    throw new InvalidArgumentException('Esta missão já foi aprovada.');
                }
                if ($status === StatusEntregaMissao::REVISAR->value) {
                    $stmt = $this->conexao->prepare(
                        "UPDATE entregas_missoes
                         SET resposta_texto = :resposta, status = 'PENDENTE', data_entrega = NOW(),
                             feedback_professor = NULL, xp_atribuido = 0, data_avaliacao = NULL, avaliado_por = NULL
                         WHERE id = :id"
                    );
                    $stmt->execute([':resposta' => $resposta, ':id' => $existente['id']]);
                    return true;
                }
            }

            $stmt = $this->conexao->prepare(
                "INSERT INTO entregas_missoes (missao_id, aluno_id, resposta_texto, status)
                 VALUES (:missao_id, :aluno_id, :resposta, 'PENDENTE')"
            );
            $stmt->execute([
                ':missao_id' => $missaoId,
                ':aluno_id' => $alunoId,
                ':resposta' => $resposta,
            ]);

            return true;
        } catch (PDOException $e) {
            throw new Exception('Erro ao enviar resposta: ' . $e->getMessage());
        }
    }

    public function alunoTemTurma(int $alunoId): bool
    {
        $stmt = $this->conexao->prepare("SELECT turma_id FROM usuarios WHERE id = :id AND tipo_usuario = 'ALUNO'");
        $stmt->execute([':id' => $alunoId]);
        $turmaId = $stmt->fetchColumn();

        return $turmaId !== false && $turmaId !== null;
    }

    private function turmaPertenceAoProfessor(int $turmaId, int $professorId): bool
    {
        $stmt = $this->conexao->prepare(
            "SELECT id FROM turmas WHERE id = :turma_id AND professor_id = :prof_id"
        );
        $stmt->execute([':turma_id' => $turmaId, ':prof_id' => $professorId]);

        return $stmt->fetchColumn() !== false;
    }

    private function obterEntregaParaProfessor(int $entregaId, int $professorId): ?array
    {
        $stmt = $this->conexao->prepare(
            "SELECT em.id, em.aluno_id, em.status, em.missao_id
             FROM entregas_missoes em
             INNER JOIN missoes_extras m ON m.id = em.missao_id
             WHERE em.id = :id AND m.professor_id = :prof_id"
        );
        $stmt->execute([':id' => $entregaId, ':prof_id' => $professorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    private function obterMissaoParaAluno(int $missaoId, int $alunoId): ?array
    {
        $stmt = $this->conexao->prepare(
            "SELECT m.id
             FROM missoes_extras m
             INNER JOIN usuarios u ON u.turma_id = m.turma_id AND u.id = :aluno_id
             WHERE m.id = :missao_id AND u.tipo_usuario = 'ALUNO'"
        );
        $stmt->execute([':missao_id' => $missaoId, ':aluno_id' => $alunoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }
}
