<?php

declare(strict_types=1);

namespace PhiloQuest\Services;

use PhiloQuest\Enum\TipoPostMural;
use PhiloQuest\Repositories\ConexaoBanco;
use PDO;
use PDOException;

class MuralService
{
    private const TOTAL_ETAPAS_CICLO = 5;

    private PDO $conexao;

    public function __construct()
    {
        $this->conexao = ConexaoBanco::getInstancia()->getConexao();
    }

    public function alunoTemTurma(int $alunoId): bool
    {
        return $this->obterTurmaIdAluno($alunoId) !== null;
    }

    public function obterCodigoTurma(int $alunoId): ?string
    {
        try {
            $stmt = $this->conexao->prepare(
                "SELECT t.codigo_turma
                 FROM usuarios u
                 INNER JOIN turmas t ON t.id = u.turma_id
                 WHERE u.id = :id AND u.tipo_usuario = 'ALUNO'
                 LIMIT 1"
            );
            $stmt->bindValue(':id', $alunoId, PDO::PARAM_INT);
            $stmt->execute();
            $codigo = $stmt->fetchColumn();

            return $codigo !== false ? (string) $codigo : null;
        } catch (PDOException $e) {
            error_log('MuralService::obterCodigoTurma: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Feed da turma: alunos que concluíram o ciclo filosófico completo.
     *
     * @return list<array{
     *   tipo: string,
     *   autor_nome: string,
     *   autor_inicial: string,
     *   mensagem: string,
     *   data_evento: string,
     *   icone: string,
     *   rotulo_tipo: string,
     *   cor: string,
     *   bg: string
     * }>
     */
    public function listarFeedTurma(int $alunoId, int $limite = 40): array
    {
        $turmaId = $this->obterTurmaIdAluno($alunoId);
        if ($turmaId === null) {
            return [];
        }

        try {
            $sql = "
                SELECT
                    'CICLO_CONCLUIDO' AS tipo,
                    MAX(se.data_validacao) AS data_evento,
                    u.nome_completo AS autor_nome,
                    'Concluiu o ciclo filosófico completo!' AS mensagem
                FROM usuarios u
                INNER JOIN submissoes_etapa se ON se.aluno_id = u.id
                WHERE u.turma_id = :turma_id
                  AND u.tipo_usuario = 'ALUNO'
                  AND se.status IN ('APROVADO', 'APROVADO_BEM_FEITO', 'APROVADO_EXCELENTE')
                  AND se.data_validacao IS NOT NULL
                GROUP BY u.id, u.nome_completo
                HAVING COUNT(DISTINCT se.etapa_id) >= :total_etapas
                ORDER BY data_evento DESC
                LIMIT :limite
            ";

            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':turma_id', $turmaId, PDO::PARAM_INT);
            $stmt->bindValue(':total_etapas', self::TOTAL_ETAPAS_CICLO, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();

            $posts = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $tipoEnum = TipoPostMural::fromValor((string) $row['tipo']);
                $cores = $tipoEnum->coresBadge();
                $nome = (string) $row['autor_nome'];

                $posts[] = [
                    'tipo' => $tipoEnum->value,
                    'autor_nome' => $nome,
                    'autor_inicial' => mb_strtoupper(mb_substr($nome, 0, 1)),
                    'mensagem' => (string) $row['mensagem'],
                    'data_evento' => (string) $row['data_evento'],
                    'icone' => $tipoEnum->icone(),
                    'rotulo_tipo' => $tipoEnum->rotulo(),
                    'cor' => $cores['cor'],
                    'bg' => $cores['bg'],
                ];
            }

            return $posts;
        } catch (PDOException $e) {
            error_log('MuralService::listarFeedTurma: ' . $e->getMessage());
            return [];
        }
    }

    private function obterTurmaIdAluno(int $alunoId): ?int
    {
        try {
            $stmt = $this->conexao->prepare(
                "SELECT turma_id FROM usuarios WHERE id = :id AND tipo_usuario = 'ALUNO' LIMIT 1"
            );
            $stmt->bindValue(':id', $alunoId, PDO::PARAM_INT);
            $stmt->execute();
            $turmaId = $stmt->fetchColumn();

            return $turmaId !== false ? (int) $turmaId : null;
        } catch (PDOException $e) {
            return null;
        }
    }
}
