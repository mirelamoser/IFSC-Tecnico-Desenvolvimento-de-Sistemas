<?php

declare(strict_types=1);

namespace PhiloQuest\Services;

use PhiloQuest\Repositories\ConexaoBanco;
use PhiloQuest\Enum\StatusSubmissao;
use PDO;
use Exception;
use PDOException;

class XPService {
    private $conexao;

    public function __construct() {
        $this->conexao = ConexaoBanco::getInstancia()->getConexao();
    }

    /**
     * Adiciona XP ao aluno baseado no status da submissão
     * Registra também no histórico de ganhos de XP
     * 
     * @param int $alunoId ID do aluno
     * @param StatusSubmissao $status Status da submissão
     * @param int $etapaId ID da etapa (opcional, para referência)
     * @return array ['sucesso' => bool, 'mensagem' => string, 'xp_ganho' => int, 'xp_total' => int]
     */
    public function adicionarXP(int $alunoId, StatusSubmissao $status, ?int $etapaId = null): array {
        try {
            $xpGanho = $status->obterXP();

            // Se não há XP a ganhar (status não aprovado), retorna 0
            if ($xpGanho === 0) {
                return [
                    'sucesso' => false,
                    'mensagem' => 'Este status não fornece XP',
                    'xp_ganho' => 0,
                    'xp_total' => $this->obterXPTotalAluno($alunoId)
                ];
            }

            // Inicia transação para garantir consistência
            $this->conexao->beginTransaction();

            // 1. Atualiza o XP total do aluno
            $stmt = $this->conexao->prepare(
                "UPDATE usuarios 
                 SET experiencia_total = COALESCE(experiencia_total, 0) + :xp
                 WHERE id = :aluno_id AND tipo_usuario = 'ALUNO'"
            );
            $stmt->bindValue(':xp', $xpGanho, PDO::PARAM_INT);
            $stmt->bindValue(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                throw new Exception("Aluno não encontrado ou não é aluno");
            }

            // 2. Registra no histórico de ganhos de XP
            $stmt = $this->conexao->prepare(
                "INSERT INTO historico_xp 
                 (aluno_id, xp_ganho, status_submissao, etapa_id, data_ganho) 
                 VALUES (:aluno_id, :xp, :status, :etapa_id, NOW())"
            );
            $stmt->bindValue(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmt->bindValue(':xp', $xpGanho, PDO::PARAM_INT);
            $stmt->bindValue(':status', $status->value, PDO::PARAM_STR);
            $stmt->bindValue(':etapa_id', $etapaId, PDO::PARAM_INT);
            $stmt->execute();

            // Confirma a transação
            $this->conexao->commit();

            $xpTotal = $this->obterXPTotalAluno($alunoId);

            return [
                'sucesso' => true,
                'mensagem' => "✓ {$xpGanho} XP adicionados ao aluno! Total: {$xpTotal} XP",
                'xp_ganho' => $xpGanho,
                'xp_total' => $xpTotal
            ];

        } catch (PDOException $e) {
            $this->conexao->rollBack();
            throw new Exception("Erro ao adicionar XP: " . $e->getMessage());
        }
    }

    /**
     * Obtém o XP total do aluno
     */
    public function obterXPTotalAluno(int $alunoId): int {
        try {
            $stmt = $this->conexao->prepare(
                "SELECT COALESCE(experiencia_total, 0) as xp_total 
                 FROM usuarios 
                 WHERE id = :aluno_id AND tipo_usuario = 'ALUNO'"
            );
            $stmt->bindValue(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmt->execute();

            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado['xp_total'] ?? 0;

        } catch (PDOException $e) {
            throw new Exception("Erro ao obter XP total: " . $e->getMessage());
        }
    }

    /**
     * Obtém o histórico de XP do aluno (últimas N submissões)
     */
    public function obterHistoricoXP(int $alunoId, int $limite = 10): array {
        try {
            $stmt = $this->conexao->prepare(
                "SELECT hx.*, 
                        e.numero as etapa_numero, 
                        e.titulo as etapa_titulo
                 FROM historico_xp hx
                 LEFT JOIN etapas e ON hx.etapa_id = e.id
                 WHERE hx.aluno_id = :aluno_id
                 ORDER BY hx.data_ganho DESC
                 LIMIT :limite"
            );
            $stmt->bindValue(':aluno_id', $alunoId, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

        } catch (PDOException $e) {
            throw new Exception("Erro ao obter histórico de XP: " . $e->getMessage());
        }
    }

    /**
     * Obtém ranking de alunos por XP (para gamificação futura)
     */
    public function obterRankingXP(?int $turmaId = null, int $limite = 10): array {
        try {
            $query = "SELECT u.id, u.nome_completo, u.matricula, 
                             COALESCE(u.experiencia_total, 0) as xp_total,
                             t.codigo_turma
                      FROM usuarios u
                      LEFT JOIN turmas t ON u.turma_id = t.id
                      WHERE u.tipo_usuario = 'ALUNO'";

            if ($turmaId !== null) {
                $query .= " AND u.turma_id = " . (int)$turmaId;
            }

            $query .= " ORDER BY COALESCE(u.experiencia_total, 0) DESC
                        LIMIT " . (int)$limite;

            $stmt = $this->conexao->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

        } catch (PDOException $e) {
            throw new Exception("Erro ao obter ranking: " . $e->getMessage());
        }
    }

    /**
     * Calcula o nível do aluno baseado no XP (sistema de milestone)
     * Exemplo: Cada 1000 XP = 1 nível
     */
    public function calcularNivel(int $xpTotal): int {
        return intval($xpTotal / 1000) + 1;
    }

    /**
     * Calcula quantos XP faltam para o próximo nível
     */
    public function calcularXPParaProximoNivel(int $xpTotal): int {
        $proximoMilestone = (intval($xpTotal / 1000) + 1) * 1000;
        return max(0, $proximoMilestone - $xpTotal);
    }

    /**
     * Calcula a barra de progresso (0-100) até o próximo nível
     */
    public function calcularProgressoNivel(int $xpTotal): int {
        $xpNoNivel = $xpTotal % 1000;
        return intval(($xpNoNivel / 1000) * 100);
    }
}
